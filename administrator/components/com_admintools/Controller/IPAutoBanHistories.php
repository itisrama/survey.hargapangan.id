<?php
/**
 * @package   AdminTools
 * @copyright 2010-2017 Akeeba Ltd / Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\AdminTools\Admin\Controller;

defined('_JEXEC') or die;

use Akeeba\AdminTools\Admin\Controller\Mixin\CustomACL;
use FOF30\Controller\DataController;

class IPAutoBanHistories extends DataController
{
	use CustomACL;
}