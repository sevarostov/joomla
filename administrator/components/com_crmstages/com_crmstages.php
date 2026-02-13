<?php

namespace Joomla\Component\Crmstages;

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;

/**
 * Entry point for the com_crmstages component
 */
class ComCrmstagesComponent
{
	/**
	 * Entry method to run the component
	 *
	 * @param string $name     Name of the component
	 * @param   CMSApplication    $app      The application object
	 * @param   MVCFactoryInterface $factory The MVC factory
	 *
	 * @return  void
	 */
	public function execute(string $name, CMSApplication $app, MVCFactoryInterface $factory): void
	{
		// Ensure user is authenticated
		if (!$app->getIdentity()->guest) {
			// Optional: Enforce component access (if ACL is implemented)
			if (!$app->getIdentity()->authorise('core.manage', 'com_crmstages')) {
				$app->enqueueMessage(
					Text::_('JERROR_ALERTNOAUTHOR'),
					'error'
				);
				$app->redirect('index.php');
				return;
			}
		}

		// Set component context
		$app->input->set('option', 'com_crmstages');

		try {
			$app->getDispatcher()->dispatch($name);
		} catch (\Exception $e) {
			// Handle exceptions
			Factory::getApplication()->enqueueMessage(
				Text::sprintf('COM_CRMSTAGES_ERROR_UNEXPECTED', $e->getMessage()),
				'error'
			);
		}
	}
}
