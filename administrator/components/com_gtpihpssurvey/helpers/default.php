<?php

/**
 * @package		GT Component
 * @author		Yudhistira Ramadhan
 * @link		http://gt.web.id
 * @license		GNU/GPL
 * @copyright	Copyright (C) 2012 GtWeb Gamatechno. All Rights Reserved.
 */
// no direct access
defined('_JEXEC') or die('Restricted access');

class FakeObject {
	public function __call($method, $args) {
		if (isset($this->$method)) {
			$func = $this->$method;
			return call_user_func_array($func, $args);
		}
	}
}

class GTHelper {

	public static function getInfo() {
		$xml = JPATH_COMPONENT_ADMINISTRATOR . DS . 'manifest.xml';
		$xml = JApplicationHelper::parseXMLInstallFile($xml);

		$info = new stdClass();
		$info->name			= $xml['name'];
		$info->type			= $xml['type'];
		$info->creationDate	= $xml['creationdate'];
		$info->creationYear	= array_pop(explode(' ', $xml['creationdate']));
		$info->author		= $xml['author'];
		$info->copyright	= $xml['copyright'];
		$info->authorEmail	= $xml['authorEmail'];
		$info->authorUrl	= $xml['authorUrl'];
		$info->version		= $xml['version'];
		$info->description	= $xml['description'];

		return $info;
	}
	
	public static function pluralize($word) {
		$plural = array(
			array('/(x|ch|ss|sh)$/i', "$1es"),
			array('/([^aeiouy]|qu)y$/i', "$1ies"),
			array('/([^aeiouy]|qu)ies$/i', "$1y"),
			array('/(bu)s$/i', "$1ses"),
			array('/s$/i', "s"),
			array('/$/', "s"));

		// Check for matches using regular expressions
		foreach ($plural as $pattern)
		{
			if (preg_match($pattern[0], $word))
			{
				$word = preg_replace($pattern[0], $pattern[1], $word);
				break;
			}
		}
		return $word;
	}

	public static function recursive_ksort(&$array) {
	    foreach ($array as $k => $v) {
	        if (is_array($v)) {
	            self::recursive_ksort($v);
	        }
	    }
	    return ksort($array);
	}

	public static function getMenuId($url) {
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select('id')->from('#__menu')->where($db->quoteName('link') .' = '.$db->quote($url));

		$db->setQuery($query);
		return intval(@$db->loadObject()->id);
	}
	
	
	public static function addSubmenu($vName) {
		$submenus = array(
			'samples'
		);

		foreach ($submenus as $submenu) {
			JHtmlSidebar::addEntry(
				JText::_('COM_GTPIHPSSURVEY_PT_'.strtoupper($submenu)),
				'index.php?option=com_gtpihpssurvey&amp;view='.$submenu,
				$vName == $submenu
			);
		}
		
	}

	public static function cleanstr($str) {
		return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $str));
	}

	public static function fixJSON($str) {
		$str = preg_replace("/(?<!\"|'|\w)([a-zA-Z0-9_]+?)(?!\"|'|\w)\s?:/", "\"$1\":", $str);
		$str = str_replace("'", '"', $str);

		return $str;
	}

	public static function getReferences($pks, $table, $key = 'id', $name = 'name', $published = null, $index = 'id') {
		$pks = GTHelperArray::toArray($pks);

		if(!count($pks) > 0) return array();

		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		array_walk($pks, array($db, 'quote'));

		foreach ($pks as $k => $pk) {
			$pks[$k] = $db->quote($pk); 
		}

		$query->select($db->quoteName(array('a.'.$name, 'a.'.$key)));
		$query->from($db->quoteName('#__gtpihpssurvey_'.$table, 'a'));
		$query->where($db->quoteName('a.'.$key) . ' IN (' . implode(',', $pks) . ')');

		if(is_numeric($published)) {
			$query->where($db->quoteName('a.published') . ' = ' . $db->quote($published));
		}

		$db->setQuery($query);
		//echo nl2br(str_replace('#__','eburo_',$query));
		
		$items = $db->loadObjectList($index);

		foreach ($items as &$item) {
			$item = $item->$name;
		}

		return $items ? $items : array();
	}

	public static function getListCount() {
		$params	= func_get_args();
		$db		= JFactory::getDBO();

		$db->setQuery($params[0]);

		if($params[1]) {
			return (int) $db->loadResult();
		}

		$db->execute();
		return (int) $db->getNumRows();
	}

	public static function getURL($params = array()) {
		$urlVars	= array(
			'Itemid'	=> JRequest::getInt('Itemid'),
			'option'	=> JRequest::getCmd('option'),
			'view'		=> JRequest::getCmd('view'),
			'tmpl'		=> JRequest::getCmd('tmpl'),
		);
		if(is_string($params)) {
			$params = explode('&', $params);
			foreach ($params as $strParam) {
				list($urlKey, $urlVar) = explode('=', $strParam);
				$urlVars[$urlKey] = $urlVar;
			}
		} elseif(is_array($params)) {
			foreach ($params as $urlKey => $urlVar) {
				$urlVars[$urlKey] = $urlVar;
			}
		}

		$urlVars = http_build_query(array_filter($urlVars));
		return JRoute::_('index.php?'.$urlVars, false);
	}

	public static function getUserId($val, $key = 'id') {
		// Initialise some variables
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName('id'))
			->from($db->quoteName('#__users'))
			->where($db->quoteName($key) . ' = ' . $db->quote($val));
		$db->setQuery($query, 0, 1);

		return $db->loadResult();
	}

	public static function httpQuery($query, $postman = false) {
		$query = http_build_query($query, "", "&");
		$query = str_replace(array('%5B', '%5D'), array('[', ']'), $query);

		if($postman) {
			$query = str_replace(array('=', '&'), array(':', PHP_EOL), $query);
			$query = urldecode($query);
		}
		return $query;
	}

	public static function isLive() {
		$rootUrl = JURI::root();
		$rootUrl = explode('://', $rootUrl);
		$rootUrl = end($rootUrl);
		$rootUrl = trim($rootUrl, '/');
		$rootUrl = trim($rootUrl, 'www.');

		return $rootUrl == 'survey.hargapangan.id';
	}
}
