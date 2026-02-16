<?php

namespace Joomla\Component\Crmstages\Administrator\Repository;


use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

use stdClass;


class StageRepository
{
	private DatabaseInterface $db;

	public function __construct(DatabaseInterface $db)
	{
		$this->db = $db;
	}

	/**
	 * Get current stage of a company
	 */
	public function getCurrentStage(int $companyId): ?object
	{
		$query = $this->db->getQuery(true)
			->select([
				's.id', 's.code', 's.name', 's.ordering',
				'c.id AS company_id'
			])
			->from($this->db->quoteName('#__crm_companies', 'c'))
			->join('INNER', $this->db->quoteName('#__crm_stages', 's'), 's.id = c.stage_id')
			->where($this->db->quoteName('c.id') . ' = :companyid')
			->bind(':companyid', $companyId, ParameterType::INTEGER);


		$this->db->setQuery($query);
		return $this->db->loadObject();
	}

	/**
	 * Get stage by code
	 */
	public function getStageByCode(string $code): ?object
	{
		$query = $this->db->getQuery(true)
			->select('id, code, name, ordering')
			->from($this->db->quoteName('#__crm_stages'))
			->where($this->db->quoteName('code') . ' = :code')
			->bind(':code', $code, ParameterType::STRING);


		$this->db->setQuery($query);
		return $this->db->loadObject();
	}

	/**
	 * Get next stage by ordering
	 */
	public function getNextStage(int $currentOrdering): ?object
	{
		$query = $this->db->getQuery(true)
			->select('id, code, name, ordering')
			->from($this->db->quoteName('#__crm_stages'))
			->where([
				$this->db->quoteName('ordering') . ' > :ordering',
				$this->db->quoteName('active') . ' = 1'
			])
			->order($this->db->quoteName('ordering'))
			->setLimit(1)
			->bind(':ordering', $currentOrdering, ParameterType::INTEGER);


		$this->db->setQuery($query);
		return $this->db->loadObject();
	}

	/**
	 * Check if action occurred for a company
	 */
	public function hasActionOccurred(int $companyId, string $actionCode): bool
	{
		$query = $this->db->getQuery(true)
			->select('COUNT(*)')
			->from($this->db->quoteName('#__crm_action_log', 'l'))
			->join(
				'INNER',
				$this->db->quoteName('#__crm_actions', 'a'),
				'a.id = l.action_id',
			)
			->where([
				$this->db->quoteName('l.company_id') . ' = :companyid',
				$this->db->quoteName('a.code') . ' = :actioncode'
			])
			->bind(':companyid', $companyId, ParameterType::INTEGER)
			->bind(':actioncode', $actionCode, ParameterType::STRING);

		$this->db->setQuery($query);
		return (int)$this->db->loadResult() > 0;
	}
}
