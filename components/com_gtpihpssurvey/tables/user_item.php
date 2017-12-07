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

class TableUser_Item extends GTTable{
	
	/**
	 * Constructor
	 *
	 * @param object Database connector object
	 * @since 1.0
	 */
	function __construct(&$db) {
		// Get Feeder ID
		$input		= JFactory::getApplication()->input;

		parent::__construct('#__gtpihpssurvey_users' , 'id', $db);
	}
	
	/**
	 * Stores a contact
	 *
	 * @param	boolean	True to update fields even if they are null.
	 * @return	boolean	True on success, false on failure.
	 * @since	1.6
	 */
	public function store($updateNulls = true) {
		// Attempt to store the data.
		return parent::store($updateNulls);
	}
	
	public function bind($array, $ignore = '') {
		$row = JArrayHelper::toObject($array);
		
		if(!@$row->id) 
			return parent::bind($array, $ignore);

		$row->market_ids	= is_string($row->market_ids) ? explode(',', $row->market_ids) : $row->market_ids;
		$row->seller_ids	= is_string($row->seller_ids) ? explode(',', $row->seller_ids) : $row->seller_ids;

		$markets = $this->getTable('ref_market');
		$markets = $markets->getList(array('id' => $row->market_ids));

		$region		= $this->getTable('ref_region'); $region->load(@$row->region_id);
		$region		= $region->getProperties(1);
		$region		= JArrayHelper::toObject($region);

		$sellers = $this->getTable('ref_seller');
		$sellers = $sellers->getList(array('id' => $row->seller_ids));
		$sellers = array_map(function($com) use ($markets) { $mkt = @$markets[$com['market_id']]['name']; $mkt = $mkt ? ' - '.$mkt : $mkt;  return $com['name'].$mkt; }, $sellers);
		$sellers = implode('<br/>', $sellers);
		$sellers = $sellers ? $sellers : JText::_('COM_GTPIHPSSURVEY_OPT_ALL_SELLERS');

		$markets = array_map(function($com) { return $com['name']; }, $markets);
		$markets = implode('<br/>', $markets);
		$markets = $markets ? $markets : JText::_('COM_GTPIHPSSURVEY_OPT_ALL_MARKETS');
		
		$row->view				= new stdCLass();
		$row->view->market_ids	= $markets;
		$row->view->seller_ids	= $sellers;
		$row->view->region_id	= $region->name;

		$array = JArrayHelper::fromObject($row);
		return parent::bind($array, $ignore);
	}
}
