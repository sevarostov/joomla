<?php

namespace Joomla\Component\Crmstages\Administrator\Model;

use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\Component\Crmstages\Administrator\Helper\StageHelper;
use Joomla\Database\ParameterType;


// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects


class CompanyModel extends ItemModel
{
	/**
	 * Load all data for company card
	 *
	 * @param int $companyId
	 *
	 * @return array|null
	 */
	public function loadCompanyCardData(int $companyId): ?array
	{
		$currentStage = $this->getCurrentStage($companyId);
		if (!$currentStage) {
			return null;
		}

		$actions = StageHelper::getAvailableActions(
			$currentStage['code'],
		);

		$instructions = StageHelper::getInstructions(
			$currentStage['code'],
		);

		$logs = $this->getEventHistory($companyId);

		$lastStage = $this->getLastStage($currentStage);

		return [
			'company_id' => $companyId,
			'current_stage' => $currentStage,
			'actions' => $actions,
			'instructions' => $instructions,
			'logs' => $logs,
			'last_stage' => $lastStage,
		];
	}

	/**
	 * Get current stage of company
	 *
	 * @param int $companyId
	 *
	 * @return array|null
	 */
	private function getCurrentStage(int $companyId): ?array
	{
		$this->db = $this->getDatabase();
		$query = $this->db->getQuery(true)
			->select([
				's.id',
				's.code',
				's.name',
				's.ordering'
			])
			->from($this->db->quoteName('#__crm_companies', 'companies'))
			->join(
				'INNER',
				$this->db->quoteName('#__crm_stages', 's'),
				's.id = companies.stage_id',
			)
			->where([
				$this->db->quoteName('companies.id') . ' = :companyid',
			])
			->bind(':companyid', $companyId, ParameterType::INTEGER);
		$this->db->setQuery($query);
		$result = $this->db->loadObject();

		return $result ? (array)$result : null;
	}

	/**
	 * Get event history for company
	 *
	 * @param int $companyId
	 *
	 * @return array
	 */
	private function getEventHistory(int $companyId): array
	{
		$this->db = $this->getDatabase();
		$query = $this->db->getQuery(true)
			->select([
				'l.created',
				's.name AS stage_name',
			])
			->from($this->db->quoteName('#__crm_action_log', 'l'))
			->join(
				'LEFT',
				$this->db->quoteName('#__crm_stages', 's'),
				's.id = l.stage_id',
			)
			->where($this->db->quoteName('l.company_id') . ' = :companyid')
			->order($this->db->quoteName('l.created') . ' DESC')
			->setLimit(10)
			->bind(':companyid', $companyId, ParameterType::INTEGER);

		$this->db->setQuery($query);
		return (array)$this->db->loadObjectList();
	}

	/**
	 * If the stage is last
	 *
	 * @param array $currentStage
	 *
	 * @return bool
	 */
	private function getLastStage(array $currentStage): bool
	{
		$this->db = $this->getDatabase();
		$query = $this->db->getQuery(true)
			->select('COUNT(*)')
			->from($this->db->quoteName('#__crm_stages', 's'))
			->where([
				$this->db->quoteName('s.active') . ' = true',
				$this->db->quoteName('s.ordering') . ' > :ordering',
				$this->db->quoteName('s.code') . ' != "N0"',
			])
			->bind(':ordering', $currentStage['ordering'], ParameterType::INTEGER);
		$this->db->setQuery($query);

		return !!($this->db->loadResult()) == 0;
	}

	public function getItem($pk = null)
	{
		// TODO: Implement getItem() method.
	}
}
