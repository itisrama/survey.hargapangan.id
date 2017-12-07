<?php

/**
 * @package     GT Component
 * @author      Yudhistira Ramadhan
 * @link        http://gt.web.id
 * @license     GNU/GPL
 * @copyright   Copyright (C) 2012 GtWeb Gamatechno. All Rights Reserved.
 */
defined('_JEXEC') or die;


class GTPIHPSSurveyModelJSON extends GTModel
{

	public function __construct($config = array()) {
		parent::__construct($config);

		$tab_position = $this->app->getUserStateFromRequest($this->context . '.tab_position', 'tab_position', 'project');
		$this->setState($this->getName() . '.tab_position', $tab_position);
	}

	protected function populateState() {
		parent::populateState();

		$id = $this->input->getInt('id', 0);
		$this->setState($this->getName().'.id', intval($id));
	}
}
