<?php

namespace Joomla\Component\Crmstages\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\TableInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Action model class.
 *
 * @since  1.0
 */
class ActionModel extends AdminModel
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $text_prefix = 'COM_CRMSTAGES_ACTION';

	/**
	 * The type alias for this content type.
	 *
	 * @var    string
	 * @since  1.0
	 */
	public $typeAlias = 'com_crmstages.action';

	/**
	 * Allowed batch commands.
	 *
	 * @var  array
	 * @since 1.0
	 */
	protected $batch_commands = [
		'ordering' => 'batchReorder',
	];

	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param string $name The table name. Optional.
	 * @param string $prefix The class prefix. Optional.
	 * @param array $options Configuration array for model. Optional.
	 *
	 * @return  TableInterface  A Table object
	 *
	 * @since   1.0
	 */
	public function getTable($name = '', $prefix = '', $options = [])
	{
		$name = $name ?: 'Action';
		$prefix = $prefix ?: 'CrmstagesTable';

		return Factory::getContainer()->get($prefix . $name);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param array $data Data for the form. [optional]
	 * @param boolean $loadData True if the form is to load its own data (default case), false if not. [optional]
	 *
	 * @return  Form|boolean  A Form object on success, false on failure
	 *
	 * @since   1.0
	 */
	public function getForm($data = [], $loadData = true)
	{
		$form = $this->loadForm(
			'com_crmstages.action',
			'action',
			['control' => 'jform', 'load_data' => $loadData]
		);

		if (empty($form)) {
			return false;
		}

		// Disable ordering field if user can't edit state
		if (!$this->canEditState((object)$data)) {
			$form->setFieldAttribute('ordering', 'disabled', 'true');
			$form->setFieldAttribute('ordering', 'filter', 'unset');
		}

		return $form;
	}

	/**
	 * Method to test whether a record can be deleted.
	 *
	 * @param object $record A record object.
	 *
	 * @return  boolean  True if allowed to delete the record.
	 *
	 * @since   1.0
	 */
	protected function canDelete($record)
	{
		if (!empty($record->id)) {
			return $this->getCurrentUser()->authorise('core.delete', 'com_crmstages.action.' . (int)$record->id);
		}
		return false;
	}

	/**
	 * Method to test whether a record can have its state changed.
	 *
	 * @param object $record A record object.
	 *
	 * @return  boolean  True if allowed to change the state of the record.
	 *
	 * @since   1.0
	 */
	protected function canEditState($record)
	{
		return $this->getCurrentUser()->authorise(
			'core.edit.state',
			'com_crmstages.action.' . (int)$record->id
		);
	}

	/**
	 * Prepare and sanitise the table prior to saving.
	 *
	 * @param TableInterface $table A Table object.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function prepareTable($table)
	{
		$date = Factory::getDate();
		$user = $this->getCurrentUser();

		if (empty($table->id)) {
			// Set created date
			$table->created = $date->toSql();
			$table->created_by = $user->id;

			// Default ordering
			if (empty($table->ordering)) {
				$db = $this->getDatabase();
				$query = $db->createQuery()
					->select('MAX(' . $db->quoteName('ordering') . ')')
					->from($db->quoteName('#__crm_actions'));
				$db->setQuery($query);
				$max = $db->loadResult();
				$table->ordering = $max ? $max + 1 : 1;
			}
		} else {
			// Update modified date
			$table->modified = $date->toSql();
			$table->modified_by = $user->id;
		}
	}

	/**
	 * Loads form data for editing.
	 *
	 * @return  mixed  The data for the form
	 *
	 * @since   1.0
	 */
	protected function loadFormData()
	{
		$app = Factory::getApplication();
		$data = $app->getUserState('com_crmstages.edit.action.data', []);

		if (empty($data)) {
			$data = $this->getItem();
		}

		$this->preprocessData('com_crmstages.action', $data);
		return $data;
	}
}
