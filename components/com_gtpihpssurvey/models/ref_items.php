<?php

/**
 * @package     GT Component
 * @author      Yudhistira Ramadhan
 * @link        http://gt.web.id
 * @license     GNU/GPL
 * @copyright   Copyright (C) 2012 GtWeb Gamatechno. All Rights Reserved.
 */
defined('_JEXEC') or die;

class GTPIHPSSurveyModelRef_Items extends GTModelList
{
	
	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  JDatabaseQuery
	 * @since   1.6
	 */
	
	public function __construct($config = array()) {
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = array('a.id', 'a.name', 'a.date');
		}
		
		parent::__construct($config);
	}
	
	protected function populateState($ordering = 'a.id', $direction = 'desc') {
		parent::populateState($ordering, $direction);

		// Adjust the context to support modal layouts.
		$layout = $this->input->get('layout', 'default');
		if ($layout) {
			$this->context.= '.'.$layout;
		}
		
		$start	= $this->getUserStateFromRequest($this->context.'.filter.start', 'start', 0);
		$length	= $this->getUserStateFromRequest($this->context.'.filter.limit', 'length', 10);
		$orders	= $this->getUserStateFromRequest($this->context.'.filter.orders', 'order', array(), 'array');
		$this->setState('list.start', $start);
		$this->setState('list.limit', $length);
		$this->setState('filter.orders', $orders);

		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);
		
		$published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '1');
		$this->setState('filter.published', $published);

		$profil		= @$this->user->profile->pihpssurvey;
		$filters	= array();

		if(@$profil->id_wil_dir1 == 1) {
			$profil->id_wil_dir1 = null;
		}

		foreach ($filters as $filter) {
			$profilVal = @$profil->$filter;
			$state = $this->getUserStateFromRequest($this->context.'.filter.'.$filter, $filter, $profilVal);
			$this->setState('filter.'.$filter, $profilVal ? $profilVal : $state);
		}
	}

	public function getFilterData() {
		$data = parent::getFilterData();

		$filters = array();
		foreach ($filters as $filter) {
			$data->$filter = $this->getState('filter.'.$filter);
		}
		return $data;
	}
	
	protected function getListQuery() {
		$profile = $this->user->profile->pihpssurvey;
		$jenis = $this->menu->params->get('jenis');
		switch($jenis) {
			default:
				$filters = array();
				break;
		}
		
		// Get a db connection.
		$db = $this->_db;
		
		// Create a new query object.
		$query = $db->getQuery(true);
		
		// Select item
		$query->select('a.*');
		$query->select('IF(DAY('.$db->quoteName('a.modified').'), '.$db->quoteName('a.modified').', '.$db->quoteName('a.created').') date');
		$query->from($db->quoteName('#__gtpihpssurvey_ref_'.GTHelper::pluralize(preg_replace('/\d/', '', $jenis)), 'a'));

		switch($jenis) {
			case 'seller':
				$query->select($db->quoteName('c.long_name', 'regency'));
				$query->join('INNER', $db->quoteName('#__gtpihpssurvey_ref_regencies', 'c').' ON'.
					$db->quoteName('a.regency_id').' = '.$db->quoteName('c.id')
				);
				$query->select($db->quoteName('d.name', 'province'));
				$query->join('INNER', $db->quoteName('#__gtpihpssurvey_ref_provinces', 'd').' ON'.
					$db->quoteName('a.province_id').' = '.$db->quoteName('d.id')
				);
				$query->select($db->quoteName('b.name', 'market'));
				$query->join('INNER', $db->quoteName('#__gtpihpssurvey_ref_markets', 'b').' ON'.
					$db->quoteName('a.market_id').' = '.$db->quoteName('b.id')
				);
				$query->where($db->quoteName('b.price_type_id').' = 1');
				break;
			case 'market':
			case 'market2':
			case 'market3':
				$query->select($db->quoteName('c.long_name', 'regency'));
				$query->join('INNER', $db->quoteName('#__gtpihpssurvey_ref_regencies', 'c').' ON'.
					$db->quoteName('a.regency_id').' = '.$db->quoteName('c.id')
				);
				$query->select($db->quoteName('d.name', 'province'));
				$query->join('INNER', $db->quoteName('#__gtpihpssurvey_ref_provinces', 'd').' ON'.
					$db->quoteName('a.province_id').' = '.$db->quoteName('d.id')
				);
				$price_type_id = str_replace('market', '', $jenis);
				$price_type_id = $price_type_id ? $price_type_id : '1';
				$query->where($db->quoteName('a.price_type_id').' = '.$db->quote($price_type_id));
				break;
		}
		
		// Publish filter
		$published = $this->getState('filter.published');
		if (is_numeric($published)) {
			$query->where('a.published = ' . (int)$published);
		} else {
			$query->where('a.published IN (0, 1)');
		}
		
		foreach ($filters as $filter) {
			$filterVal = $this->getState('filter.'.$filter);
			if(!$filterVal) continue;
			
			$query->where($db->quoteName('a.'.$filter).' = '.$db->quote($filterVal));		
		}

		if(@$profile->province_id) {
			$query->where($db->quoteName('a.province_id').' = '.$db->quote($profile->province_id));
		}

		$regency_ids = @$profile->regency_ids;
		if($regency_ids) {
			$query->where($db->quoteName('a.regency_id').' IN ('.$regency_ids.')');
		}
		
		$search = $this->getState('filter.search');
		if (!empty($search)) {
			
			// If contains spaces, the words will be used as keywords.
			if (preg_match('/\s/', $search)) {
				$search = str_replace(' ', '%', $search);
			}
			$search = $db->quote('%' . $search . '%');
			
			$search_query = array();
			$search_query[] = $db->quoteName('a.name') . 'LIKE ' . $search;
			$query->where('(' . implode(' OR ', $search_query) . ')');
		}
		
		$query->group($db->quoteName('a.id'));

		// Add the list ordering clause.
		$orders = (array) $this->getState('filter.orders');
		
		switch ($this->menu->params->get('jenis')) {
			default:
				$orderFields = array(
					2 => 'a.id', 3 => 'a.name'
				);
				break;
			case 'market':
			case 'market2':
			case 'market3':
				$orderFields = array(
					2 => 'a.id', 3 => 'a.name', 4 => 'd.name', 5 => 'c.name', 6 => 'date'
				);
				break;
		}

		$fields = $this->getFields();

		foreach ($orders as $order) {
			$order = JArrayHelper::toObject($order);
			$ordername = @$orderFields[$order->column];
			if(!$ordername) continue;

			switch ($ordername) {
				case 'date' :
					$query->order('IF(DAY('.$db->quoteName('a.modified').'), '.$db->quoteName('a.modified').', '.$db->quoteName('a.created').') ' . $order->dir);
					$query->order($db->quoteName('a.id') . ' ' . $order->dir);
					break;
				default:
					$query->order($db->quoteName($ordername).' '.$order->dir);
					break;
			}
		}
		
		//echo nl2br(str_replace('#__','eburo_',$query)); die;
		return $query;
	}

	public function getFields() {
		$checkrow	= '<input type="checkbox" name="checkall-toggle" value="" title="'.JText::_('COM_GTPIHPSSURVEY_CHECK_ALL').'" onclick="Joomla.checkAll(this)" />';

		switch ($this->menu->params->get('jenis')) {
			case 'market':
			case 'market2':
			case 'market3':
				return array(
					array($checkrow, 'checkrow', 'text-center', '15px', false),
					array(JText::_('COM_GTPIHPSSURVEY_ACTION'), 'action', 'text-center', '50px', false),
					array(JText::_('JGLOBAL_FIELD_ID_LABEL'), 'id', 'text-center', '80px', true),
					array(JText::_('COM_GTPIHPSSURVEY_FIELD_NAME'), 'name', 'text-left', 'auto', true),
					array(JText::_('COM_GTPIHPSSURVEY_FIELD_PROVINCE'), 'province', 'text-left', 'auto', true),
					array(JText::_('COM_GTPIHPSSURVEY_FIELD_REGENCY'), 'regency', 'text-left', 'auto', true),
					array(JText::_('COM_GTPIHPSSURVEY_FIELD_DATE'), 'date', 'text-left', '300px', true)
				);
				break;
			case 'seller':
				return array(
					array($checkrow, 'checkrow', 'text-center', '15px', false),
					array(JText::_('COM_GTPIHPSSURVEY_ACTION'), 'action', 'text-center', '50px', false),
					array(JText::_('JGLOBAL_FIELD_ID_LABEL'), 'id', 'text-center', '80px', true),
					array(JText::_('COM_GTPIHPSSURVEY_FIELD_NAME'), 'name', 'text-left', 'auto', true),
					array(JText::_('COM_GTPIHPSSURVEY_FIELD_PROVINCE'), 'province', 'text-left', 'auto', true),
					array(JText::_('COM_GTPIHPSSURVEY_FIELD_REGENCY'), 'regency', 'text-left', 'auto', true),
					array(JText::_('COM_GTPIHPSSURVEY_FIELD_MARKET'), 'market', 'text-left', 'auto', true),
					array(JText::_('COM_GTPIHPSSURVEY_FIELD_DATE'), 'date', 'text-left', '300px', true)
				);
				break;
		}
	}

	public function getItems($is_table = false) {
		$jenis = $this->menu->params->get('jenis');
		$items = parent::getItems($is_table);
		
		foreach ($items as $i => &$item) {
			$date 			= $item->date;

			switch ($jenis) {
				case 'fitur':
					$item->tanggal = GTHelperDate::format($item->tanggal, 'd M Y');
					break;
				default:
					break;
			}

			$item->date		= GTHelperDate::format($date, 'd M Y H:i');
			$editUrl  = GTHelper::getURL(array(
				'task'	=> 'ref_item.edit',
				'id'	=> $item->id,
				'tmpl'	=> 'component'
			));
			$viewUrl = GTHelper::getURL(array(
				'view'		=> 'ref_item',
				'layout'	=> 'view',
				'id'		=> $item->id,
				'tmpl'		=> 'component'
			));

			$item->checkrow	= JHtml::_('grid.id', $i, $item->id);
			$item->action = '<div class="text-left" style="width:60px">'.implode('', array(
				sprintf('<button link="%s" class="modalForm btn btn-default btn-xs btn-%s" style="margin: 0 3px 5px !important;"><i class="fa fa-fw fa-%s"></i> %s</button>', $viewUrl, 'primary', 'eye', JText::_('COM_GTPIHPSSURVEY_TOOLBAR_VIEW'))
			)).'</div>';

			$item = GTHelperArray::handleNull($item, '<div class="text-center" style="color:red">- '.JText::_('COM_GTPIHPSSURVEY_EMPTY').' -</div>');
			$item->diff = intval($date) ? GTHelperDate::diff($date) : null;
		}

		return $this->prepareItemsJson($items);
	}

	public function getTotal($cachable = true, $removeJoin = false) {
		return parent::getTotal($cachable, $removeJoin);
	}
}
