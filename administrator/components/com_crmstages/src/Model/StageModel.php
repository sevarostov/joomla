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
 * Stage model class.
 *
 * @since  1.0
 */
class StageModel extends AdminModel
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $text_prefix = 'COM_CRMSTAGES_STAGE';

	/**
	 * The type alias for this content type.
	 *
	 * @var    string
	 * @since  1.0
	 */
	public $typeAlias = 'com_crmstages.stage';

	/**
	 * Allowed batch commands.
	 *
	 * @var  array
	 * @since 1.0
	 */
	protected $batch_commands = [
		'active' => 'batchActivate',
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
		$name = $name ?: 'Stage';
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
		// Get the form.
		$form = $this->loadForm(
			'com_crmstages.stage',
			'stage',
			['control' => 'jform', 'load_data' => $loadData],
		);

		if (empty($form)) {
			return false;
		}

		// Modify form based on access controls
		if (!$this->canEditState((object)$data)) {
			$form->setFieldAttribute('active', 'disabled', 'true');
			$form->setFieldAttribute('ordering', 'disabled', 'true');

			// Prevent filtering on save
			$form->setFieldAttribute('active', 'filter', 'unset');
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
			return $this->getCurrentUser()->authorise('core.delete', 'com_crmstages.stage.' . (int)$record->id);
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
			'com_crmstages.stage.' . (int)$record->id,
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

		// Set created date if new
		if (empty($table->id)) {
			$table->created = $date->toSql();
			$table->created_by = $user->id;

			// Set default ordering if not provided
			if (empty($table->ordering)) {
				$db = $this->getDatabase();
				$query = $db->createQuery()
					->select('MAX(' . $db->quoteName('ordering') . ')')
					->from($db->quoteName('#__crm_stages'));
				$db->setQuery($query);
				$max = $db->loadResult();
				$table->ordering = $max ? $max + 1 : 1;
			}
		} else {
			// Update modified date
			$table->modified = $date->toSql();
			$table->modified_by = $user->id;
		}

		$table->code = trim($table->code);
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
		// Check session for previously entered form data
		$app = Factory::getApplication();
		$data = $app->getUserState('com_crmstages.edit.stage.data', []);

		if (empty($data)) {
			$data = $this->getItem();
		}

		$this->preprocessData('com_crmstages.stage', $data);
		return $data;
	}

	/**
	 * A protected method to get a set of ordering conditions.
	 *
	 * @param TableInterface $table A record object.
	 *
	 * @return  array  An array of conditions to add to ordering queries.
	 *
	 * @since   1.0
	 */
	protected function getReorderConditions($table)
	{
		$db = $this->getDatabase();
		return [
			$db->quoteName('active') . ' = ' . (int)$table->active,
		];
	}

	/**
	 * Batch activate action.
	 *
	 * @param integer $value The value of the activation state (1 = active, 0 = inactive).
	 * @param array $pks An array of primary key IDs to update.
	 * @param array $contexts An array mapping primary keys to item contexts.
	 *
	 * @return  boolean             True on success, false on failure.
	 *
	 * @since   1.0
	 */
	protected function batchActivate($value, $pks, $contexts)
	{
		/** @var TableInterface $table */
		$table = $this->getTable();
		$value = (int)$value;

		// Validate the activation value (should be 0 or 1)
		if (!in_array($value, [0, 1])) {
			$this->setError(Text::_('COM_CRMSTAGES_ERROR_INVALID_ACTIVATION_STATE'));
			return false;
		}

		// Start database transaction
		$db = $this->getDatabase();
		$db->transactionStart();

		try {
			foreach ($pks as $pk) {
				// Check if user is authorised to edit the state of this item
				if (!$this->getCurrentUser()->authorise('core.edit.state', $contexts[$pk])) {
					$this->setError(Text::_('JLIB_APPLICATION_ERROR_BATCH_CANNOT_EDIT'));
					$db->transactionRollback();
					return false;
				}

				// Reset table and load the record
				$table->reset();
				if (!$table->load($pk)) {
					$this->setError(Text::sprintf('JLIB_APPLICATION_ERROR_RECORD_LOAD', $pk));
					$db->transactionRollback();
					return false;
				}

				// Set the new active state
				$table->active = $value;

				// Store the updated record
				if (!$table->store()) {
					$this->setError($table->getError());
					$db->transactionRollback();
					return false;
				}
			}

			// Commit the transaction if all records were updated successfully
			$db->transactionCommit();

			return true;
		} catch (\Exception $e) {
			// Rollback on any unexpected exception
			$db->transactionRollback();
			$this->setError($e->getMessage());
			return false;
		}
	}
}
