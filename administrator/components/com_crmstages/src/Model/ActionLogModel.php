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
 * ActionLog model class.
 *
 * @since  1.0
 */
class ActionLogModel extends AdminModel
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $text_prefix = 'COM_CRMSTAGES_ACTIONLOG';

	/**
	 * The type alias for this content type.
	 *
	 * @var    string
	 * @since  1.0
	 */
	public $typeAlias = 'com_crmstages.actionlog';

	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $name     The table name. Optional.
	 * @param   string  $prefix   The class prefix. Optional.
	 * @param   array   $options  Configuration array for model. Optional.
	 *
	 * @return  TableInterface  A Table object
	 *
	 * @since   1.0
	 */
	public function getTable($name = '', $prefix = '', $options = [])
	{
		$name = $name ?: 'ActionLog';
		$prefix = $prefix ?: 'CrmstagesTable';

		return Factory::getContainer()->get($prefix . $name);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      Data for the form. [optional]
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not. [optional]
	 *
	 * @return  Form|boolean  A Form object on success, false on failure
	 *
	 * @since   1.0
	 */
	public function getForm($data = [], $loadData = true)
	{
		$form = $this->loadForm(
			'com_crmstages.actionlog',
			'actionlog',
			['control' => 'jform', 'load_data' => $loadData]
		);

		if (empty($form)) {
			return false;
		}

		return $form;
	}

	/**
	 * Method to test whether a record can be deleted.
	 *
	 * @param   object  $record  A record object.
	 *
	 * @return  boolean  True if allowed to delete the record.
	 *
	 * @since   1.0
	 */
	protected function canDelete($record)
	{
		// Optionally restrict deletion (e.g., only for admins)
		return $this->getCurrentUser()->authorise(
			'core.delete',
			'com_crmstages.actionlog.' . (int)$record->company_id
		);
	}

	/**
	 * Method to test whether a record can have its state changed.
	 * Since this is a log, state changes are typically not applicable.
	 *
	 * @param   object  $record  A record object.
	 *
	 * @return  boolean  True if allowed to change the state of the record.
	 *
	 * @since   1.0
	 */
	protected function canEditState($record)
	{
		return false; // Log entries are immutable
	}

	/**
	 * Prepare and sanitise the table prior to saving.
	 * Automatically sets the `created` timestamp if not provided.
	 *
	 * @param   TableInterface  $table  A Table object.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function prepareTable($table)
	{
		$date = Factory::getDate();

		// Set created timestamp if not already set
		if (!isset($table->created) || empty($table->created)) {
			$table->created = $date->toSql();
		}
	}

	/**
	 * Loads form data for editing.
	 * For log entries, this is typically read-only.
	 *
	 * @return  mixed  The data for the form
	 *
	 * @since   1.0
	 */
	protected function loadFormData()
	{
		$app = Factory::getApplication();
		$data = $app->getUserState('com_crmstages.edit.actionlog.data', []);

		if (empty($data)) {
			$data = $this->getItem();
		}

		$this->preprocessData('com_crmstages.actionlog', $data);
		return $data;
	}

	/**
	 * Override save method to prevent updates to primary key fields.
	 * Log entries should not be modified after creation.
	 *
	 * @param   array  $data  The data to save.
	 *
	 * @return  boolean       True on success, false otherwise.
	 *
	 * @since   1.0
	 */
	public function save($data)
	{
		// Prevent modification of company_id or stage_id (primary key)
		if (isset($data['company_id']) && isset($data['stage_id']) && isset($data['action_id'])) {
			$table = $this->getTable();
			if ($table->load([
				'company_id' => $data['company_id'],
				'stage_id' => $data['stage_id'],
				'action_id' => $data['action_id']
			])) {
				$this->setError(Text::_('COM_CRMSTAGES_ERROR_ACTIONLOG_CANNOT_MODIFY_KEY'));
				return false;
			}
		}

		return parent::save($data);
	}

	/**
	 * Override delete method with permission check.
	 *
	 * @param   mixed  $pks  An optional array of primary key values to delete.
	 *
	 * @return  boolean      True on success, false otherwise.
	 *
	 * @since   1.0
	 */
	public function delete(&$pks)
	{
		if (!is_array($pks)) {
			$pks = [$pks];
		}

		foreach ($pks as $pk) {
			if (!$this->canDelete((object)['company_id' => $pk])) {
				$this->setError(Text::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'));
				return false;
			}
		}

		return parent::delete($pks);
	}
}
