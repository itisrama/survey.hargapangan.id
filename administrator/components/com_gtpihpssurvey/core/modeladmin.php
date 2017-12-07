<?php

/**
 * @package		GT Component
 * @author		Yudhistira Ramadhan
 * @link		http://gt.web.id
 * @license		GNU/GPL
 * @copyright	Copyright (C) 2012 GtWeb Gamatechno. All Rights Reserved.
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.modeladmin');

class GTModelAdmin extends JModelAdmin
{
	
	public $app;
	public $input;
	public $context;
	public $prevName;
	public $item;
	public $user;
	public $menu;
	
	public function __construct($config = array()) {
		parent::__construct($config);
		
		// Set variables
		$this->app		= JFactory::getApplication();
		$this->input	= $this->app->input;
		$this->user		= JFactory::getUser();
		$this->menu		= $this->app->getMenu()->getActive();

		// Set User Profile
		$userProfiles	= JUserHelper::getProfile($this->user->id);
		foreach ($userProfiles as &$userProfile) {
			if(is_array($userProfile)) {
				$userProfile = JArrayHelper::toObject($userProfile, 'stdClass', false);
			}
		}
		$this->user->profile = $userProfiles;

		// Adjust the context to support modal layouts.
		$layout = $this->input->get('layout', 'default');
		$this->context	= implode('.', array($this->option, $this->getName(), $layout));

		// Add table path
		$this->addTablePath(GT_TABLES);
	}
	
	protected function populateState() {
		parent::populateState();
	}

	public function getItemExternal($pk, $name, $is_table = true) {		
		if($is_table) {
			$table = $this->getTable($name);
			$table = $table->getTableName();
		} else {
			$table = GTHelper::pluralize($name);
			$table = '#__gtpihpssurvey_'.$table;
		}

		// Get a db connection.
		$db = $this->_db;

		// Create a new query object.
		$query = $db->getQuery(true);

		$query->select('a.*');		
		$query->from($db->quoteName($table, 'a'));
		$query->where($db->quoteName('a.id') . ' = ' . $db->quote($pk));

		$db->setQuery($query);

		if($this->input->get('debugdb')) {
			echo '<strong>getItemExternal</strong></br>=================================================';
			echo nl2br(str_replace('#__','pihpsnas_',$query)).'<br/><br/>';
		}
		
		$result	= $db->loadObject();

		if(!@$result->id) {
			$fields = $db->getTableColumns($table, false);
			$fields = array_keys($fields);
			$result = new stdClass();
			foreach ($fields as $field) {
				$result->$field = null;
			}
		}
		return $result;
	}

	protected function loadFormData() {
		GTHelperFieldset::setData($this->data);
		return $this->data;
	}

	protected function getFormData() {
		$layout        = $this->app->getUserStateFromRequest($this->getName() . '.layout', 'layout');
		$context	= implode('.', array($this->option, $layout, $this->getName()));
		
		$data	= JFactory::getApplication()->getUserState($context . '.data', array());
		$data	= empty($data) ? $this->item : JArrayHelper::toObject($data);

		return $data;
	}
	
	public function getForm($data = array(), $loadData = true, $control = 'jform') {
		$component_name = $this->input->get('option');
		$model_name = $this->getName();
		
		$data = $data ? $data : $this->getFormData();
		$this->data = $data;

		// Get the form.
		$form = $this->loadForm($component_name . '.' . $model_name, $model_name, array('control' => $control, 'load_data' => $loadData));
		if (empty($form)) {
			return false;
		}
		
		return $form;
	}

	public function searchItem($table = null, $params = array(), $return_id = false) {
		$table = $table ? $table : $this->getName();
		$table = GTHelper::pluralize($table);
		$table = '#__gtpihpssurvey_'.$table;

		// Get a db connection.
		$db = $this->_db;

		// Create a new query object.
		$query = $db->getQuery(true);

		if($return_id) {
			$query->select($db->quoteName('a.id'));
		} else {
			$query->select('a.*');
		}
		
		$query->from($db->quoteName($table, 'a'));

		foreach ($params as $pfield => $pvalue) {
			$query->where($db->quoteName('a.'.$pfield) . ' = ' . $db->quote($pvalue));
		}

		$db->setQuery($query);

		if($this->input->get('debugdb')) {
			echo '<strong>searchItem</strong></br>=================================================';
			echo nl2br(str_replace('#__','pihpsnas_',$query)).'<br/><br/>';
		}
		
		if(count($params) > 0) {
			if($return_id) {
				return intval($db->loadResult());
			} else {
				$result = $db->loadObject();
				return $result ? $result : new stdClass();
			}
		} else {
			return false;
		}
	}

	public function getFormExternal($name, $data = array(), $loadData = true, $control = 'jform') {
		$this->name	= $name;
		$return		= $this->getForm($data, $loadData, $control);
		$this->name	= $this->prevName;
		return $return;
	}

	public function save($data) {
		$data = is_object($data) ? JArrayHelper::fromObject($data) : $data;
		foreach ($data as $k => $dat) {
			$dat = is_string($dat) ? trim($dat) : $dat;
			$dat = is_array($dat) ? array_filter($dat) : $dat;
			if($dat === '' || $dat === '[]' || $dat == array()) {
				$data[$k] = null;
			}
		}
		return parent::save($data);
	}

	public function saveExternal($data, $name, $return_id = false) {
		$data	= is_object($data) ? JArrayHelper::fromObject($data) : $data;
		$table	= $this->getTable($name);
		$key	= $table->getKeyName();
		$pk		= intval(@$data[$key]);
		$isNew	= $pk > 0;

		foreach ($data as $k => $dat) {
			if(trim($dat) === '') {
				unset($data->$k);
			}
		}
		if (!$isNew) {
			$table->load($pk);
		}

		// Bind the data.
		if (!$table->bind($data)) {
			$this->setError($table->getError());
			return false;
		}

		// Check the data.
		if (!$table->check()) {
			$this->setError($table->getError());
			return false;
		}

		// Store the data.
		if (!$table->store()) {
			$this->setError($table->getError());
			return false;
		}

		if (isset($table->$key) && $return_id) {
			return $table->$key;
		} else {
			return true;
		}
	}
	
	public function saveReference($value, $type) {
		$table = GTHelper::pluralize($type);
		$id = $this->getReference($value, $table);
		if($id) {
			return $id;
		} else {
			$data		= new stdClass();
			$data->id	= 0;
			$data->name	= $value;
			return $this->saveExternal($data, $type, true);
		}
	}

	public function saveBulk($items, $table = null, $meta = true, $is_table = false) {
		$date		= JFactory::getDate()->toSql();
		$user_id	= JFactory::getUser()->get('id');
		$user_id	= $this->input->get('user_id', $user_id);

		if($is_table) {
			$table = $this->getTable($table);
			$table = $table->getTableName();
		} else {
			$table = $table ? $table : $this->getName();
			$table = GTHelper::pluralize($table);
			$table = '#__gtpihpssurvey_'.$table;
		}
		

		$items = is_object($items) ? JArrayHelper::fromObject($items) : $items;
		if(!count($items) > 0) {
			return true;
		}

		$db = JFactory::getDbo();
 
		$query = $db->getQuery(true);

		// Insert columns.
		$columns = reset($items);
		$columns = is_object($columns) ? JArrayHelper::fromObject($columns) : $columns;
		$columns = array_keys($columns);

		foreach ($items as &$item) {
			$item = is_object($item) ? JArrayHelper::fromObject($item) : $item;
			foreach ($item as &$val) {
				$val = $db->quote($val);
			}
			if($meta) {
				$item[]	= $db->quote($date);
				$item[]	= $db->quote($user_id);
			}
			$item	= implode(', ', $item);
		}

		// Prepare the insert query.
		$insert_cols = $meta ? array_merge($columns, array('created', 'created_by')) : $columns;
		$query->insert($db->quoteName($table));
		$query->columns($db->quoteName($insert_cols));
		$query->values($items);

		foreach ($columns as &$column) {
			$column = $db->quoteName($column).' = VALUES('.$db->quoteName($column).')';
		}
		if($meta) {
			$columns[]	= $db->quoteName('modified').' = '.$db->quote($date);
			$columns[]	= $db->quoteName('modified_by').' = '.$db->quote($user_id);
		}
		
		$columns	= implode(', ', $columns);

		$query = $query . ' ON DUPLICATE KEY UPDATE ' . $columns;

		//echo nl2br(str_replace('#__','pihpsnas_',$query));

		// Set the query using our newly populated query object and execute it.
		$db->setQuery($query);

		if($this->input->get('debugdb')) {
			echo '<strong>saveBulk</strong></br>=================================================';
			echo nl2br(str_replace('#__','pihpsnas_',$query)).'<br/><br/>';
		}

		return $db->execute();
	}

	public function getReference($value, $type) {
		$table = '#__gtpihpssurvey_' . $type;
		$db = $this->_db;
		$query = $db->getQuery(true);
		$query->select($db->quoteName('id'))->from($table);
		$query->where('(' . $db->quoteName('id') . ' = ' . $db->quote($value) 
			. ' OR LOWER(' . $db->quoteName('name') . ') = LOWER(' . $db->quote($value) . '))');

		$db->setQuery($query);

		return @$db->loadObject()->id;
	}

	public function deleteExternal(&$pks, $table, $is_table = true) {
		if(!count(array_filter($pks)) > 0) {
			return true;
		}

		if($is_table) {
			$table = $this->getTable($table);
			$table = $table->getTableName();
		} else {
			$table = $table ? $table : $this->getName();
			$table = GTHelper::pluralize($table);
			$table = '#__gtpihpssurvey_'.$table;
		}
		
		// Get a db connection.
		$db = $this->_db;
		
		// Create a new query object.
		$query = $db->getQuery(true);
		$query->delete($db->quoteName($table));

		$pks = array_map(array($db, 'quote'), $pks);
		$pks = implode(',', $pks);
		$query->where($db->quoteName('id').' IN ('.$pks.')');

		$db->setQuery($query)->execute();
		
		if($this->input->get('debugdb')) {
			echo '<strong>deleteExternal</strong></br>=================================================';
			echo nl2br(str_replace('#__','pihpsnas_',$query)).'<br/><br/>';
		}
		return true;
	}

	public function getLastID($table, $component_prefix = true) {
		$prefix = $component_prefix ? '#__gtpusiknas_' : '#__';
		$table = $table ? $table : $this->getName();
		$table = $prefix.$table;
		
		// Get a db connection.
		$db = $this->_db;
		
		// Create a new query object.
		$query = $db->getQuery(true);

		$query->select('MAX('.$db->quoteName('a.id').')');
		$query->from($db->quoteName($table, 'a'));

		$db->setQuery($query);
		return $db->loadResult();
	}
}
