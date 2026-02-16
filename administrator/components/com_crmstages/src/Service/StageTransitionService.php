<?php

namespace Joomla\Component\Crmstages\Administrator\Service;

use Exception;
use Joomla\CMS\Factory;
use Joomla\Component\Crmstages\Administrator\Helper\Constants;
use Joomla\Component\Crmstages\Administrator\Repository\StageRepository;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;

class StageTransitionService
{
	private StageRepository $stageRepo;
	private CMSApplication $app;
	private DatabaseInterface $db;

	public function __construct(
		CMSApplication $app,
		DatabaseInterface $db,
	)
	{
		$this->app = $app;
		$this->db = $db;
		$this->stageRepo = new StageRepository($db);
	}

	/**
	 * Attempt to transition a company to the next stage
	 */
	public function transition(int $companyId): bool
	{
		// 1. Get current stage
		$currentStage = $this->stageRepo->getCurrentStage($companyId);
		if (!$currentStage) {
			$this->app->enqueueMessage(Text::_('COM_CRMSTAGES_ERROR_COMPANY_NOT_FOUND'), 'error');
			return false;
		}

		// 2. Get next stage
		$nextStage = $this->stageRepo->getNextStage($currentStage->ordering);
		if (!$nextStage) {
			$this->app->enqueueMessage(Text::_('COM_CRMSTAGES_NO_NEXT_STAGE'), 'warning');
			return false;
		}

		// 3. Validate transition rules
		$validation = $this->validateTransition($currentStage, $nextStage, $companyId);
		if (!$validation['allowed']) {
			$this->app->enqueueMessage($validation['reason'], 'error');
			return false;
		}

		// 4. Perform transition
		return $this->performTransition($companyId, $nextStage->code);
	}

	/**
	 * Validate transition rules
	 */
	private function validateTransition(object $current, object $target, int $companyId): array
	{
		// Rule 1: Direct transition allowed?
		$allowedTransitions = $this->getAllowedTransitions($current->code);
		if (!in_array($target->code, $allowedTransitions)) {
			return [
				'allowed' => false,
				'reason' => Text::sprintf(
					'COM_CRMSTAGES_TRANSITION_NOT_ALLOWED',
					$current->name,
					$target->name,
				)
			];
		}

		// Rule 2: All required actions must have occurred
		$requiredActions = $this->getRequiredActions($target->code);
		foreach ($requiredActions as $actionCode) {
			if (!$this->stageRepo->hasActionOccurred($companyId, $actionCode)) {
				return [
					'allowed' => false,
					'reason' => Text::sprintf(
						'COM_CRMSTAGES_REQUIRED_ACTION_MISSING',
						$this->getActionName($actionCode),
						$target->name,
					)
				];
			}
		}

		// Rule 3: No blocking conditions
		$blockedTransitions = $this->getBlockedTransitions($target->code);
		if (in_array($current->code, $blockedTransitions)) {
			return [
				'allowed' => false,
				'reason' => Text::_('COM_CRMSTAGES_TRANSITION_BLOCKED_BY_RULES')
			];
		}

		return [
			'allowed' => true,
			'reason' => ''
		];
	}

	/**
	 * Get allowed transitions for a stage code
	 */
	private function getAllowedTransitions(string $stageCode): array
	{
		return match ($stageCode) {
			Constants::STAGE_ICE => [Constants::STAGE_TOUCHED],
			Constants::STAGE_TOUCHED => [Constants::STAGE_AWARE],
			Constants::STAGE_AWARE => [Constants::STAGE_INTERESTED],
			Constants::STAGE_INTERESTED => [Constants::STAGE_DEMO_PLANNED],
			Constants::STAGE_DEMO_PLANNED => [Constants::STAGE_DEMO_DONE],
			Constants::STAGE_DEMO_DONE => [Constants::STAGE_COMMITTED],
			Constants::STAGE_COMMITTED => [Constants::STAGE_CUSTOMER],
			Constants::STAGE_CUSTOMER => [Constants::STAGE_ACTIVATED],
			default => []
		};
	}

	/**
	 * Get required actions for entering a stage
	 */
	private function getRequiredActions(string $stageCode): array
	{
		return match ($stageCode) {
			Constants::STAGE_AWARE => [
				Constants::ACTION_ATTEMPT_CONTACT
			],
			Constants::STAGE_INTERESTED => [
				Constants::ACTION_ATTEMPT_CONTACT,
				Constants::ACTION_CONVO_LPR_COMMENT
			],
			Constants::STAGE_DEMO_PLANNED => [
				Constants::ACTION_ATTEMPT_CONTACT,
				Constants::ACTION_DISCOVERY_FORM,
				Constants::ACTION_CONVO_LPR_COMMENT
			],
			Constants::STAGE_DEMO_DONE => [
				Constants::ACTION_ATTEMPT_CONTACT,
				Constants::ACTION_PLANNING_DEMO,
				Constants::ACTION_DISCOVERY_FORM,
				Constants::ACTION_CONVO_LPR_COMMENT
			],
			Constants::STAGE_COMMITTED => [
				Constants::ACTION_ATTEMPT_CONTACT,
				Constants::ACTION_DEMO_CONDUCTED,
				Constants::ACTION_PLANNING_DEMO,
				Constants::ACTION_DISCOVERY_FORM,
				Constants::ACTION_CONVO_LPR_COMMENT
			],
			Constants::STAGE_CUSTOMER => [
				Constants::ACTION_ATTEMPT_CONTACT,
				Constants::ACTION_INVOICE_ISSUED,
				Constants::ACTION_DEMO_CONDUCTED,
				Constants::ACTION_PLANNING_DEMO,
				Constants::ACTION_DISCOVERY_FORM,
				Constants::ACTION_CONVO_LPR_COMMENT
			],
			Constants::STAGE_ACTIVATED => [
				Constants::ACTION_PAYMENT_RECEIVED,
				Constants::ACTION_ATTEMPT_CONTACT,
				Constants::ACTION_INVOICE_ISSUED,
				Constants::ACTION_DEMO_CONDUCTED,
				Constants::ACTION_PLANNING_DEMO,
				Constants::ACTION_DISCOVERY_FORM,
				Constants::ACTION_CONVO_LPR_COMMENT
			],
			default => []
		};
	}

	/**
	 * Get blocked transitions (stages that cannot transition to this stage)
	 */
	private function getBlockedTransitions(string $stageCode): array
	{
		return match ($stageCode) {
			Constants::STAGE_AWARE => [
				Constants::STAGE_DEMO_PLANNED,
				Constants::STAGE_DEMO_DONE,
				Constants::STAGE_COMMITTED,
				Constants::STAGE_CUSTOMER,
				Constants::STAGE_ACTIVATED
			],
			Constants::STAGE_INTERESTED => [
				Constants::STAGE_DEMO_DONE,
				Constants::STAGE_COMMITTED,
				Constants::STAGE_CUSTOMER,
				Constants::STAGE_ACTIVATED
			],
			Constants::STAGE_DEMO_PLANNED => [
				Constants::STAGE_COMMITTED,
				Constants::STAGE_CUSTOMER,
				Constants::STAGE_ACTIVATED
			],
			Constants::STAGE_DEMO_DONE => [
				Constants::STAGE_CUSTOMER,
				Constants::STAGE_ACTIVATED
			],
			Constants::STAGE_COMMITTED => [
				Constants::STAGE_ACTIVATED
			],
			default => []
		};
	}

	/**
	 * Get human-readable action name
	 */
	private function getActionName(string $actionCode): string
	{
		$names = [
			Constants::ACTION_ATTEMPT_CONTACT => Text::_('COM_CRMSTAGES_ACTION_ATTEMPT_CONTACT'),
			Constants::ACTION_CONVO_LPR_COMMENT => Text::_('COM_CRMSTAGES_ACTION_CONVO_LPR_COMMENT'),
			Constants::ACTION_DISCOVERY_FORM => Text::_('COM_CRMSTAGES_ACTION_DISCOVERY_FORM'),
			Constants::ACTION_PLANNING_DEMO => Text::_('COM_CRMSTAGES_ACTION_PLANNING_DEMO'),
			Constants::ACTION_DEMO_CONDUCTED => Text::_('COM_CRMSTAGES_ACTION_DEMO_CONDUCTED'),
			Constants::ACTION_INVOICE_ISSUED => Text::_('COM_CRMSTAGES_ACTION_INVOICE_ISSUED'),
			Constants::ACTION_PAYMENT_RECEIVED => Text::_('COM_CRMSTAGES_ACTION_PAYMENT_RECEIVED'),
			Constants::ACTION_ID_CARD_ISSUED => Text::_('COM_CRMSTAGES_ACTION_ID_CARD_ISSUED')
		];

		return $names[$actionCode] ?? $actionCode;
	}

	/**
	 * Perform the stage transition
	 *
	 * @param int $companyId
	 * @param string $targetStageCode
	 *
	 * @return bool
	 */
	public function performTransition(int $companyId, string $targetStageCode): bool
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
			->order('a.id DESC')
			->bind(':id', $targetStageId);

		$db->setQuery($actionQuery);

		$targetActionId = $db->loadResult();

		if (!$targetActionId) {
			$targetActionId = 0;
		}

		$db->transactionStart();

		try {
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

			$updateQuery = $db->getQuery(true)
				->update($db->quoteName('#__crm_companies'))
				->set($db->quoteName('stage_id') . ' = ' . (int)$targetStageId)
				->where($db->quoteName('id') . ' = ' . (int)$companyId);

			$db->setQuery($updateQuery);
			$db->execute();

			$db->transactionCommit();

			$this->app->enqueueMessage(
				Text::sprintf('COM_CRMSTAGES_TRANSITION_SUCCESS', $targetStageId),
				'message',
			);

			return true;

		} catch (Exception $e) {

			$db->transactionRollback();
			$this->app->enqueueMessage(
				Text::_('COM_CRMSTAGES_ERROR_TRANSITION_FAILED') . ': ' . $e->getMessage(),
				'error',
			);
			return false;
		}
	}
}
