<?php

/**
 * @package		GT Component 
 * @author		Yudhistira Ramadhan
 * @link		http://gt.web.id
 * @license		GNU/GPL
 * @copyright	Copyright (C) 2012 GtWeb Gamatechno. All Rights Reserved.
 */
defined('_JEXEC') or die;

class GTPIHPSSurveyViewPerformance_Report extends GTView {

	protected $items;
	protected $pagination;
	protected $state;

	public function __construct($config = array()) {
		parent::__construct($config);
	}

	function display($tpl = null) {
		// Get model data.
		$this->state	= $this->get('State');
		$this->items	= $this->get('Items');
		$this->dayCount	= $this->get('DayCount');
		
		$this->provinces	= $this->get('Provinces');
		$this->priceTypes	= $this->get('PriceTypes');

		$this->start_date	= strtotime($this->state->get('filter.start_date'));
		$this->end_date		= strtotime($this->state->get('filter.end_date'));

		parent::display($tpl);
	}

}
