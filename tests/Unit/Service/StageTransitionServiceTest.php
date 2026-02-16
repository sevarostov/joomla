<?php

namespace App\Tests\Unit\Service;

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Database\Mysqli\MysqliQuery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Joomla\Component\Crmstages\Administrator\Service\StageTransitionService;
use Joomla\Database\DatabaseInterface;

#[CoversClass(StageTransitionService::class)]
#[CoversMethod(StageTransitionService::class, 'performTransition')]
class StageTransitionServiceTest extends TestCase
{
	private StageTransitionService $service;

	protected function setUp(): void
	{
		$this->db = Factory::getContainer()->get(DatabaseInterface::class);
		$this->app = Factory::getApplication();

		$this->service = new StageTransitionService($this->app, $this->db);
	}


	public function testPerformTransition_Success()
	{
		$companyId = 1;
		$revertStageCode = 'C1';
		$targetStageCode = 'C2';

		$this->revertPerformTransition($companyId, $targetStageCode, $revertStageCode);

		$result = $this->service->performTransition($companyId, $targetStageCode);

		$this->assertTrue($result);
	}

	/**
	 * Revert a stage transition (rollback to previous stage) && removes log
	 *
	 * @param int $companyId
	 * @param string $targetStageCode Current stage to revert FROM
	 * @param string|null $revertToStageCode Stage to revert TO (optional, defaults to previous)
	 *
	 * @return bool True on success, false on failure
	 */
	public function revertPerformTransition(
		int $companyId,
		string $targetStageCode,
		?string $revertToStageCode = null,
	): bool
	{
		$db = $this->db;

		try {
			$db->transactionStart();

			// 1. Get current stage ID (targetStageCode is what we're reverting FROM)
			$stageQuery = $db->getQuery(true)
				->select('id')
				->from($db->quoteName('#__crm_stages'))
				->where($db->quoteName('code') . ' = :code')
				->bind(':code', $targetStageCode);
			$db->setQuery($stageQuery);
			$currentStageId = $db->loadResult();

			if (!$currentStageId) {
				$this->app->enqueueMessage(
					Text::_('COM_CRMSTAGES_ERROR_STAGE_NOT_FOUND'),
					'error',
				);
				$db->transactionRollback();
				return false;
			}

			// 2. Determine revert stage (either specified or from history)
			if ($revertToStageCode) {
				// Use explicitly provided stage
				$revertStageQuery = $db->getQuery(true)
					->select('id')
					->from($db->quoteName('#__crm_stages'))
					->where($db->quoteName('code') . ' = :code')
					->bind(':code', $revertToStageCode);
				$db->setQuery($revertStageQuery);
				$revertStageId = $db->loadResult();
			} else {
				// Find most recent previous stage from action log
				$historyQuery = $db->getQuery(true)
					->select('stage_id')
					->from($db->quoteName('#__crm_action_log'))
					->where($db->quoteName('company_id') . ' = ' . (int)$companyId)
					->where($db->quoteName('stage_id') . ' != ' . (int)$currentStageId)
					->order('created DESC')
					->setLimit(1);
				$db->setQuery($historyQuery);
				$revertStageId = $db->loadResult();
			}

			if (!$revertStageId) {
				$this->app->enqueueMessage(
					Text::_('COM_CRMSTAGES_ERROR_NO_PREVIOUS_STAGE'),
					'error',
				);
				$db->transactionRollback();
				return false;
			}

			// 3. Log the revert action
			$logQuery = $db->getQuery(true);
			$columns = ['company_id', 'stage_id', 'action_id', 'created', 'notes'];
			$values = [
				(int)$companyId,
				(int)$revertStageId,
				0, // No specific action for revert
				$db->quote(Factory::getDate()->toSql()),
				$db->quote('Reverted from stage ' . $targetStageCode)
			];

			/**  @var MysqliQuery $logQuery */
			$logQuery
				->delete($db->quoteName('#__crm_action_log'))
				->where(
					[
						'company_id' => $companyId,
						'stage_id' => $currentStageId,
					],
				);
			$db->setQuery($logQuery);
			$db->execute();

			// 4. Update company stage
			$updateQuery = $db->getQuery(true)
				->update($db->quoteName('#__crm_companies'))
				->set($db->quoteName('stage_id') . ' = ' . (int)$revertStageId)
				->where($db->quoteName('id') . ' = ' . (int)$companyId);
			$db->setQuery($updateQuery);
			$db->execute();

			$db->transactionCommit();

			$this->app->enqueueMessage(
				Text::sprintf('COM_CRMSTAGES_TRANSITION_REVERTED', $revertStageId),
				'message',
			);

			return true;

		} catch (Exception $e) {
			$db->transactionRollback();
			$this->app->enqueueMessage(
				Text::_('COM_CRMSTAGES_ERROR_REVERT_FAILED') . ': ' . $e->getMessage(),
				'error',
			);
			return false;
		}
	}


}
