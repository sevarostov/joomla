<?php

namespace Joomla\Component\Crmstages\Administrator\Controller;

use Exception;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Router\Route;
use Joomla\Component\Crmstages\Administrator\Helper\StageHelper;
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

		if (!$companyId) {
			return new JsonResponse(400, 'Invalid request: missing company_id');
		}

				try {
		$currentStage = $this->getCurrentStage($companyId);

		if (!$currentStage) {
			return new JsonResponse(404, 'Company not found');
		}

		$targetStageCode = $this->getTargetStageCode($currentStage->stage_ordering + 1);

		$currentStageCode = $currentStage->stage_code;
		$transitionResult = $this->canTransition(
			$currentStageCode,
			$targetStageCode->stage_code,
			$companyId,
		);

		if (!$transitionResult['allowed']) {
			return new JsonResponse(403, 'Transition not allowed: ' . $transitionResult['reason']);
		}

		$success = $this->performTransition(
			$companyId,
			$targetStageCode->stage_code,
		);

		if ($success) {

			$this->setRedirect(Route::_('index.php?option=com_crmstages&view=companycard&id=' . $companyId, false));

		} else {
			return new JsonResponse(500, 'Failed to update stage');
		}

				} catch (Exception $e) {
					return new JsonResponse(500, 'An error occured');
				}
	}

	/**
	 * Get current stage of company
	 *
	 * @param int $companyId
	 *
	 * @return object|null
	 */
	private function getCurrentStage(int $companyId)
	{
		$query = $this->db->getQuery(true)
			->select([
				'c.id AS company_id',
				's.id AS stage_id',
				's.code AS stage_code',
				's.name AS stage_name',
				's.ordering AS stage_ordering'
			])
			->from($this->db->quoteName('#__crm_companies', 'c'))
			->join(
				'INNER',
				$this->db->quoteName('#__crm_stages', 's'),
				's.id = c.stage_id',
			)
			->where($this->db->quoteName('c.id') . ' = :companyid')
			->bind(':companyid', $companyId, ParameterType::INTEGER);

		$this->db->setQuery($query);
		return $this->db->loadObject();
	}

	/**
	 * Get target stage
	 *
	 * @param int $ordering
	 *
	 * @return object|null
	 */
	private function getTargetStageCode(int $ordering)
	{
		$query = $this->db->getQuery(true)
			->select([
				's.id AS stage_id',
				's.code AS stage_code',
				's.name AS stage_name',
				's.ordering AS stage_ordering'
			])
			->from($this->db->quoteName('#__crm_stages', 's'))
			->where($this->db->quoteName('s.ordering') . ' = :ordering')
			->bind(':ordering', $ordering, ParameterType::INTEGER);

		$this->db->setQuery($query);
		return $this->db->loadObject();
	}

	/**
	 * Check if transition from current to target stage is allowed
	 *
	 * @param string $currentStage
	 * @param string $targetStageCode
	 * @param int $companyId
	 *
	 * @return array ['allowed' => bool, 'reason' => string]
	 */
	private function canTransition(string $currentStage, string $targetStageCode, int $companyId): array
	{
		// Get stage configuration
		$stages = StageHelper::getStagesConfig();

		if (!isset($stages[$currentStage])) {
			return [
				'allowed' => false,
				'reason' => 'Current stage does not exist'
			];
		}

		if (!isset($stages[$targetStageCode])) {
			return [
				'allowed' => false,
				'reason' => 'Target stage does not exist'
			];
		}

		// Rule 1: Direct transition must be allowed
		if (!in_array($targetStageCode, $stages[$currentStage]['allowed_transitions'])) {
			return [
				'allowed' => false,
				'reason' => sprintf(
					'Transition from %s to %s is not permitted',
					$currentStage,
					$targetStageCode,
				)
			];
		}

		// Rule 2: All required actions must have occurred
		foreach ($stages[$targetStageCode]['required_events'] as $actionCode) {
			if (!$this->hasEventOccurred($companyId, $actionCode)) {
				return [
					'allowed' => false,
					'reason' => sprintf(
						'Required event "%s" has not occurred',
						$actionCode,
					)
				];
			}
		}

		// Rule 3: No blocking conditions
		if (in_array($currentStage, $stages[$targetStageCode]['blocked_transitions'])) {
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
	 * @param string $actionCode
	 *
	 * @return bool
	 */
	private function hasEventOccurred(int $companyId, string $actionCode): bool
	{
		$query = $this->db->getQuery(true)
			->select('COUNT(*)')
			->from($this->db->quoteName('#__crm_action_log', 'l'))
			->join(
				'INNER',
				'#__crm_actions as action',
				'action.id = l.action_id'
			)
			->where([
				$this->db->quoteName('company_id') . ' = :companyid',
				$this->db->quoteName('action.code') . ' = :actionCode',
			])
			->bind(':companyid', $companyId, ParameterType::INTEGER)
			->bind(':actionCode', $actionCode, ParameterType::STRING);

		$this->db->setQuery($query);

		return (int)$this->db->loadResult() > 0;
	}

	/**
	 * Perform the stage transition
	 *
	 * @param int $companyId
	 * @param string $targetStageCode
	 *
	 * @return bool
	 */
	private function performTransition(int $companyId, string $targetStageCode): bool
	{
		$db = $this->db;

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

		$actionQuery = $db->getQuery(true)
			->select('a.id as action_id')
			->from($db->quoteName('#__crm_stage_actions', 'sa'))
			->join(
				'INNER',
				$this->db->quoteName('#__crm_actions', 'a'),
				'a.id = sa.action_id',
			)
			->join(
				'INNER',
				$this->db->quoteName('#__crm_stages', 's'),
				's.id = sa.stage_id',
			)
			->where($db->quoteName('s.id') . ' = :id')
			->bind(':id', $targetStageId);
		$db->setQuery($stageQuery);
		$targetActionId = $db->loadResult();

		if (!$targetActionId) {
			$this->app->enqueueMessage(
				Text::_('COM_CRMSTAGES_ERROR_ACTION_NOT_FOUND'),
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
				'action_id',
				'created'
			];
			$values = [
				(int)$companyId,
				(int)$targetStageId,
				(int)$targetActionId,
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
				Text::sprintf('COM_CRMSTAGES_TRANSITION_SUCCESS', $targetStageId),
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
