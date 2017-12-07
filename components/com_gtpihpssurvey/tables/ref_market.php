<?php

/**
 * @package		GT PIHPS
 * @author		Yudhistira Ramadhan
 * @link		http://gt.web.id
 * @license		GNU/GPL
 * @copyright	Copyright (C) 2012 GtWeb Gamatechno. All Rights Reserved.
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

class TableRef_Market extends GTTable{
	
	/**
	 * Constructor
	 *
	 * @param object Database connector object
	 * @since 1.0
	 */
	function __construct(&$db) {
		parent::__construct('#__gtpihpssurvey_ref_markets', 'id', $db);
	}
	
	/**
	 * Stores a contact
	 *
	 * @param	boolean	True to update fields even if they are null.
	 * @return	boolean	True on success, false on failure.
	 * @since	1.6
	 */
	public function store($updateNulls = false) {
		$this->commodity_ids = implode(',', $this->commodity_ids);

		// Attempt to store the data.
		$data = $this->getProperties(0);
		return parent::store($updateNulls);
	}
	
	public function bind($array, $ignore = '') {
		$row = JArrayHelper::toObject($array);
		
		if(!$row->id) 
			return parent::bind($array, $ignore);

		$regency		= $this->getTable('ref_regency'); $regency->load(@$row->regency_id);
		$regency		= $regency->getProperties(1);
		$regency		= JArrayHelper::toObject($regency);

		$price_type		= $this->getTable('ref_price_type'); $price_type->load(@$row->price_type_id);
		$price_type		= $price_type->getProperties(1);
		$price_type		= JArrayHelper::toObject($price_type);

		$row->commodity_ids 	= is_string($row->commodity_ids) ? explode(',', $row->commodity_ids) : $row->commodity_ids;

		$commodities = $this->getTable('ref_commodity');
		$commodities = $commodities->getList(array('id' => $row->commodity_ids));
		$commodities = array_map(function($com) { return $com['name']; }, $commodities);
		$commodities = implode('<br/>', $commodities);

		$row->view					= new stdCLass();
		$row->view->regency_id		= $regency->long_name;
		$row->view->price_type_id	= $price_type->name;
		$row->province_id			= $regency->province_id;
		$row->view->commodity_ids	= $commodities;

		$array = JArrayHelper::fromObject($row);
		return parent::bind($array, $ignore);
	}
}
