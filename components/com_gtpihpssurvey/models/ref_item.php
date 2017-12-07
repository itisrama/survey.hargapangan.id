<?php

/**
 * @package     GT Component
 * @author      Yudhistira Ramadhan
 * @link        http://gt.web.id
 * @license     GNU/GPL
 * @copyright   Copyright (C) 2012 GtWeb Gamatechno. All Rights Reserved.
 */
defined('_JEXEC') or die;

class GTPIHPSSurveyModelRef_Item extends GTModelAdmin
{

	public function __construct($config = array()) {
		parent::__construct($config);
	}

	protected function populateState() {
		parent::populateState();

		$id = $this->input->getInt('id', 0);
		$this->setState($this->getName().'.id', intval($id));
	}
	
	public function getItem($pk = null) {
		$data = parent::getItem();
		if(!is_object($data)) return false;

		$this->item	= $data;

		return $data;
	}

	public function getForm($data = array(), $loadData = true, $control = 'jform') {
		$component_name = $this->input->get('option');
		$model_name = $this->getName();
		
		$data = $data ? JArrayHelper::toObject($data) : $this->getFormData();
		$this->data = $data;

		$jenis	= $this->menu->params->get('jenis');
		$jenis2	= preg_replace('/\d/', '', $jenis);

		switch ($jenis) {
			case 'market2':
			case 'market3':
				$form = 'ref_marketseller';
				break;
			
			default:
				$form = 'ref_'.$jenis2;
				break;
		}
		
		// Get the form.
		$form = $this->loadForm($component_name . '.' . $model_name, $form, array('control' => $control, 'load_data' => $loadData));
		if (empty($form)) {
			return false;
		}

		return $form;
	}

	public function getTable($name = '', $prefix = 'Table', $options = array()) {
		if (empty($name)) {
			$jenis	= $this->menu->params->get('jenis');
			$name	= ucwords('ref_'.preg_replace('/\d/', '', $jenis));
		}
		return parent::getTable($name, $prefix, $options);
	}

	public function save($data){
		$data	= JArrayHelper::toObject($data);
		$jenis	= $this->menu->params->get('jenis');

		sort($data->commodity_ids);

		switch ($jenis) {
			case 'market':
			case 'market2':
			case 'market3':
				$regency		= $this->getItemExternal($data->regency_id, 'ref_regency');
				$price_type_id	= str_replace('market', '', $jenis);
				$price_type_id	= $price_type_id ? $price_type_id : '1';
				
				if(!$data->id > 0) {
					$oldData = $this->searchItem('ref_market', array(
						'regency_id'	=> $data->regency_id,
						'name'			=> $data->name,
						'price_type_id'	=> $price_type_id
					));


					$commodity_ids = explode(',', @$oldData->commodity_ids);
					$commodity_ids = array_merge((array) $data->commodity_ids, $commodity_ids);
					$commodity_ids = array_unique($commodity_ids);
					$commodity_ids = array_values($commodity_ids);

					sort($commodity_ids);

					$data->id				= intval(@$oldData->id);
					$data->commodity_ids	= $commodity_ids;
				}
				
				$data->province_id		= $regency->province_id;
				$data->price_type_id	= $price_type_id;
				$data->published 		= is_numeric($data->published) ? $data->published : 1;
				break;
			case 'seller':
				$regency = $this->getItemExternal($data->regency_id, 'ref_regency');

				if(!$data->id > 0) {
					$oldData = $this->searchItem('ref_seller', array(
						'market_id'		=> $data->market_id,
						'name'			=> $data->name,
					));

					$commodity_ids = explode(',', @$oldData->commodity_ids);
					$commodity_ids = array_merge((array) $data->commodity_ids, $commodity_ids);
					$commodity_ids = array_unique($commodity_ids);
					$commodity_ids = array_values($commodity_ids);

					sort($commodity_ids);
					
					$data->id				= intval(@$oldData->id);
					$data->commodity_ids	= $commodity_ids;
					$data->published 		= is_numeric($data->published) ? $data->published : 1;
				}
				
				$data->province_id		= $regency->province_id;
				break;
		}

		if(!parent::save($data)) return false;

		switch ($jenis) {
			case 'market2':
			case 'market3':
				$market_id = $this->getState($this->getName().'.id');

				$data->id			= $this->searchItem('ref_seller', array('market_id' => $market_id), true);
				$data->market_id	= $market_id;

				if(!parent::saveExternal($data, 'ref_seller')) return false;
				break;
		}

		return true;
	}

	public function delete(&$pks) {
		//return parent::delete($pks);
		return true;
	}
}
