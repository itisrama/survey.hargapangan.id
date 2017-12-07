<?php

/**
 * @package		GT Component
 * @author		Yudhistira Ramadhan
 * @link		http://gt.web.id
 * @license		GNU/GPL
 * @copyright	Copyright (C) 2012 GtWeb Gamatechno. All Rights Reserved.
 */
defined('_JEXEC') or die;

class GTPIHPSSurveyControllerService extends GTControllerForm {

	public function __construct($config = array()) {
		parent::__construct($config);

		$this->getViewItem($urlQueries = array());
	}

	public function getModel($name = '', $prefix = '', $config = array('ignore_request' => true)) {
		$model = parent::getModel($name, $prefix, $config);
		return $model;
	}

	protected function prepareJSON($input, $data, $result = true, $message = null) {
		$model = $this->getModel();
		$json = new stdClass();
		$json->result = $result;

		if($data) {
			$json->data = $data;
		}
		if($message) {
			$json->message = $message;
		}

		if(!$this->input->get('debugdb')) {
			header('Content-type: application/json; charset=utf-8');
			echo json_encode($json);
		}
		
		$model->saveServiceLog($input, $json);

		$this->app->close();
	}

	protected function doLogin($type = 'login') {
		$model		= $this->getModel();
		$result		= new stdClass();
		$username	= $this->input->get('username', '', 'username');
		$password	= $this->input->get('password', '', 'raw');

		if(!($username && $password)) {
			$result->status		= false;
			$result->message	= $type == 'login' ? JText::_('COM_GTPIHPSSURVEY_LOGIN_USER_EMPTY') : JText::_('COM_GTPIHPSSURVEY_LOGIN_PASSWORD_EMPTY');
			return $result;
		}
		
		// Get the global JAuthentication object.
		jimport('joomla.user.authentication');

		$auth		= JAuthentication::getInstance();
		$response	= $auth->authenticate(array(
			'username' => $username,
			'password' => $password
		), array(
			'remember' => false,
			'return' => null
		));

		$userSys	= JFactory::getUser($response->username);
		$status		= $response->status === JAuthentication::STATUS_SUCCESS;
		$userSurvey	= $model->getUser($userSys->id);
		$userStatus	= intval(@$userSurvey->published);

		if($status && @$userSurvey->id && @$userSurvey->published == 1) {
			$result->userSys	= $userSys;
			$result->userSur	= $userSurvey;
			$result->status		= true;
			$result->message	= JText::_('COM_GTPIHPSSURVEY_LOGIN_USER_SUCCESS');
		} elseif($userStatus != 1) {
			$result->status		= false;
			$result->message	= JText::_('COM_GTPIHPSSURVEY_LOGIN_USER_NOT_ACTIVE');
		} else {
			$result->status		= false;
			$result->message	= $type == 'login' ? JText::_('COM_GTPIHPSSURVEY_LOGIN_USER_WRONG') : JText::_('COM_GTPIHPSSURVEY_LOGIN_PASSWORD_WRONG');
		}

		return $result;
	}

	public function login($json = true) {
		$model = $this->getModel();
		$input = new stdClass();
		$input->post = $_POST;
		$input->get = $_GET;
		$model->saveServiceLog($input);

		
		$login = $this->doLogin();
		if(!$login->status) {
			$this->prepareJSON($input, null, false, $login->message);
		} else {
			$token		= $this->input->get('token', '', 'raw');
			$userSys	= $login->userSys;
			$userSur	= $login->userSur;
			$refUser	= $model->getReferencesByUser($userSys->id);

			// Update Token
			$model->updateToken($userSur->id, $token);

			$data				= new stdClass();
			$data->id			= $userSys->id;
			$data->displayname	= $userSys->name;
			$data->email		= $userSys->email;
			$data->phone		= $userSur->phone;
			$data->username		= $userSys->username;
			$data->type			= $userSur->type;
			$data->cities		= $refUser;

			$this->prepareJSON($input, $data, true, $login->message);
		}
	}

	public function updatePassword() {
		$model			= $this->getModel();
		$input			= new stdClass();
		$input->post	= $_POST;
		$input->get		= $_GET;
		$model->saveServiceLog($input);

		$userdata				= new stdClass();
		$userdata->id			= $this->input->get('user_id', '', 'int');
		$userdata->password		= $this->input->get('newpassword', '', 'raw');
		$userdata->password2	= $this->input->get('newpassword2', '', 'raw');
		$oldPassword 			= $this->input->get('password', '', 'raw');

		$result = $model->updateUser($userdata, $oldPassword);

		$this->prepareJSON($input, null, $result->status, $result->message);
	}

	public function updateProfil() {
		$model			= $this->getModel();
		$input			= new stdClass();
		$input->post	= $_POST;
		$input->get		= $_GET;
		$model->saveServiceLog($input);

		$userdata			= new stdClass();
		$userdata->id		= $this->input->get('user_id', '', 'int');
		$userdata->name		= $this->input->get('name');
		$userdata->email	= $this->input->get('email', '', 'raw');
		$phone 				= $this->input->get('phone');
		$oldPassword 		= $this->input->get('password', '', 'raw');
		$result = $model->updateUser($userdata, $oldPassword, $phone);

		$this->prepareJSON($input, null, $result->status, $result->message);
	}

	public function references() {
		$model			= $this->getModel();	
		$input			= new stdClass();
		$input->post	= $_POST;
		$input->get		= $_GET;
		$model->saveServiceLog($input);

		$user_id	= $this->input->get('user_id', '0');
		$data		= $model->getReferencesByUser($user_id);
		//echo "<pre>"; print_r($data); echo "</pre>"; die;

		$data 		= count($data) > 0 ? $data : false;
		
		$this->prepareJSON($input, $data);
	}

	public function testFirebase() {
		$model	= $this->getModel();

		$token		= $this->input->get('reg_id');
		$message	= $this->input->get('msg');
		$type		= $this->input->get('type');

		$model->sendToFirebase($token, $message, $type);

		$this->app->close();
	}

	public function checkData() {
		$model = $this->getModel();

		$input = new stdClass();
		$input->post = $_POST;
		$input->get = $_GET;
		$model->saveServiceLog($input);

		$data = $model->getPrice();

		if(@$data->id > 0) {
			$this->prepareJSON($input, $data, true, 'Data sudah terisi');
		} else {
			$this->prepareJSON($input, null, false, 'Data belum terisi');
		}
	}

	public function getItems() {
		$model = $this->getModel();
		$input = new stdClass();
		$input->post = $_POST;
		$input->get = $_GET;
		$model->saveServiceLog($input);

		$prices		= $model->getPrices();

		foreach ($prices as $price) {
			$price->created	= JHtml::date($price->created, 'd-m-Y H:i:s');
			$price->modified	= JHtml::date($price->modified, 'd-m-Y H:i:s');
		}

		if(count($prices) > 0) {
			$this->prepareJSON($input, $prices, true);
		} else {
			$this->prepareJSON($input, null, false, 'Belum ada data yang dapat ditampilkan');
		}
	}

	public function getItem() {
		$model = $this->getModel();
		$input = new stdClass();
		$input->post = $_POST;
		$input->get = $_GET;
		$model->saveServiceLog($input);

		$sellers		= $model->getSellers();
		$categories		= $model->getCommodityCategories();
		$commodities	= $model->getCommodities();
		$market 		= $model->getMarket();
		$date 			= $this->input->get('date');
		
		$price			= $model->getPrice();
		$priceOld		= $model->getPrice(true);
		$detail 		= $model->getPriceDetail(@$price->id);
		$detailOld 		= $model->getPriceDetail(@$priceOld->id);
		foreach ($sellers as &$seller) {
			$selected	= explode(',', $seller->commodities);
			$coms		= $model->prepareCommodities($categories, $commodities, $selected);

			foreach ($coms as &$com) {
				$price_now			= @$detail[$seller->id][$com->id];
				$price_then			= @$detailOld[$seller->id][$com->id];
				
				$com->price_then	= floatval(@$price_then->price);
				$com->price_now		= floatval(@$price_now->price);
				$com->status 		= intval(@$price_now->is_revision);
			}

			$seller->commodities = $coms;
		}

		$data				= new stdClass();
		$data->id			= intval(@$price->id);
		$data->market_id	= intval(@$market->id);
		$data->market		= @$market->name;
		$data->date			= $date;
		$data->message		= @$price->message;
		$data->type			= @$price->id > 0 ? (@$price->status == 'revision' ? 'revision' : 'edit') : 'new';
		$data->sellers		= $sellers;

		$this->prepareJSON($input, $data);
	}

	public function submit() {
		$model = $this->getModel();
		$input = new stdClass();
		$input->post = $_POST;
		$input->get = $_GET;
		$model->saveServiceLog($input);

		switch($model->submit()) {
			case 1:
				$this->prepareJSON($input, null, true, 'Data berhasil disimpan');
				break;
			case 2:
				$this->prepareJSON($input, null, false, 'Data gagal disimpan');
				break;
			case 3:
				$this->prepareJSON($input, null, 'failed', 'Sesi habis, silakan login ulang');
				break;
		}
	}

	public function delete() {
		$model = $this->getModel();
		$input = new stdClass();
		$input->post = $_POST;
		$input->get = $_GET;
		$model->saveServiceLog($input);

		$login = $this->doLogin('verify');

		if($login->status) {
			$model->deletePrice();
			$this->prepareJSON($input, null, true, JText::_('COM_GTPIHPSSURVEY_PRICE_DELETED'));
		} else {
			$this->prepareJSON($input, null, false, $login->message);
		}
	}

	public function getSurveys() {
		$model = $this->getModel();
		$input = new stdClass();
		$input->post = $_POST;
		$input->get = $_GET;
		$model->saveServiceLog($input);

		$surveys	= $model->getSurveys();

		foreach ($surveys as $survey) {
			$survey->created	= JHtml::date($survey->created, 'd-m-Y H:i:s');
			$survey->modified	= JHtml::date($survey->modified, 'd-m-Y H:i:s');
		}

		if(count($surveys) > 0) {
			$this->prepareJSON($input, $surveys, true);
		} else {
			$this->prepareJSON($input, null, false, 'Belum ada data submisi yang dapat ditampilkan');
		}
	}

	public function getSurveyDetail() {
		$model = $this->getModel();
		$input = new stdClass();
		$input->post = $_POST;
		$input->get = $_GET;
		$model->saveServiceLog($input);

		$price	= $model->getSurvey();
		$detail = $model->getPriceDetail(@$price->id);

		$this->input->set('user_id', @$price->surveyor_id);
		$this->input->set('market_id', @$price->market_id);
		$this->input->set('date', @$price->date);

		$sellers		= $model->getSellers();
		$categories		= $model->getCommodityCategories();
		$commodities	= $model->getCommodities();
		
		$priceOld		= $model->getPrice(true);
		$detailOld 		= $model->getPriceDetail(@$priceOld->id);

		foreach ($sellers as $seller) {
			$selected	= explode(',', $seller->commodities);
			$coms		= $model->prepareCommodities($categories, $commodities, $selected);

			foreach ($coms as &$com) {
				$price_now			= @$detail[$seller->id][$com->id];
				$price_then			= @$detailOld[$seller->id][$com->id];
				
				$com->status		= intval(@$price_now->is_revision);
				$com->price			= intval(@$price_now->price);

				$com->price_then	= intval(@$price_then->price);
				$com->price_now		= intval(@$price_now->price);
				$com->diff			= null;
				$com->percent		= null;

				if($com->price_now > 0 && $com->price_then) {
					$com->diff		= $com->price_now - $com->price_then;
					$com->trend		= $com->diff > 0 ? 'up' : ($com->diff < 0 ? 'down' : 'still');
					$com->diff		= abs($com->diff);
					$com->percent	= round(($com->diff / $com->price_then) * 100, 2).'%';
				} else {
					$com->trend		= 'unknown';
				}
				
			}

			$seller->commodities = $coms;
		}

		$price->sellers	= $sellers;

		$this->prepareJSON($input, $price);
	}

	public function validate() {
		$model = $this->getModel();

		$input = new stdClass();
		$input->post = $_POST;
		$input->get = $_GET;
		$model->saveServiceLog($input);

		switch($model->validateData()) {
			case 1:
				$this->prepareJSON($input, null, false, 'Data gagal disimpan');
				break;
			case 2:
				$this->prepareJSON($input, null, true, 'Data sudah berhasil divalidasi tanpa revisi');
				break;
			case 3:
				$this->prepareJSON($input, null, true, 'Permintaan revisi sudah berhasil dikirim');
				break;
			case 4:
				$this->prepareJSON($input, null, 'failed', 'Sesi habis, silakan login ulang');
				break;
		}
	}

	public function getIntegrationMarkets() {
		$model		= $this->getModel();
		$markets	= $model->getIntegrationMarkets();

		if(!$this->input->get('debugdb')) {
			header('Content-type: application/json; charset=utf-8');
			echo json_encode($markets);
		}
		$this->app->close();
	}

	public function getIntegrationPrices() {
		$model		= $this->getModel();
		$prices		= $model->getIntegrationPrices();

		if(!$this->input->get('debugdb')) {
			header('Content-type: application/json; charset=utf-8');
			echo json_encode($prices);
		}
		$this->app->close();
	}

	public function checkVersion() {
		$model = $this->getModel();
		$input = new stdClass();
		$input->post = $_POST;
		$input->get = $_GET;
		$model->saveServiceLog($input);

		// Ignore if not live
		if(!GTHelper::isLive()) {
			$this->prepareJSON($input, null, true);
		}

		$curVersion = $this->input->get('version');

		$dom = new DOMDocument();
		libxml_use_internal_errors(true);
		$dom->loadHTMLFile('https://play.google.com/store/apps/details?id=com.gamatechno.egov.pihps.capturingdata');
		$xpath = new DOMXPath($dom);
		$query = '//div[@itemprop="softwareVersion"]';
		$entry = $xpath->query($query)->item(0);
		$lastVersion = trim($entry->nodeValue);
		if($lastVersion && $lastVersion != $curVersion) {
			$this->prepareJSON($input, null, false);
		} else {
			$this->prepareJSON($input, null, true);
		}
	}
}
