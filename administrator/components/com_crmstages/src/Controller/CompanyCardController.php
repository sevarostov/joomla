<?php
namespace Joomla\Component\Crmstages\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\Component\Crmstages\Helper\StageHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;


defined('_JEXEC') or die;

class CompanyCardController extends BaseController
{
	private DatabaseInterface $db;
	protected $app;


	public function __construct($config = [])
	{
		parent::__construct($config);
		$this->db  = Factory::getContainer()->get(DatabaseInterface::class);
		$this->app = Factory::getApplication();
	}

	/**
	 * Display company card with all components
	 *
	 * @param false $cachable
	 * @param array $urlparams*/
	public function display($cachable = false, $urlparams = [])
	{
		$input = $this->app->getInput();
		$companyId = $input->getInt('id', 0);

		if (!$companyId) {
			return new JsonResponse(400, 'Company ID is required');
		}

		try {
			// Load all required data
			$data = $this->loadCompanyCardData($companyId);

			if (!$data) {
				return new JsonResponse(404, 'Company not found');
			}

			// Pass data to view
			$view = $this->getView('CompanyCard', 'html');
			$view->set('data', $data);
			$view->display();

		} catch (\Exception $e) {
			return new JsonResponse(500, 'An error occured');
		}

		return new JsonResponse(500, 'An error occured');
	}

	/**
	 * Load all data for company card
	 *
	 * @param int $companyId
	 * @return array|null
	 */
	private function loadCompanyCardData(int $companyId): ?array
	{
		// 1. Get current stage
		$currentStage = $this->getCurrentStage($companyId);
		if (!$currentStage) {
			return null;
		}

		// 2. Get available actions
		$actions = StageHelper::getAvailableActions(
			$currentStage['code']
		);

		// 3. Get manager instructions
		$instructions = StageHelper::getInstructions(
			$currentStage['code']
		);

		// 4. Get event history
		$logs = $this->getEventHistory($companyId);

		return [
			'company_id' => $companyId,
			'current_stage' => $currentStage,
			'actions' => $actions,
			'instructions' => $instructions,
			'logs' => $logs
		];
	}

	/**
	 * Get current stage of company
	 *
	 * @param int $companyId
	 * @return array|null
	 */
	private function getCurrentStage(int $companyId): ?array
	{
		$query = $this->db->getQuery(true)
			->select([
				's.id',
				's.code',
				's.name',
				'l.created'
			])
			->from($this->db->quoteName('#__crm_companies', 's'))
			->join(
				'INNER',
				$this->db->quoteName('#__crm_action_log', 'l'),
				'l.company_id = c.id'
			)
			->join(
				'INNER',
				$this->db->quoteName('#__crm_stages', 's'),
				's.id = l.stage_id'
			)
			->join('INNER',
				$this->db->quoteName('#__crm_companies', 'c'), 'c.id = l.company_id')
			->where([
				$this->db->quoteName('c.id') . ' = :companyid',
				$this->db->quoteName('l.id') . ' = (' .
				$this->db->getQuery(true)
					->select('MAX(id)')
					->from($this->db->quoteName('#__crm_action_log'))
					->where($this->db->quoteName('company_id') . ' = c.id')
				. ')'
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
	 * @return array
	 */
	private function getEventHistory(int $companyId): array
	{
		$query = $this->db->getQuery(true)
			->select([
				'l.created',
				's.name AS stage_name',
			])
			->from($this->db->quoteName('#__crm_action_log', 'l'))
			->join(
				'LEFT',
				$this->db->quoteName('#__crm_stages', 's'),
				's.id = l.stage_id'
			)
			->where($this->db->quoteName('l.company_id') . ' = :companyid')
			->order($this->db->quoteName('l.created') . ' DESC')
			->bind(':companyid', $companyId, ParameterType::INTEGER);

		$this->db->setQuery($query);
		return (array)$this->db->loadObjectList();
	}
}
