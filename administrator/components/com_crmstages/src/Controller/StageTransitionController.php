<?php

namespace Joomla\Component\Crmstages\Controller;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\Component\Crmstages\Helper\StageHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

defined('_JEXEC') or die;

class StageTransitionController extends BaseController
{
	/**
	 * @var DatabaseInterface
	 */
	private $db;

	/**
	 * @var CMSApplication
	 */
	protected $app;

	public function __construct($config = [])
	{
		parent::__construct($config);
		$this->db = Factory::getContainer()->get(DatabaseInterface::class);
		$this->app = Factory::getApplication();
	}

	/**
	 * Handle stage transition request
	 *
	 * Given a company is in stage X
	 * When user requests transition to stage Y
	 * Then validate rules and update if allowed
	 */
	public function transition()
	{
		$input = $this->app->getInput();
		$companyId = $input->getInt('company_id', 0);
		$targetStageCode = $input->getString('stage', '');

		// Validate input
		if (!$companyId || !$targetStageCode) {
			return new JsonResponse(400, 'Invalid request: missing company_id or stage');
		}

		try {
			// Given: Load current company state
			$currentState = $this->getCurrentState($companyId);

			if (!$currentState) {
				return new JsonResponse(404, 'Company not found');
			}

			$currentStageCode = $currentState->stage_code;

			// When: Check transition eligibility
			$transitionResult = $this->canTransition(
				$currentStageCode,
				$targetStageCode,
				$companyId,
			);

			if (!$transitionResult['allowed']) {
				return new JsonResponse(403, 'Transition not allowed: ' . $transitionResult['reason']);
			}

			// Then: Execute transition
			$success = $this->performTransition(
				$companyId,
				$targetStageCode,
				$currentState->stage_id,
			);

			if ($success) {
				return new JsonResponse(403, sprintf(
					'%s to %s Stage transition successful',
					$currentStageCode,
					$targetStageCode,
				));
			} else {
				return new JsonResponse(500, 'Failed to update stage');
			}

		} catch (\Exception $e) {
			return new JsonResponse(500, 'An error occured');
		}
	}

	/**
	 * Get current state of company
	 *
	 * @param int $companyId
	 *
	 * @return object|null
	 */
	private function getCurrentState(int $companyId)
	{
		$query = $this->db->getQuery(true)
			->select([
				'c.id AS company_id',
				's.id AS stage_id',
				's.code AS stage_code',
				's.name AS stage_name'
			])
			->from($this->db->quoteName('#__crm_companies', 'c'))
			->join(
				'INNER',
				$this->db->quoteName('#__crm_action_log', 'l'),
				'l.company_id = c.id',
			)
			->join(
				'INNER',
				$this->db->quoteName('#__crm_stages', 's'),
				's.id = l.stage_id',
			)
			->where($this->db->quoteName('c.id') . ' = :companyid')
			->order($this->db->quoteName('l.created') . ' DESC')
			->setLimit(1)
			->bind(':companyid', $companyId, ParameterType::INTEGER);


		$this->db->setQuery($query);
		return $this->db->loadObject();
	}

	/**
	 * Check if transition from current to target stage is allowed
	 *
	 * @param string $currentStage
	 * @param string $targetStage
	 * @param int $companyId
	 *
	 * @return array ['allowed' => bool, 'reason' => string]
	 */
	private function canTransition(string $currentStage, string $targetStage, int $companyId): array
	{
		// Get stage configuration
		$stages = StageHelper::getStagesConfig();

		if (!isset($stages[$currentStage])) {
			return [
				'allowed' => false,
				'reason' => 'Current stage does not exist'
			];
		}

		if (!isset($stages[$targetStage])) {
			return [
				'allowed' => false,
				'reason' => 'Target stage does not exist'
			];
		}

		// Rule 1: Direct transition must be allowed
		if (!in_array($targetStage, $stages[$currentStage]['allowed_transitions'])) {
			return [
				'allowed' => false,
				'reason' => sprintf(
					'Transition from %s to %s is not permitted',
					$currentStage,
					$targetStage,
				)
			];
		}

		// Rule 2: All required actions must have occurred
		foreach ($stages[$targetStage]['required_events'] as $stageId) {
			if (!$this->hasEventOccurred($companyId, $stages[$targetStage]['allowed_transitions'], $stageId)) {
				return [
					'allowed' => false,
					'reason' => sprintf(
						'Required event "%s" has not occurred',
						$stageId,
					)
				];
			}
		}

		// Rule 3: No blocking conditions
		if (in_array($currentStage, $stages[$targetStage]['blocked_transitions'])) {
			return [
				'allowed' => false,
				'reason' => 'Transition blocked by business rules'
			];
		}

		return ['allowed' => true, 'reason' => ''];
	}

	/**
	 * Check if a specific event has occurred for company
	 *
	 * @param int $companyId
	 * @param int $stageId
	 * @param int $actionid
	 *
	 * @return bool
	 */
	private function hasEventOccurred(int $companyId, int $stageId, int $actionid): bool
	{
		$query = $this->db->getQuery(true)
			->select('COUNT(*)')
			->from($this->db->quoteName('#__crm_action_log'))
			->where([
				$this->db->quoteName('company_id') . ' = :companyid',
				$this->db->quoteName('stage_id') . ' = :stageid',
				$this->db->quoteName('action_id') . ' = :actionid'
			])
			->bind(':companyid', $companyId, ParameterType::INTEGER)
			->bind(':stageid', $stageId, ParameterType::INTEGER)
			->bind(':actionid', $actionid, ParameterType::INTEGER);

		$this->db->setQuery($query);

		return (int)$this->db->loadResult() > 0;
	}

	/**
	 * Perform the stage transition
	 *
	 * @param int $companyId
	 * @param string $targetStageCode
	 * @param int $currentStageId
	 *
	 * @return bool
	 */
	private function performTransition(int $companyId, string $targetStageCode, int $currentStageId): bool
	{
		$db = $this->db;

		// Get target stage ID
		$stageQuery = $db->getQuery(true)
			->select('id')
			->from($db->quoteName('#__crm_stages'))
			->where($db->quoteName('code') . ' = :code')
			->bind(':code', $targetStageCode);
		$db->setQuery($stageQuery);
		$targetStageId = $db->loadResult();

		if (!$targetStageId) {
			$this->app->enqueueMessage(
				Text::_('COM_CRMSTAGES_ERROR_STAGE_NOT_FOUND'),
				'error',
			);
			return false;
		}

		// Start transaction
		$db->transactionStart();

		try {
			// 1. Insert new action log entry
			$logQuery = $db->getQuery(true);
			$columns = [
				'company_id',
				'stage_id',
				'created'
			];
			$values = [
				(int)$companyId,
				(int)$targetStageId,
				$db->quote(Factory::getDate()->toSql()),
			];

			$logQuery
				->insert($db->quoteName('#__crm_action_log'))
				->columns($db->quoteName($columns))
				->values(implode(',', $values));

			$db->setQuery($logQuery);
			$db->execute();

			// 2. Update company record with new stage (if applicable)
			// Assuming there's a main company table that stores current stage
			$updateQuery = $db->getQuery(true)
				->update($db->quoteName('#__crm_companies'))
				->set($db->quoteName('stage_id') . ' = ' . (int)$targetStageId)
				->where($db->quoteName('id') . ' = ' . (int)$companyId);

			$db->setQuery($updateQuery);
			$db->execute();

			// Commit transaction
			$db->transactionCommit();

			$this->app->enqueueMessage(
				Text::sprintf('COM_CRMSTAGES_TRANSITION_SUCCESS', $targetStageCode),
				'message',
			);

			return true;

		} catch (\Exception $e) {
			// Rollback on any error
			$db->transactionRollback();
			$this->app->enqueueMessage(
				Text::_('COM_CRMSTAGES_ERROR_TRANSITION_FAILED') . ': ' . $e->getMessage(),
				'error',
			);
			return false;
		}
	}
}
