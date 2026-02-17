<?php


namespace Joomla\Component\Crmstages\Administrator\Controller;

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactory;
use Joomla\CMS\Response\JsonResponse;
use Joomla\Component\Crmstages\Administrator\Model\CompanyModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Companycard display controller.
 *
 */
class DisplayController extends BaseController
{
	protected $app;


	public function __construct($config = [])
	{
		parent::__construct($config);
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
			$data = (new CompanyModel([], $this->factory))->loadCompanyCardData($companyId);

			if (!$data) {
				return new JsonResponse(404, 'Company not found');
			}

			$view = $this->getView('CompanyCard', 'html');
			$view->set('data', $data);
			$view->display();

		} catch (Exception $exception) {
			return new JsonResponse(500, 'An error occured');
		}
	}
}
