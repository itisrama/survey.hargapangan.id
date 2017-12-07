<?php

/**
 * @package		GT Component 
 * @author		Yudhistira Ramadhan
 * @link		http://gt.web.id
 * @license		GNU/GPL
 * @copyright	Copyright (C) 2012 GtWeb Gamatechno. All Rights Reserved.
 */
defined('_JEXEC') or die;

class GTPIHPSViewPerformance_Report extends GTView {

	protected $items;
	protected $pagination;
	protected $state;

	public function __construct($config = array()) {
		parent::__construct($config);
	}

	function display($tpl = 'excel') {
		// Get model data.
		$this->state		= $this->get('State');
		$this->items		= $this->get('Items');
		$this->dayCount		= $this->get('DayCount');
		$this->provinces	= $this->get('Provinces');

		$document = JFactory::getDocument();

		// Set filename
		$filename = array(
			JText::_('COM_GTPIHPS_HEADER_PERFORMANCE_REPORT_S'),
			JHtml::date($this->state->get('filter.start_date'), 'dFY'),
			JHtml::date($this->state->get('filter.end_date'), 'dFY')
		);
		$document->setName(implode('-', $filename));

		$objPHPExcel = $document->getPhpExcelObj();

		// Set properties
		$objPHPExcel->getProperties()->setCreator($this->user->name);
		$objPHPExcel->getProperties()->setTitle(JText::_('COM_GTPIHPS_HEADER_PERFORMANCE_REPORT'));

		// Rename sheet
		$objPHPExcel->setActiveSheetIndex(0);
		$objPHPExcel->getActiveSheet()->setTitle(JText::_('COM_GTPIHPS_HEADER_PERFORMANCE_REPORT_S'));
		
		$this->objPHPExcel = $objPHPExcel;
		
		parent::display($tpl);
	}

}
