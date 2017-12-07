<?php

/**
 * @package     GT Component
 * @author      Yudhistira Ramadhan
 * @link        http://gt.web.id
 * @license     GNU/GPL
 * @copyright   Copyright (C) 2012 GtWeb Gamatechno. All Rights Reserved.
 */
defined('_JEXEC') or die;

class GTPIHPSSurveyModelService extends GTModelAdmin{
	
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

	public function sendToFirebase($tokens, $message, $user_type = 'surveyor', $printed = false) {
		$tokens = is_array($tokens) ? $tokens : array($tokens);
		$tokens = array_filter($tokens);

		if(!count($tokens)) return true;

		if(GTHelper::isLive()) {
			$accessKey = 'AIzaSyBJOMP-RTFgycrnlb2pbNXdFWHnhP41B40';
		} else {
			$accessKey = 'AAAA6OzqG-U:APA91bHwQ_iGV0OV_ilO6iu5v8a7mb2K6ccvaCwMAfOr2d4nvFObrP26a7wZB8Q7E6-K86OThyVxX-0rSXnoF8kFyxebDtlnOLrgp_pVyRXwHob0CnpRR3RIqACwGaBS33LUHjr5UiA0';
		}

		define( 'API_ACCESS_KEY', $accessKey);

		$msg = array(
			'body'		=> $message,
			'click_action' => 'NOTIF'.strtoupper($user_type), //NOTIFVALIDATOR
			'title'		=> JText::_('COM_GTPIHPSSURVEY_FIREBASE_TITLE'),
			'vibrate'	=> 1,
			'sound'		=> 2
		);

		$fields = array(
			'registration_ids'	=> $tokens,
			'notification'		=> $msg,
			'data'				=> array('type' => $user_type)
		);

		$headers = array(
			'Authorization: key=' . API_ACCESS_KEY,
			'Content-Type: application/json'
		);

		$ch = curl_init();
		curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );
		curl_setopt( $ch,CURLOPT_POST, true );
		curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers);
		curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
		$result = curl_exec($ch);
		curl_close( $ch );
		
		if ($printed) {
			echo $result;
		}

		return true;
	}

	public function getUser($user_id) {
		// Get a db connection.
		$db = $this->_db;

		// Create a new query object.
		$query = $db->getQuery(true);

		$query->select('a.*');
		$query->from($db->quoteName('#__gtpihpssurvey_users', 'a'));
		
		$query->where($db->quoteName('a.published') . ' = 1');
		$query->where($db->quoteName('a.user_id') . ' = '.$db->quote($user_id));

		$db->setQuery($query);
		if($this->input->get('debugdb')) {
			echo '<strong>getUser</strong></br>=================================================';
			echo nl2br(str_replace('#__','pihpsnas_',$query)).'<br/><br/>';
		}

		//echo nl2br(str_replace('#__','eburo_',$query)); die;
		return $db->loadObject();
	}

	public function getToken($user_id) {
		$user = $this->getUser($user_id);
		return $user->token;
	}

	public function getTokens($market_id, $type = 'surveyor') {
		// Get a db connection.
		$db = $this->_db;

		// Create a new query object.
		$query = $db->getQuery(true);

		$query->select($db->quoteName('a.token'));
		$query->from($db->quoteName('#__gtpihpssurvey_users', 'a'));
		$query->join('INNER', $db->quoteName('#__gtpihpssurvey_ref_regions', 'b').' ON '.
			$db->quoteName('a.region_id').' = '.$db->quoteName('b.id')
		);
		$query->join('INNER', $db->quoteName('#__gtpihpssurvey_ref_markets', 'c').' ON '.
			'IF('.$db->quoteName('a.market_ids').' IS NULL, '.
				'FIND_IN_SET('.$db->quoteName('c.regency_id').', '.$db->quoteName('b.regency_ids').') ,'.
				'FIND_IN_SET('.$db->quoteName('c.id').', '.$db->quoteName('a.market_ids').')'.
			')'
		);
		
		$query->where($db->quoteName('a.token') . ' IS NOT NULL');
		$query->where($db->quoteName('a.published') . ' = 1');
		$query->where($db->quoteName('a.type') . ' = '.$db->quote($type));
		$query->where($db->quoteName('c.id') . ' = '.$db->quote($market_id));

		$db->setQuery($query);
		if($this->input->get('debugdb')) {
			echo '<strong>getUser</strong></br>=================================================';
			echo nl2br(str_replace('#__','pihpsnas_',$query)).'<br/><br/>';
		}

		//echo nl2br(str_replace('#__','eburo_',$query)); die;
		return $db->loadColumn();
	}

	public function updateUser($userdata, $password, $phone = null) {
		$user		= JUser::getInstance($userdata->id);
		$surveyUser	= $this->searchItem('user', array('user_id' => $userdata->id));
		$return		= new stdClass();

		// Get the log in credentials.
		$credentials				= array();
		$credentials['username']	= $user->username;
		$credentials['password']	= $password;

		// Get the global JAuthentication object.
		jimport('joomla.user.authentication');

		$authenticate	= JAuthentication::getInstance();
		$response		= $authenticate->authenticate($credentials, array('remember' => false, 'return' => null));
		$loggedUser		= JFactory::getUser($response->username);

		$authenticated	= $response->status === JAuthentication::STATUS_SUCCESS;
		$authenticated	= $authenticated && $user->id == $loggedUser->id;
		$authenticated	= $authenticated && $surveyUser->published == 1;

		if(!$authenticated) {
			if(!$surveyUser->published == 1) {
				$return->status		= false;
				$return->message	= 'Login gagal, user tidak aktif';
				return $return;
			} else {
				$return->status		= false;
				$return->message	= 'Password yang digunakan salah';
				return $return;
			}
		}

		if(@$userdata->password !== @$userdata->password2) {
			$return->status		= false;
			$return->message	= 'Kombinasi password dan konfirmasi password tidak sesuai';
			return $return;
		}

		$userdata = JArrayHelper::fromObject($userdata);
		// Bind the data.
		if (!$user->bind($userdata)) {
			$return->status		= false;
			$return->message	= $user->getError();
			return $return;
		}
		// Store the data.
		if (!$user->save()) {
			$return->status		= false;
			$return->message	= $user->getError();
			return $return;
		}

		if($phone) {
			$surveyUser->phone = $phone;
			if(!parent::saveExternal($surveyUser, 'user_item')) {
				$return->status		= false;
				$return->message	= $this->getError();
				return $return;
			}
		}

		$return->status		= true;
		$return->message	= 'Data pengguna telah berhasil disimpan';
		return $return;
	}

	public function updateToken($id, $token) {
		$data			= new stdClass();
		$data->id		= $id;
		$data->token	= $token;
		return $this->saveExternal($data, 'profile');
	}

	public function getReferencesByUser($user_id, $prepare = true) {
		// Get a db connection.
		$db = $this->_db;

		// Create a new query object.
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('a.id', 'a.regency_id', 'a.market_id', 'a.name')));
		$query->from($db->quoteName('#__gtpihpssurvey_ref_sellers', 'a'));

		$query->select($db->quoteName('b.name', 'market'));
		$query->join('INNER', $db->quoteName('#__gtpihpssurvey_ref_markets', 'b').' ON '.
			$db->quoteName('a.market_id').' = '.$db->quoteName('b.id')
		);

		$query->select($db->quoteName('c.name', 'regency'));
		$query->join('INNER', $db->quoteName('#__gtpihpssurvey_ref_regencies', 'c').' ON '.
			$db->quoteName('a.regency_id').' = '.$db->quoteName('c.id')
		);

		$query->select($db->quoteName('d.name', 'type'));
		$query->join('INNER', $db->quoteName('#__gtpihpssurvey_ref_price_types', 'd').' ON '.
			$db->quoteName('b.price_type_id').' = '.$db->quoteName('d.id')
		);

		$query->join('INNER', $db->quoteName('#__gtpihpssurvey_ref_regions', 'f').' ON '.
			$db->quoteName('a.province_id').' = '.$db->quoteName('f.province_id').' AND '.
			'IF(LENGTH(f.regency_ids) > 0, FIND_IN_SET('.$db->quoteName('a.regency_id').', '.$db->quoteName('f.regency_ids').'), TRUE)'
		);

		$query->join('INNER', $db->quoteName('#__gtpihpssurvey_users', 'e').' ON '.
			$db->quoteName('f.id').' = '.$db->quoteName('e.region_id').' AND '.
			'IF(LENGTH(e.market_ids) > 0, FIND_IN_SET('.$db->quoteName('a.market_id').', '.$db->quoteName('e.market_ids').'), TRUE) AND '.
			'IF(LENGTH(e.seller_ids) > 0 AND b.price_type_id = 1, FIND_IN_SET('.$db->quoteName('a.id').', '.$db->quoteName('e.seller_ids').'), TRUE)'
		);
		
		$query->where($db->quoteName('a.published') . ' = 1');
		$query->where($db->quoteName('b.published') . ' = 1');
		$query->where($db->quoteName('c.published') . ' = 1');
		$query->where($db->quoteName('d.published') . ' = 1');
		$query->where($db->quoteName('e.published') . ' = 1');

		$query->where($db->quoteName('e.user_id') . ' = '.$db->quote($user_id));
		$query->order($db->quoteName('b.price_type_id'));
		$query->order($db->quoteName('b.name'));
		$query->order($db->quoteName('a.name'));

		$db->setQuery($query);
		if($this->input->get('debugdb')) {
			echo '<strong>getReferencesByUser</strong></br>=================================================';
			echo nl2br(str_replace('#__','pihpsnas_',$query)).'<br/><br/>';
		}

		//echo nl2br(str_replace('#__','eburo_',$query)); die;
		
		$data = $db->loadObjectList();

		if(!$prepare) {
			return $data;
		}

		$cities = array();
		$markets = array();
		$sellers = array();
		foreach ($data as $item) {
			$city = new stdClass();
			$city->id = $item->regency_id;
			$city->name = $item->regency;
			$cities[$item->regency_id] = $city;

			$market = new stdClass();
			$market->id = $item->market_id;
			$market->name = $item->market;
			$market->type = $item->type;
			$markets[$item->regency_id][$item->market_id] = $market;

			$seller = new stdClass();
			$seller->id = $item->id;
			$seller->name = $item->name;
			$sellers[$item->market_id][$item->id] = $seller;
		}

		$cities = array_values($cities);
		foreach ($cities as &$city) {
			$city_markets = array_values((array) @$markets[$city->id]);
			foreach ($city_markets as &$market) {
				$market->sellers = array_values((array) @$sellers[$market->id]);
			}

			$city->markets = $city_markets;
		}

		return $cities;
	}

	public function getPrices($show_pending = true) {
		// Get a db connection.
		$db = $this->_db;

		// Create a new query object.
		$query = $db->getQuery(true);

		$user_id	= $this->input->get('user_id', '', 'int');
		$limit 		= 20;
		$offset 	= $this->input->get('offset', 0, 'int');

		$query->select($db->quoteName(array('a.id', 'a.price_type_id', 'a.market_id')));
		$query->from($db->quoteName('#__gtpihpssurvey_prices', 'a'));

		$query->select($db->quoteName('b.name', 'market'));
		$query->join('INNER', $db->quoteName('#__gtpihpssurvey_ref_markets', 'b').' ON '.
			$db->quoteName('a.market_id').' = '.$db->quoteName('b.id')
		);

		$query->select($db->quoteName('a.created_by', 'user_id'));

		$query->select($db->quoteName(array('a.status', 'a.date', 'a.created', 'a.modified')));
		
		$query->where($db->quoteName('a.created_by') . ' = '.$db->quote($user_id));
		$query->where($db->quoteName('a.published') . ' = 1');
		if(!$show_pending) {
			$query->where($db->quoteName('a.status') . ' != '.$db->quote('pending'));
		}

		$query->group($db->quoteName('a.id'));
		$query->order($db->quoteName('a.modified') . ' desc');
		$query->setLimit($limit, $offset * $limit);

		$db->setQuery($query);
		if($this->input->get('debugdb')) {
			echo '<strong>getPrices</strong></br>=================================================';
			echo nl2br(str_replace('#__','pihpsnas_',$query)).'<br/><br/>';
		}

		//echo nl2br(str_replace('#__','eburo_',$query)); die;
		return $db->loadObjectList();
	}

	public function getMarket() {
		$market_id	= $this->input->get('market_id', '', 'INT');
		$market = $this->getItemExternal($market_id, 'ref_market', false);

		return $market;
	}

	public function getPrice($latest = false) {
		$user_id	= $this->input->get('user_id', '', 'INT');
		$market_id	= $this->input->get('market_id', '', 'INT');
		$date		= $this->input->get('date');

		// Get a db connection.
		$db = $this->_db;

		// Create a new query object.
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('a.id', 'a.regency_id', 'a.market_id', 'a.status', 'a.message')));
		$query->select($db->quoteName('a.created_by', 'user_id'));
		$query->from($db->quoteName('#__gtpihpssurvey_prices', 'a'));

		$query->select($db->quoteName('b.name', 'type'));
		$query->join('INNER', $db->quoteName('#__gtpihpssurvey_ref_price_types', 'b').' ON '.
			$db->quoteName('a.price_type_id').' = '.$db->quoteName('b.id')
		);
		
		$query->where($db->quoteName('a.published') . ' = 1');
		/*if($user_id) {
			$query->where($db->quoteName('a.created_by') . ' = '.$db->quote($user_id));
		}*/
		$query->where($db->quoteName('a.market_id') . ' = '.$db->quote($market_id));
		if($latest) {
			$query->where($db->quoteName('a.status') . ' = '.$db->quote('approved'));
			$query->where($db->quoteName('a.date') . ' < '.$db->quote($date));
		} else {
			$query->where($db->quoteName('a.date') . ' = '.$db->quote($date));
		}
		
		$query->order($db->quoteName('a.id') . ' desc');
		$query->setLimit(1);

		$db->setQuery($query);
		if($this->input->get('debugdb')) {
			echo '<strong>getPrice</strong></br>=================================================';
			echo nl2br(str_replace('#__','pihpsnas_',$query)).'<br/><br/>';
		}

		//echo nl2br(str_replace('#__','eburo_',$query)); die;
		return $db->loadObject();
	}

	public function getPriceDetail($price_id) {
		// Get a db connection.
		$db = $this->_db;

		// Create a new query object.
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('a.seller_id', 'a.commodity_id', 'a.price', 'a.is_revision')));
		$query->from($db->quoteName('#__gtpihpssurvey_price_details', 'a'));
		
		$query->where($db->quoteName('a.price_id') . ' = '.$db->quote($price_id));

		$db->setQuery($query);
		if($this->input->get('debugdb')) {
			echo '<strong>getPriceDetail</strong></br>=================================================';
			echo nl2br(str_replace('#__','pihpsnas_',$query)).'<br/><br/>';
		}

		//echo nl2br(str_replace('#__','eburo_',$query)); die;
		$items = $db->loadObjectList();

		$prices = array();
		foreach ($items as $item) {
			$prices[$item->seller_id][$item->commodity_id] = $item;
		}

		return $prices;
	}

	public function getSellers() {
		$user_id	= $this->input->get('user_id', '', 'INT');
		$market_id	= $this->input->get('market_id', '', 'INT');

		// Get a db connection.
		$db = $this->_db;

		// Create a new query object.
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('a.id', 'a.name')));
		$query->select($db->quoteName('a.commodity_ids', 'commodities'));

		$query->join('INNER', $db->quoteName('#__gtpihpssurvey_ref_regions', 'g').' ON '.
			$db->quoteName('a.province_id').' = '.$db->quoteName('g.province_id').' AND '.
			'IF(LENGTH(g.regency_ids) > 0, FIND_IN_SET('.$db->quoteName('a.regency_id').', '.$db->quoteName('g.regency_ids').'), TRUE)'
		);

		$query->join('INNER', $db->quoteName('#__gtpihpssurvey_ref_markets', 'f').' ON '.
			$db->quoteName('a.province_id').' = '.$db->quoteName('f.province_id').' AND '.
			$db->quoteName('a.regency_id').' = '.$db->quoteName('f.regency_id').' AND '.
			$db->quoteName('a.market_id').' = '.$db->quoteName('f.id')
		);

		$query->join('INNER', $db->quoteName('#__gtpihpssurvey_users', 'e').' ON '.
			$db->quoteName('g.id').' = '.$db->quoteName('e.region_id').' AND '.
			'IF(LENGTH(e.market_ids) > 0, FIND_IN_SET('.$db->quoteName('a.market_id').', '.$db->quoteName('e.market_ids').'), TRUE) AND '.
			'IF(LENGTH(e.seller_ids) > 0 AND f.price_type_id = 1, FIND_IN_SET('.$db->quoteName('a.id').', '.$db->quoteName('e.seller_ids').'), TRUE)'
		);
		
		$query->from($db->quoteName('#__gtpihpssurvey_ref_sellers', 'a'));
		
		$query->where($db->quoteName('a.published') . ' = 1');
		$query->where($db->quoteName('a.market_id') . ' = '.$db->quote($market_id));
		$query->where($db->quoteName('e.user_id') . ' = '.$db->quote($user_id));
		$query->group($db->quoteName('a.id'));

		$query->order($db->quoteName('f.price_type_id'));
		$query->order($db->quoteName('f.name'));
		$query->order($db->quoteName('a.name'));

		//echo nl2br(str_replace('#__','eburo_',$query)); die;

		$db->setQuery($query);
		if($this->input->get('debugdb')) {
			echo '<strong>getSellers</strong></br>=================================================';
			echo nl2br(str_replace('#__','pihpsnas_',$query)).'<br/><br/>';
		}
		return $db->loadObjectList();
	}

	public function getCommodityCategories() {
		// Get a db connection.
		$db = $this->_db;

		// Create a new query object.
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('a.id', 'a.parent_id', 'a.name')));
		$query->from($db->quoteName('#__gtpihpssurvey_ref_categories', 'a'));
		
		$query->where($db->quoteName('a.published') . ' = 1');
		
		$db->setQuery($query);
		if($this->input->get('debugdb')) {
			echo '<strong>getCommodityCategories</strong></br>=================================================';
			echo nl2br(str_replace('#__','pihpsnas_',$query)).'<br/><br/>';
		}

		$raw = $db->loadObjectList();
		$data = array();
		foreach ($raw as $item) {
			$data[$item->parent_id][$item->id] = $item->name;
		}
		return $data;
	}

	public function getCommodities() {
		// Get a db connection.
		$db = $this->_db;

		// Create a new query object.
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('a.name', 'a.denomination', 'b.min_digit', 'b.max_digit', 'a.id', 'a.category_id')));		
		$query->from($db->quoteName('#__gtpihpssurvey_ref_commodities', 'a'));
		$query->join('INNER', $db->quoteName('#__gtpihpssurvey_ref_categories', 'b').' ON '.
			$db->quoteName('a.category_id').' = '.$db->quoteName('b.id')
		);
		$query->where($db->quoteName('a.published') . ' = 1');

		$db->setQuery($query);
		if($this->input->get('debugdb')) {
			echo '<strong>getCommodities</strong></br>=================================================';
			echo nl2br(str_replace('#__','pihpsnas_',$query)).'<br/><br/>';
		}

		return $db->loadObjectList();
	}

	public function prepareCommodities($categories, $commodities, $selected) {
		$coms = array();
		foreach ($commodities as $com) {
			if(!in_array($com->id, $selected)) {
				continue;
			}
			$coms[$com->category_id][$com->id] = implode(':', JArrayHelper::fromObject($com));
		}

		$data			= GTHelperHtml::setCommodities($categories[0], $categories, $coms);
		$commodities	= array();
		foreach ($data as &$item) {
			if(!$item->text) continue;
			list($name, $denom, $min_digit, $max_digit)	= explode(':', $item->text.'::::');
			$min_digit	= intval($min_digit);
			$max_digit	= intval($max_digit);

			$commodity				= new stdClass();
			$commodity->id			= is_numeric($item->value) ? $item->value : '';
			$commodity->name		= str_replace('&nbsp;', '', $name);
			$commodity->denom		= $denom;
			$commodity->type		= is_numeric($item->value) ? 'commodity' : 'category';
			$commodity->level		= substr_count($name, str_repeat('&nbsp;', 4));
			$commodity->min_digit	= $min_digit;
			$commodity->max_digit	= $max_digit;

			$commodities[]		= $commodity;
		}

		//echo "<pre>"; print_r($commodities); echo "</pre>"; die;
		return $commodities;
	}

	public function getDetailIDs($price_id) {
		// Get a db connection.
		$db = $this->_db;

		// Create a new query object.
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('a.id', 'a.price_id', 'a.seller_id', 'a.commodity_id')));
		$query->from($db->quoteName('#__gtpihpssurvey_price_details', 'a'));

		$query->where($db->quoteName('a.price_id') . ' = ' . $db->quote($price_id));

		$db->setQuery($query);
		if($this->input->get('debugdb')) {
			echo '<strong>getDetailIDs</strong></br>=================================================';
			echo nl2br(str_replace('#__','pihpsnas_',$query)).'<br/><br/>';
		}
		$data = $db->loadObjectList();
		$result = array();
		foreach($data as &$item) {
			$result[$item->seller_id][$item->commodity_id] = $item->id;
		}

		return $result;
	}

	public function submit() {
		$id			= $this->input->get('id', '', 'int');
		$user_id	= $this->input->get('user_id', '', 'int');
		$market_id	= $this->input->get('market_id', '', 'int');
		$prices		= $this->input->get('prices', array(), 'array');
		$date		= $this->input->get('date');
		$date		= JHtml::date($date, 'Y-m-d');
		$location	= $this->input->get('location', '', 'raw');
		$token		= $this->input->get('token', '', 'raw');
		$market 	= $this->getItemExternal($market_id, 'ref_market');

		$count_prices = call_user_func_array('array_merge', $prices);
		if(!count(array_filter($count_prices)) > 0) {
			return 2;
		}

		if(!$market->id) {
			return 2;
		}

		$user = $this->getUser($user_id);

		/*if(@$user->token !== $token) {
			return 3;
		}*/

		if($id > 0) {
			$price = $this->searchItem('price', array(
				'id' => $id
			));
		} else {
			$price = $this->searchItem('price', array(
				'market_id' => $market_id,
				'created_by' => $user_id,
				'date' => $date
			));
		}

		$price->id				= intval(@$price->id);
		$price->price_type_id	= $market->price_type_id;
		$price->province_id		= $market->province_id;
		$price->regency_id		= $market->regency_id;
		$price->market_id		= $market_id;
		$price->date			= $date;
		$price->status			= @$user->type == 'validator' ? 'approved' : 'pending';

		if(!$id) {
			$price->location	= $location;
			$price->created_by	= $user_id;
		} else {
			$price->modified_by	= $user_id;
		}

		$oldData = $this->getItemExternal($id, 'price', false);
		if(@$oldData->is_review) {
			$price->modified = $oldData->modified;
			$price->modified_by = $oldData->modified_by;
		}

		$price_id	= $this->saveExternal($price, 'price', true);
		if(!$price_id > 0) {
			return 2;
		}

		$detail_ids	= $this->getDetailIDs($id);
		$details 	= array();
		foreach ($prices as $seller_id => $commodities) {
			foreach ($commodities as $commodity_id => $comPrice) {
				if($comPrice < 50) continue;

				$detail					= new stdClass();
				$detail->id				= intval(@$detail_ids[$seller_id][$commodity_id]);
				$detail->price_id		= $price_id;
				$detail->seller_id		= $seller_id;
				$detail->commodity_id	= $commodity_id;
				$detail->price			= $comPrice;
				$detail->is_revision	= 0;
				$details[]				= $detail;
				
				unset($detail_ids[$seller_id][$commodity_id]);
			}
		}

		if(count($detail_ids) > 0) {
			$detail_ids = call_user_func_array('array_merge', $detail_ids);
			$this->deleteExternal($detail_ids, 'price_detail');
		}

		$this->saveBulk($details, 'price_detail', true, true);

		// Notify Validator
		$user	= $this->getUser($user_id);
		$tokens	= $this->getTokens($market_id);

		if(($tokenKey = array_search($user->token, $tokens)) !== false) {
			unset($tokens[$tokenKey]);
		}

		$market	= $this->getItemExternal($market_id, 'ref_market');
		$date	= JHtml::date($date, 'j M Y');
		$msg	= $price->id > 0 ? JText::_('COM_GTPIHPSSURVEY_FIREBASE_SUBMIT_NEW') : JText::_('COM_GTPIHPSSURVEY_FIREBASE_SUBMIT_EDIT');
		$msg	= sprintf($msg, $user->name, $market->name, $date);

		// Send Notification to Firebase
		$this->sendToFirebase($tokens, $msg, 'validator');

		return 1;
	}

	public function deletePrice() {
		$id	= $this->input->get('id', '', 'INT');
		
		// Get a db connection.
		$db = $this->_db;

		// Delete Price
		$query = $db->getQuery(true);
		/*
		$query->delete($db->quoteName('#__gtpihpssurvey_prices'));
		$query->where($db->quoteName('id').' = '.$db->quote($id));

		$db->setQuery($query)->execute();

		// Delete Price Detail
		$query = $db->getQuery(true);

		$query->delete($db->quoteName('#__gtpihpssurvey_price_details'));
		$query->where($db->quoteName('price_id').' = '.$db->quote($id));

		$db->setQuery($query)->execute();
		*/

		$price					= new stdClass();
		$price->id				= $id;
		$price->published		= 0;
		$price->modified_by		= $user_id;
		
		return $this->saveExternal($price, 'price');
	}

	public function saveServiceLog($input, $output = null) {
		
		if(!$output) {
			return false;
		}

		$post	= @$input->post;
		$get	= @$input->get;
		$task	= @$post['task'] ? $post['task'] : @$get['task'];

		unset($post['option']);
		unset($post['task']);
		unset($get['option']);
		unset($get['task']);

		//$serviceLog						= $this->searchItem('service_log', array('name' => $task));
		//$serviceLog->id 				= intval(@$serviceLog->id);
		$serviceLog 					= new stdClass();
		$serviceLog->id 				= 0;
		$serviceLog->name				= $task;
		$serviceLog->input_get			= count($get) > 0 ? GTHelper::httpQuery($get, true) : null;
		$serviceLog->input_post			= count($post) > 0 ? GTHelper::httpQuery($post, true) : null;
		$serviceLog->input_get_json		= count($get) > 0 ? json_encode($get) : null;
		$serviceLog->input_post_json	= count($post) > 0 ? json_encode($post) : null;
		$serviceLog->output				= count($output) > 0 ? json_encode($output) : null;

		return $this->saveExternal($serviceLog, 'service_log');
		
		return true;
	}

	public function getSurvey() {
		$id = $this->input->get('id', 0, 'int');

		// Get a db connection.
		$db = $this->_db;

		// Create a new query object.
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('a.id', 'a.market_id')));
		$query->from($db->quoteName('#__gtpihpssurvey_prices', 'a'));

		$query->select($db->quoteName('b.name', 'market'));
		$query->join('INNER', $db->quoteName('#__gtpihpssurvey_ref_markets', 'b').' ON '.
			$db->quoteName('a.market_id').' = '.$db->quoteName('b.id')
		);

		$query->select($db->quoteName('a.created_by', 'surveyor_id'));
		$query->select($db->quoteName('c.name', 'surveyor'));
		$query->select($db->quoteName('c.email', 'surveyor_email'));
		$query->join('INNER', $db->quoteName('#__users', 'c').' ON '.
			$db->quoteName('a.created_by').' = '.$db->quoteName('c.id')
		);

		$query->select($db->quoteName('d.phone', 'surveyor_phone'));
		$query->join('INNER', $db->quoteName('#__gtpihpssurvey_users', 'd').' ON '.
			$db->quoteName('a.created_by').' = '.$db->quoteName('d.user_id')
		);

		$query->select($db->quoteName(array('a.status', 'a.date', 'a.created', 'a.modified')));
		
		$query->where($db->quoteName('a.published') . ' = 1');
		$query->where($db->quoteName('d.published') . ' = 1');
		$query->where($db->quoteName('a.id') . ' = '.$db->quote($id));

		$db->setQuery($query);
		if($this->input->get('debugdb')) {
			echo '<strong>getSurvey</strong></br>=================================================';
			echo nl2br(str_replace('#__','pihpsnas_',$query)).'<br/><br/>';
		}

		//echo nl2br(str_replace('#__','eburo_',$query)); die;
		$result = $db->loadObject();
		return $result ? $result : new stdClass();
	}

	public function getSurveys() {
		// Get a db connection.
		$db = $this->_db;

		// Create a new query object.
		$query = $db->getQuery(true);

		$user_id	= $this->input->get('user_id', '', 'int');
		$regency_id	= $this->input->get('regency_id', '', 'int');
		$date		= $this->input->get('date');
		$status		= $this->input->get('status');
		$limit 		= 20;
		$offset 	= $this->input->get('offset', '', 'int');

		$refs		= $this->getReferencesByUser($user_id, false);
		$city_ids	= array(0);
		$market_ids	= array(0);
		foreach ($refs as $ref) {
			$city_ids[$ref->regency_id]		= $ref->regency_id;
			$market_ids[$ref->market_id]	= $ref->market_id;
		}
		$city_ids	= implode(',', array_map(array($db, 'quote'), $city_ids));
		$market_ids	= implode(',', array_map(array($db, 'quote'), $market_ids));

		$query->select($db->quoteName(array('a.id', 'a.market_id')));
		$query->from($db->quoteName('#__gtpihpssurvey_prices', 'a'));

		$query->select($db->quoteName('b.name', 'market'));
		$query->join('INNER', $db->quoteName('#__gtpihpssurvey_ref_markets', 'b').' ON '.
			$db->quoteName('a.market_id').' = '.$db->quoteName('b.id')
		);

		$query->select($db->quoteName('e.name', 'type'));
		$query->join('INNER', $db->quoteName('#__gtpihpssurvey_ref_price_types', 'e').' ON '.
			$db->quoteName('a.price_type_id').' = '.$db->quoteName('e.id')
		);

		$query->select($db->quoteName('a.created_by', 'surveyor_id'));
		$query->select($db->quoteName('c.name', 'surveyor'));
		$query->select($db->quoteName('c.email', 'surveyor_email'));
		$query->join('INNER', $db->quoteName('#__users', 'c').' ON '.
			$db->quoteName('a.created_by').' = '.$db->quoteName('c.id')
		);

		$query->select($db->quoteName('d.phone', 'surveyor_phone'));
		$query->join('INNER', $db->quoteName('#__gtpihpssurvey_users', 'd').' ON '.
			$db->quoteName('a.created_by').' = '.$db->quoteName('d.user_id')
		);

		$query->select($db->quoteName(array('a.status', 'a.date', 'a.created', 'a.modified')));
		
		$query->where($db->quoteName('a.published') . ' = 1');
		$query->where($db->quoteName('d.published') . ' = 1');
		$query->where($db->quoteName('a.regency_id') . ' IN ('.$city_ids.')');
		$query->where($db->quoteName('a.market_id') . ' IN ('.$market_ids.')');


		if($date && !$status) {
			$query->where($query->quoteName('a.date') . ' = '.$query->quote($date));
		}

		if($status) {
			$query->where($db->quoteName('a.status') . ' = '.$db->quote($status));
		}

		if($regency_id) {
			$query->where($db->quoteName('a.regency_id') . ' = '.$db->quote($regency_id));
		}

		$query->order('IF('.$db->quoteName('a.modified').', '.$db->quoteName('a.modified').', '.$db->quoteName('a.created').') desc');
		if(in_array($status, array('pending', 'revision'))) {
			$query->order($db->quoteName('a.date').' asc');
		}

		if($date) {
			$query->order($db->quoteName('a.price_type_id').' asc');
		}
		
		$query->group($db->quoteName('a.id'));

		if(!$date) {
			$query->setLimit($limit, $offset * $limit);
		}
		
		$db->setQuery($query);
		if($this->input->get('debugdb')) {
			echo '<strong>getSurveys</strong></br>=================================================';
			echo nl2br(str_replace('#__','pihpsnas_',$query)).'<br/><br/>';
		}

		//echo nl2br(str_replace('#__','eburo_',$query)); die;
		return $db->loadObjectList();
	}

	public function validateData() {
		$id			= $this->input->get('id', '', 'int');
		$id 		= intval($id);
		$user_id	= $this->input->get('user_id', '', 'int');
		$prices		= $this->input->get('prices', array(), 'array');
		$status		= $this->input->get('status');
		$token		= $this->input->get('token', '', 'raw');
		$message	= $this->input->get('message', '', 'raw');
		if(!$id) {
			return 1;
		}

		$user = $this->getUser($user_id);
		
		/*if(@$user->token !== $token) {
			return 4;
		}*/

		$oldData = $this->getItemExternal($id, 'price', false);

		$price					= new stdClass();
		$price->id				= $id;
		$price->status			= $status;
		$price->modified_by		= $user_id;

		if(@$oldData->is_review) {
			$price->modified = $oldData->modified;
			$price->modified_by = $oldData->modified_by;
		}

		if($message) {
			$price->message = $message;
		}
		
		$price_id	= $this->saveExternal($price, 'price', true);

		// Notify Surveyor
		$price	= $this->getItemExternal($price_id, 'price', false);
		$token	= $this->getToken($price->created_by);
		$market	= $this->getItemExternal(0, 'ref_market');
		$date	= JHtml::date($price->date, 'j M Y');
		$msg	= $status == 'approved' ? JText::_('COM_GTPIHPSSURVEY_FIREBASE_VALIDATE_APPROVED') : JText::_('COM_GTPIHPSSURVEY_FIREBASE_VALIDATE_REVISION');
		$msg	= sprintf($msg, $user->name, $market->name, $date);

		// Send Notification to Firebase
		$this->sendToFirebase($token, $msg, 'surveyor');

		if(!$price_id > 0) {
			return 1;
		}

		if($status !== 'revision') {
			return 2;
		}

		$detail_ids	= $this->getDetailIDs($id);
		$details 	= array();
		foreach ($prices as $seller_id => $commodities) {
			foreach ($commodities as $commodity_id => $is_revision) {
				$detail_id 				= intval(@$detail_ids[$seller_id][$commodity_id]);
				if(!$detail_id) continue;

				$detail					= new stdClass();
				$detail->id				= $detail_id;
				$detail->is_revision 	= $is_revision;
				$details[]				= $detail;
			}
		}

		$this->saveBulk($details, 'price_detail', false, true);

		return 3;
	}

	public function getIntegrationMarkets() {
		$province_id = $this->input->get('province_id');

		// Get a db connection.
		$db = $this->_db;

		// Create a new query object.
		$query = $db->getQuery(true);

		$query->select($db->quoteName('a.id', 'market_id'));
		$query->select($db->quoteName('a.name', 'market_desc'));
		$query->select($db->quoteName('a.price_type_id'));
		$query->from($db->quoteName('#__gtpihpssurvey_ref_markets', 'a'));

		$query->join('INNER', $db->quoteName('#__gtpihpssurvey_ref_sellers', 'b').' ON '.
			$db->quoteName('a.id').' = '.$db->quoteName('b.market_id')
		);

		$query->select($db->quoteName('c.id', 'region_id'));
		$query->select($db->quoteName('c.long_name', 'region_desc'));
		$query->join('INNER', $db->quoteName('#__gtpihpssurvey_ref_regencies', 'c').' ON '.
			$db->quoteName('a.regency_id').' = '.$db->quoteName('c.id')
		);

		if($province_id) {
			$query->join('INNER', $db->quoteName('#__gtpihpssurvey_ref_provinces', 'd').' ON '.
				$db->quoteName('a.province_id').' = '.$db->quoteName('d.id')
			);
			$query->where($db->quoteName('d.source_id') . ' = '.$db->quote($province_id));
		}
		
		
		$query->group('a.id');

		$query->where($db->quoteName('a.published') . ' = '.$db->quote(1));
		//$query->where($db->quoteName('a.price_type_id') . ' = '.$db->quote('1'));

		$db->setQuery($query);
		if($this->input->get('debugdb')) {
			echo '<strong>getIntegrationMarkets</strong></br>=================================================';
			echo nl2br(str_replace('#__','pihpsnas_',$query)).'<br/><br/>';
		}
		//echo nl2br(str_replace('#__','eburo_',$query)); die;
		
		return $db->loadObjectList();
	}

	public function getIntegrationPrices() {
		$date = $this->input->get('period');
		$province_id = $this->input->get('province_id');

		// Get a db connection.
		$db = $this->_db;

		// Create a new query object.
		$query = $db->getQuery(true);

		$query->select($db->quoteName('a.date'));
		$query->select('MAX('.$db->quoteName('a.created').') inputted');
		$query->select('MAX('.$db->quoteName('a.modified').') validated');
		$query->from($db->quoteName('#__gtpihpssurvey_prices', 'a'));

		$query->select('ROUND(AVG('.$db->quoteName('b.price').')/50)*50 price');
		$query->join('INNER', $db->quoteName('#__gtpihpssurvey_price_details', 'b').' ON '.
			$db->quoteName('a.id').' = '.$db->quoteName('b.price_id')
		);

		$query->select($db->quoteName('c.id', 'market_id'));
		$query->join('INNER', $db->quoteName('#__gtpihpssurvey_ref_markets', 'c').' ON '.
			$db->quoteName('a.market_id').' = '.$db->quoteName('c.id')
		);

		$query->select('SUBSTRING_INDEX('.$db->quoteName('d.source_id').', "-", 1) commodity_id');
		$query->select('SUBSTRING_INDEX('.$db->quoteName('d.source_id').', "-", -1) quality_id');
		$query->join('INNER', $db->quoteName('#__gtpihpssurvey_ref_commodities', 'd').' ON '.
			$db->quoteName('b.commodity_id').' = '.$db->quoteName('d.id')
		);

		$query->join('INNER', $db->quoteName('#__gtpihpssurvey_ref_provinces', 'e').' ON '.
			$db->quoteName('a.province_id').' = '.$db->quoteName('e.id')
		);
		
		

		$query->group('a.date');
		$query->group('a.market_id');
		$query->group('b.commodity_id');

		$query->where($db->quoteName('a.status') . ' = '.$db->quote('approved'));
		$query->where($db->quoteName('e.source_id') . ' = '.$db->quote($province_id));
		$query->where($db->quoteName('a.date') . ' = '.$db->quote($date));
		$query->where($db->quoteName('b.price') . ' > 0');
		$query->where($db->quoteName('a.published') . ' = 1');
		//$query->where($db->quoteName('c.price_type_id') . ' = '.$db->quote('1'));

		$db->setQuery($query);
		if($this->input->get('debugdb')) {
			echo '<strong>getIntegrationPrices</strong></br>=================================================';
			echo nl2br(str_replace('#__','pihpsnas_',$query)).'<br/><br/>';
		}
		//echo nl2br(str_replace('#__','eburo_',$query)); die;
		
		$data = $db->loadObjectList();

		$items = array();
		foreach ($data as $item) {
			$items[$item->market_id]['market_id']	= $item->market_id;
			$items[$item->market_id]['date']		= $item->date;
			$items[$item->market_id]['inputted']	= $item->inputted;
			$items[$item->market_id]['validated']	= $item->validated;
			$items[$item->market_id]['details'][$item->commodity_id.'-'.$item->quality_id]['commodity_id']	= $item->commodity_id;
			$items[$item->market_id]['details'][$item->commodity_id.'-'.$item->quality_id]['quality_id']	= $item->quality_id;
			$items[$item->market_id]['details'][$item->commodity_id.'-'.$item->quality_id]['price']			= $item->price;
		}

		sort($items);
		foreach ($items as &$item) {
			sort($item['details']);
		}

		return $items;
	}

	
}
