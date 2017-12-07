<?php

/**
 * @package     GT Component
 * @author      Yudhistira Ramadhan
 * @link        http://gt.web.id
 * @license     GNU/GPL
 * @copyright   Copyright (C) 2012 GtWeb Gamatechno. All Rights Reserved.
 */
defined('_JEXEC') or die;

class GTPIHPSSurveyModelPerformance_Report extends GTModelList{
	
	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  JDatabaseQuery
	 * @since   1.6
	 */
	
	public function __construct($config = array()) {
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = array();
		}

		parent::__construct($config);
	}
	
	protected function populateState($ordering = null, $direction = null) {
		parent::populateState($ordering, $direction);

		$this->setState('list.start', 0);
		$this->setState('list.limit', 0);
		
		// Adjust the context to support modal layouts.
		$layout = $this->input->get('layout', 'default');
		if ($layout) {
			$this->context.= '.' . $layout;
		}

		$date = $this->getUserStateFromRequest($this->context . '.filter.date', 'filter_date', JHtml::date('now', 'Y-m-d'));
		$this->setState('filter.date', JHtml::date($date, 'd-m-Y'));

		$all_provinces = array_keys($this->getProvinces(true));
		$province_ids = $this->getUserStateFromRequest($this->context . '.filter.province_ids', 'filter_province_ids', $all_provinces, 'array');
		$province_ids = array_merge($province_ids, array(0));
		$this->setState('filter.province_ids', $province_ids);

		$report_type = $this->getUserStateFromRequest($this->context . '.filter.report_type', 'filter_report_type', '0');
		$this->setState('filter.report_type', $report_type);

		$price_type_id = $this->getUserStateFromRequest($this->context . '.filter.price_type_id', 'filter_price_type_id', '1');
		$this->setState('filter.price_type_id', $price_type_id);
	}

	public function getDayCount() {
		$date		= JHtml::date($this->getState('filter.date'), 'Y-m-d');

		// Get a db connection.
		$db = $this->_db;

		// Create a new query object.
		$query = $db->getQuery(true);
		
		// Select prices
		$query->select('COUNT(DISTINCT '.$db->quoteName('a.date').') total');
		$query->from($db->quoteName('#__gtpihpssurvey_prices', 'a'));

		$query->where('DAYOFWEEK('.$db->quoteName('a.date').') NOT IN (1,7)');
		$query->where($db->quoteName('a.date').' = '.$db->quote($date));

		//echo nl2br(str_replace('#__','eburo_',$query)); die;
		$db->setQuery($query);
		return @$db->loadObject()->total;
	}
	
	protected function getCounts2($report_type = null) {
		$date		= JHtml::date($this->getState('filter.date'), 'Y-m-d');
		$price_type_id = $this->getState('filter.price_type_id');

		// Get a db connection.
		$db = $this->_db;
		
		// Create a new query object.
		$query = $db->getQuery(true);
		
		// Select prices
		$query->select($db->quoteName(array('a.id', 'a.province_id', 'a.regency_id', 'a.market_id')));
		$query->select('COUNT('.$db->quoteName('a.id').') total');
		$query->from($db->quoteName('#__gtpihpssurvey_prices', 'a'));

		// Join regency
		$query->join('INNER', $db->quoteName('#__gtpihpssurvey_ref_regencies', 'c') . ' ON ' . $db->quoteName('a.regency_id') . ' = ' . $db->quoteName('c.id'));

		// Join market
		$query->join('INNER', $db->quoteName('#__gtpihpssurvey_ref_markets', 'd') . ' ON ' . $db->quoteName('a.market_id') . ' = ' . $db->quoteName('d.id'));

		// Dates filter
		$query->where($db->quoteName('a.date').' = '.$db->quote($date));
		
		// Publish filter
		$query->where($db->quoteName('d.price_type_id') . ' = '.$db->quote($price_type_id));
		$query->where($db->quoteName('a.published') . ' = 1');

		$query->where('DAYOFWEEK('.$db->quoteName('a.date').') NOT IN (1,7)');

		$time_diff = JHtml::date('now', 'Z');
		$whereOnTime = '('.$db->quoteName('a.date').' = DATE(DATE_ADD('.$db->quoteName('a.created').', INTERVAL '.$time_diff.' SECOND)) AND HOUR(DATE_ADD('.$db->quoteName('a.created').', INTERVAL '.$time_diff.' SECOND)) <= '.$db->quote('13').')';
		switch ($report_type) {
			case '1':
				$query->where($whereOnTime);
				break;
			case '2':
				$query->where('!'.$whereOnTime);
				break;
		}
		
		$query->group($db->quoteName('a.market_id'));
		//echo nl2br(str_replace('#__','eburo_',$query)); die;
		$db->setQuery($query);
		$data = $db->loadObjectList('market_id');

		$items = array();
		foreach ($data as $market_id => $item) {
			$items[$item->province_id][$item->regency_id][$market_id] = $item->total;
		}
		return $items;
	}

	protected function getCounts($report_type = null) {
		$date		= JHtml::date($this->getState('filter.date'), 'Y-m-d');
		$price_type_id = $this->getState('filter.price_type_id');

		// Get a db connection.
		$db = $this->_db;
		
		// Create a new query object.
		$query = $db->getQuery(true);
		$query2 = $db->getQuery(true);


		$query2->select('MAX('.$db->quoteName('a.id').') id');
		$query2->from($db->quoteName('#__gtpihpssurvey_prices', 'a'));
		
		// Dates filter
		$query2->where($db->quoteName('a.date').' = '.$db->quote($date));
		$query2->group($db->quoteName('a.market_id'));

		// Publish filter
		$query2->where($db->quoteName('a.published') . ' = 1');

		// Select prices
		$query->select($db->quoteName(array('a.id', 'a.province_id', 'a.regency_id', 'a.market_id')));
		$query->select('1 total');
		$query->from($db->quoteName('#__gtpihpssurvey_prices', 'a'));

		$query->join('INNER', '('.$query2.') b ON '.$db->quoteName('a.id').' = '.$db->quoteName('b.id'));

		$time_diff = JHtml::date('now', 'Z');
		$whereOnTime = '('.$db->quoteName('a.date').' = DATE(DATE_ADD('.$db->quoteName('a.created').', INTERVAL '.$time_diff.' SECOND)) AND HOUR(DATE_ADD('.$db->quoteName('a.created').', INTERVAL '.$time_diff.' SECOND)) <= '.$db->quote('13').')';
		switch ($report_type) {
			case '1':
				$query->where($whereOnTime);
				$query->where($db->quoteName('a.status').' = '.$db->quote('approved'));
				break;
			case '2':
				$query->where('!'.$whereOnTime);
				$query->where($db->quoteName('a.status').' = '.$db->quote('approved'));
				break;
			case '3':
				$query->where($db->quoteName('a.status').' != '.$db->quote('approved'));
				break;
		}
		
		//echo nl2br(str_replace('#__','eburo_',$query)); die;
		$db->setQuery($query);
		$data = $db->loadObjectList('market_id');

		$items = array();
		foreach ($data as $market_id => $item) {
			$items[$item->province_id][$item->regency_id][$market_id] = $item->total;
		}
		return $items;
	}

	public function getItems($table = false) {
		$dayCount		= $this->getDayCount();
		$refProvinces	= $this->getProvinces();
		$refRegencies	= $this->getRegencies();
		$refMarkets		= $this->getMarkets();

		$percentages		= array();
		$countsOnTime		= $this->getCounts(1);
		$countsLate			= $this->getCounts(2);
		$countsWait			= $this->getCounts(3);

		//echo "<pre>"; print_r($refMarkets); echo "</pre>"; die;
		
		foreach ($refProvinces as $province_id => $province) {
			$countProvince = 0;
			$countDayProvince = 0;
			$sumProvince = 0;
			$sumProvinceOT = 0;
			$sumProvinceLT = 0;
			$sumProvinceWT = 0;
			$provRegencies = @$refRegencies[$province_id];
			if(!is_array($provRegencies)) {
				unset($refProvinces[$province_id]);
				continue;
			}
			
			//echo "<pre>"; print_r($provRegencies); echo "</pre>";
			foreach ($provRegencies as $regency_id => $regency) {
				$countRegency = 0;
				$countDayRegency = 0;
				$sumRegency = 0;
				$sumRegencyOT = 0;
				$sumRegencyLT = 0;
				$sumRegencyWT = 0;
				$regMarkets = @$refMarkets[$regency_id];
				if(!is_array($regMarkets)) {
					unset($provRegencies[$regency_id]);
					continue;
				}

				foreach ($regMarkets as $market_id => $market) {
					$marketCountOT	= intval(@$countsOnTime[$province_id][$regency_id][$market_id]);
					$marketCountLT	= intval(@$countsLate[$province_id][$regency_id][$market_id]);
					$marketCountWT	= intval(@$countsWait[$province_id][$regency_id][$market_id]);

					/*if(!$marketCount > 0) {
						unset($regMarkets[$market_id]);
						continue;
					}*/

					$mItem			= new stdClass();
					$mItem->name	= $market;

					if($marketCountOT > 0) {
						$sumProvinceOT++;
						$sumRegencyOT++;
						$mItem->status = 1;
					} elseif(($marketCountLT) > 0) {
						$sumProvinceLT++;
						$sumRegencyLT++;
						$mItem->status = 2;
					} elseif(($marketCountWT) > 0) {
						$sumProvinceWT++;
						$sumRegencyWT++;
						$mItem->status = 3;
					} else {
						$mItem->status = 0;
					}

					$sumProvince++;
					$sumRegency++;
					
					$countProvince++;
					$countRegency++;

					$regMarkets[$market_id] = $mItem;
				}

				/*if(!$countRegency > 0) {
					unset($provRegencies[$regency_id]);
					continue;
				}*/

				$rItem				= new stdClass();
				$rItem->name		= $regency;
				$rItem->ontime		= $sumRegencyOT > 0 ? round(($sumRegencyOT/$sumRegency) * 100, 1) : 0;
				$rItem->late		= $sumRegencyLT > 0 ? round(($sumRegencyLT/$sumRegency) * 100, 1) : 0;
				$rItem->wait		= $sumRegencyWT > 0 ? round(($sumRegencyWT/$sumRegency) * 100, 1) : 0;
				$rItem->empty		= 100 - $rItem->ontime - $rItem->late - $rItem->wait;
				$rItem->count		= $countRegency;
				$rItem->children	= $regMarkets;

				$provRegencies[$regency_id] = $rItem;
			}

			if(!count($provRegencies)>0) {
				unset($refProvinces[$province_id]);
				continue;
			}
			
			$pItem				= new stdClass();
			$pItem->name		= $province;
			$pItem->ontime		= $sumProvinceOT ? round(($sumProvinceOT/$sumProvince) * 100, 1) : 0;
			$pItem->late		= $sumProvinceLT ? round(($sumProvinceLT/$sumProvince) * 100, 1) : 0;
			$pItem->wait		= $sumProvinceWT ? round(($sumProvinceWT/$sumProvince) * 100, 1) : 0;
			$pItem->empty		= 100 - $pItem->ontime - $pItem->late - $pItem->wait;
			$pItem->count		= $countProvince;
			$pItem->children	= $provRegencies;

			$refProvinces[$province_id] = $pItem;
		}
		return $refProvinces; 
	}

	public function getSelectedProvinces() {
		return $this->getProvinces(false);
	}

	public function getProvinces($all = true) {
		// Get a db connection.
		$db = $this->_db;

		// Create a new query object.
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('a.id', 'a.name')));
		$query->from($db->quoteName('#__gtpihpssurvey_ref_provinces', 'a'));

		//$query->where($db->quoteName('a.published') . ' = 1');

		if(!$all) {
			$province_ids = (array) $this->getState('filter.province_ids');
			$province_ids = array_map(array($db, 'quote'), $province_ids);
			$query->where($db->quoteName('a.id') . ' IN ('.implode(',', $province_ids).')');
		}

		$query->order($db->quoteName('a.id'));
		$query->group($db->quoteName('a.id'));

		$db->setQuery($query);
		$raw = $db->loadObjectList();
		$data = array();
		foreach ($raw as $item) {
			$data[$item->id] = trim($item->name);
		}

		//echo nl2br(str_replace('#__','eburo_',$query)); die;
		return $data;
	}

	public function getRegencies() {
		// Get a db connection.
		$db = $this->_db;

		$province_ids = (array) $this->getState('filter.province_ids');
		$province_ids = array_map(array($db, 'quote'), $province_ids);

		// Create a new query object.
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('a.id', 'a.province_id', 'a.type', 'a.name')));
		$query->from($db->quoteName('#__gtpihpssurvey_ref_regencies', 'a'));

		// Join Market
		$query->join('INNER', $db->quoteName('#__gtpihpssurvey_ref_markets', 'b').' ON '.$db->quoteName('a.id').' = '.$db->quoteName('b.regency_id'));
		
		$query->group($db->quoteName('a.id'));
		//$query->where($db->quoteName('b.published') . ' = 1');
		$query->where($db->quoteName('a.province_id') . ' IN ('.implode(',', $province_ids).')');

		$query->order($db->quoteName('a.province_capital').' desc');
		$query->order($db->quoteName('a.type'));

		$db->setQuery($query);
		
		$result = array();
		foreach ($db->loadObjectList('id') as $k => $regency) {
			$name = sprintf(JText::_('COM_GTPIHPSSURVEY_'.strtoupper($regency->type)), $regency->name);
			$result[$regency->province_id][$k] = trim($name);
		}
		//echo nl2br(str_replace('#__','eburo_',$query));
		return $result;
	}

	public function getMarkets() {
		// Get a db connection.
		$db = $this->_db;

		$price_type_id = $this->getState('filter.price_type_id');
		$province_ids = (array) $this->getState('filter.province_ids');
		$province_ids = array_map(array($db, 'quote'), $province_ids);

		// Create a new query object.
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('a.id', 'a.regency_id', 'a.name')));
		$query->from($db->quoteName('#__gtpihpssurvey_ref_markets', 'a'));
		
		$query->where($db->quoteName('a.published') . ' = 1');
		$query->where($db->quoteName('a.province_id') . ' IN ('.implode(',', $province_ids).')');
		$query->where($db->quoteName('a.price_type_id'). ' = '.$db->quote($price_type_id));

		$db->setQuery($query);
		
		$result = array();
		foreach ($db->loadObjectList('id') as $k => $market) {
			$result[$market->regency_id][$k] = trim($market->name);
		}
		
		//echo nl2br(str_replace('#__','eburo_',$query)); die;
		return $result;
	}

	public function getPriceTypes() {
		// Get a db connection.
		$db = JFactory::getDBO();

		// Create a new query object.
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('a.id', 'a.name')));
		$query->from($db->quoteName('#__gtpihpssurvey_ref_price_types', 'a'));
		
		if (JFactory::getUser()->guest) {
			$query->where($db->quoteName('a.published') . ' = 1');
		}
		
		$db->setQuery($query);
		return $db->loadObjectList();
	}
}