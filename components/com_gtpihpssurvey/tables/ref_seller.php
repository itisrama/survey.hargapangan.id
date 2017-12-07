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

class TableRef_Seller extends GTTable{
	
	/**
	 * Constructor
	 *
	 * @param object Database connector object
	 * @since 1.0
	 */
	function __construct(&$db) {
		parent::__construct('#__gtpihpssurvey_ref_sellers', 'id', $db);
	}
	
	/**
	 * Stores a contact
	 *
	 * @param	boolean	True to update fields even if they are null.
	 * @return	boolean	True on success, false on failure.
	 * @since	1.6
	 */
	public function store($updateNulls = false) {
		// Attempt to store the data.
		$this->commodity_ids = implode(',', $this->commodity_ids);

		return parent::store($updateNulls);
	}
	
	public function bind($array, $ignore = '') {
		$row = JArrayHelper::toObject($array);
		
		if(!$row->id) 
			return parent::bind($array, $ignore);

		$regency		= $this->getTable('ref_regency'); $regency->load(@$row->regency_id);
		$regency		= $regency->getProperties(1);
		$regency		= JArrayHelper::toObject($regency);

		$market		= $this->getTable('ref_market'); $market->load(@$row->market_id);
		$market		= $market->getProperties(1);
		$market		= JArrayHelper::toObject($market);

		
		$row->province_id		= $regency->province_id;
		$row->commodity_ids 	= is_string($row->commodity_ids) ? explode(',', $row->commodity_ids) : $row->commodity_ids;

		$commodities = $this->getTable('ref_commodity');
		$commodities = $commodities->getList(array('id' => $row->commodity_ids));
		$commodities = array_map(function($com) { return $com['name']; }, $commodities);
		$commodities = implode('<br/>', $commodities);

		$row->view					= new stdCLass();
		$row->view->regency_id		= $regency->name;
		$row->view->market_id		= $market->name;
		$row->view->commodity_ids	= $commodities;
		
		$array = JArrayHelper::fromObject($row);
		return parent::bind($array, $ignore);
	}
}
