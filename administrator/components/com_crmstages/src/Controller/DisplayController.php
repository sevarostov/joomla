<?php


namespace Joomla\Component\Crmstages\Administrator\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactory;
use Joomla\CMS\Response\JsonResponse;
use Joomla\Component\Crmstages\Administrator\Helper\StageHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Companycard display controller.
 *
 */
class DisplayController extends BaseController
{
	private DatabaseInterface $db;
	protected $app;


	public function __construct($config = [])
	{
		parent::__construct($config);
		$this->db = Factory::getContainer()->get(DatabaseInterface::class);
		$this->app = Factory::getApplication();
		$this->factory = new MVCFactory('\\Joomla\\Component\\Crmstages');
	}

	/**
	 * The default view.
	 *
	 * @var    string
	 * @since  1.6
	 */
	protected $default_view = 'companycard';

	/**
	 * Method to display a view.
	 *
	 * @param boolean $cachable If true, the view output will be cached
	 * @param array $urlparams An array of safe URL parameters and their variable types
	 *
	 * @return  DisplayController|JsonResponse  This object to support chaining.
	 *
	 * @see        \Joomla\CMS\Filter\InputFilter::clean() for valid values.
	 *
	 * @since   1.5
	 */
	public function display($cachable = false, $urlparams = [])
	{
		$input = $this->app->getInput();
		$companyId = $input->getInt('id', 0);

		if (!$companyId) {
			return new JsonResponse(400, 'Company ID is required');
		}

		try {
			$data = $this->loadCompanyCardData($companyId);

			if (!$data) {
				return new JsonResponse(404, 'Company not found');
			}

			$view = $this->getView('CompanyCard', 'html');
			$view->set('data', $data);
			$view->display();

		} catch (\Exception $e) {
			return new JsonResponse(500, 'An error occured');
		}

	}

	/**
	 * Load all data for company card
	 *
	 * @param int $companyId
	 *
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
			$currentStage['code'],
		);

		// 3. Get manager instructions
		$instructions = StageHelper::getInstructions(
			$currentStage['code'],
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
	 *
	 * @return array|null
	 */
	private function getCurrentStage(int $companyId): ?array
	{
		$query = $this->db->getQuery(true)
			->select([
				's.id',
				's.code',
				's.name'
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
			->bind(':companyid', $companyId, ParameterType::INTEGER);

		$this->db->setQuery($query);
		return (array)$this->db->loadObjectList();
	}
}
