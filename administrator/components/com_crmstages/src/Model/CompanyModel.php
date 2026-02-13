<?php

namespace Joomla\Component\Crmstages\Model;


use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\CMS\Table\TableInterface;
use Joomla\Database\ParameterType;


// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects


class CompanyModel extends AdminModel
{
	protected $text_prefix = 'COM_CRMSTAGES_COMPANY';
	public $typeAlias = 'com_crmstages.company';
	public function getItem($pk = null)
	{
		$pk = is_null($pk) ? $this->getState('company.id') : $pk;

		if ($this->_item === null)
		{
			$this->_item = [];
		}

		if (!isset($this->_item[$pk]))
		{
			try
			{
				$db = $this->getDatabase();
				$query = $db->getQuery(true);

				$query->select('*')
					->from($db->quoteName('#__crm_companies'))
					->where($db->quoteName('id') . ' = :id')
					->bind(':id', $pk, ParameterType::INTEGER);

				$db->setQuery($query);
				$item = $db->loadObject();

				if (empty($item))
				{
					throw new \Exception(Text::_('COM_CRMSTAGES_ERROR_ITEM_NOT_FOUND'));
				}

				// Load current stage
				$stageQuery = $db->getQuery(true)
					->select('s.code, s.name')
					->from('#__crm_stages AS s')
					->join('INNER', '#__crm_action_log AS l', 'l.stage_id = s.id')
					->where('l.company_id = :companyid')
					->order('l.created DESC')
					->setLimit(1)
					->bind(':companyid', $item->id, ParameterType::INTEGER);

				$db->setQuery($stageQuery);
				$stage = $db->loadObject();

				$item->stage = $stage;

				// Load action history
				$historyQuery = $db->getQuery(true)
					->select('l.created, s.code, s.name, a.name AS action')
					->from('#__crm_action_log AS l')
					->join('INNER', '#__crm_stages AS s', 's.id = l.stage_id')
					->join('LEFT', '#__crm_actions AS a', 'a.id = l.action_id')
					->where('l.company_id = :companyid')
					->order('l.created DESC')
					->bind(':companyid', $item->id, ParameterType::INTEGER);

				$db->setQuery($historyQuery);
				$item->history = $db->loadObjectList();

				$this->_item[$pk] = $item;
			}
			catch (\Exception $e)
			{
				$this->setError($e->getMessage());
				return false;
			}
		}

		return $this->_item[$pk];
	}

	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $name     The table name. Optional.
	 * @param   string  $prefix   The class prefix. Optional.
	 * @param   array   $options  Configuration array for model. Optional.
	 *
	 * @return  TableInterface  A Table object
	 */
	public function getTable($name = '', $prefix = '', $options = [])
	{
		$name = $name ?: 'Company';
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
	 */
	public function getForm($data = [], $loadData = true)
	{
		$form = $this->loadForm(
			'com_crmstages.company',
			'company',
			['control' => 'jform', 'load_data' => $loadData]
		);

		if (empty($form)) {
			return false;
		}

		// Disable fields if user can't edit state
		if (!$this->canEditState((object)$data)) {
			$form->setFieldAttribute('stage_id', 'disabled', 'true');
			$form->setFieldAttribute('stage_id', 'filter', 'unset');
		}

		return $form;
	}

	/**
	 * Method to test whether a record can be deleted.
	 *
	 * @param   object  $record  A record object.
	 * @return  boolean  True if allowed to delete the record.
	 */
	protected function canDelete($record)
	{
		if (!empty($record->id)) {
			return $this->getCurrentUser()->authorise('core.delete', 'com_crmstages.company.' . (int)$record->id);
		}
		return false;
	}

	/**
	 * Method to test whether a record can have its state changed.
	 *
	 * @param   object  $record  A record object.
	 * @return  boolean  True if allowed to change the state of the record.
	 */
	protected function canEditState($record)
	{
		return $this->getCurrentUser()->authorise('core.edit.state', 'com_crmstages.company.' . (int)$record->id);
	}

	/**
	 * Loads form data for editing
	 *
	 * @return  mixed  The data for the form
	 */
	protected function loadFormData()
	{
		$app = Factory::getApplication();
		$data = $app->getUserState('com_crmstages.edit.company.data', []);

		if (empty($data)) {
			$data = $this->getItem();
		}

		$this->preprocessData('com_crmstages.company', $data);
		return $data;
	}
}
