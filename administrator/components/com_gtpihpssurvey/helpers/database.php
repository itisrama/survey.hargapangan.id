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

class GTHelperDatabase
{
	public static function saveBulk($items, $table) {
		echo "<pre>"; print_r($items); echo "</pre>";
		$items = is_object($items) ? JArrayHelper::fromObject($items) : $items;
		if(!count($items) > 0) {
			return true;
		}

		$db = JFactory::getDbo();
 
		$query = $db->getQuery(true);

		// Insert columns.
		$columns = reset($items);
		$columns = is_object($columns) ? JArrayHelper::fromObject($columns) : $columns;
		$columns = array_keys($columns);

		foreach ($items as &$item) {
			$item = is_object($item) ? JArrayHelper::fromObject($item) : $item;
			foreach ($item as &$val) {
				$val = $db->quote($val);
			}
			$item = implode(', ', $item);
		}

		// Prepare the insert query.
		$query->insert($db->quoteName($table));
		$query->columns($db->quoteName($columns));
		$query->values($items);

		foreach ($columns as &$column) {
			$column = $db->quoteName($column).' = VALUES('.$db->quoteName($column).')';
		}
		$columns = implode(', ', $columns);

		$query = $query . ' ON DUPLICATE KEY UPDATE ' . $columns;

		// Set the query using our newly populated query object and execute it.
		echo nl2br(str_replace('#__','eburo_',$query));
		$db->setQuery($query);

		return $db->execute();
	}

}
