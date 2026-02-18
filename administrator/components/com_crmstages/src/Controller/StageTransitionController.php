<?php

namespace Joomla\Component\Crmstages\Administrator\Controller;

use AllowDynamicProperties;
use Exception;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Component\Crmstages\Administrator\Service\StageTransitionService;
use Joomla\Database\DatabaseInterface;

defined('_JEXEC') or die;

#[AllowDynamicProperties] class StageTransitionController extends BaseController
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

		if (!Session::checkToken('get')) {
			return new JsonResponse(403, 'Invalid token');
		}

		if (!$companyId) {
			return new JsonResponse(400, 'Invalid request: missing company_id');
		}

		try {
			$result = (new StageTransitionService($this->app, $this->db))->transition($companyId);
dd('$result2',$result);
			if (!$result) {
				return new JsonResponse(400, 'Bad Request');
			}

			$this->setRedirect(Route::_('index.php?option=com_crmstages&view=companycard&id=' . $companyId, false));

		} catch (Exception $e) {
			return new JsonResponse(500, 'An error occured');
		}
	}
}
