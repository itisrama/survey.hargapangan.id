<?php
/**
 * @package   T3 Blank
 * @copyright Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license   GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// get params

$sitename	= $this->params->get('sitename');
$slogan		= $this->params->get('slogan', '');
$subSlogan	= null;
$logotype	= $this->params->get('logotype', 'text');
$logoimage	= $logotype == 'image' ? $this->params->get('logoimage') : '';

$document		= JFactory::getDocument();
$config			= JFactory::getConfig();
$titlePosCfg	= $config->get('sitename_pagetitles');
$siteNameCfg	= $config->get('sitename');

if (!$sitename) {
	$sitename = $siteNameCfg;
}

$titleConj	= '-';
$titleNew	= $slogan ? $slogan : $sitename;
switch ($titlePosCfg) {
	case '1':
		$document->setTitle(str_replace(
			$siteNameCfg.' -',
			$titleNew.' '.$titleConj,
			$document->getTitle()
		));
		break;
	case '2':
		$document->setTitle(str_replace(
			'- '.$siteNameCfg,
			$titleConj.' '.$titleNew,
			$document->getTitle()
		));
		break;
}

$uriFavicon = JUri::root(true).'/templates/'.$this->template.'/favicon/';

$this->addHeadLink($uriFavicon.'apple-touch-icon-57x57.png', 'apple-touch-icon-precomposed', 'rel', array('size' => '57x57'));
$this->addHeadLink($uriFavicon.'apple-touch-icon-114x114.png', 'apple-touch-icon-precomposed', 'rel', array('size' => '114x114'));
$this->addHeadLink($uriFavicon.'apple-touch-icon-144x144.png', 'apple-touch-icon-precomposed', 'rel', array('size' => '144x144'));
$this->addHeadLink($uriFavicon.'apple-touch-icon-60x60.png', 'apple-touch-icon-precomposed', 'rel', array('size' => '60x60'));
$this->addHeadLink($uriFavicon.'apple-touch-icon-120x120.png', 'apple-touch-icon-precomposed', 'rel', array('size' => '120x120'));
$this->addHeadLink($uriFavicon.'apple-touch-icon-76x76.png', 'apple-touch-icon-precomposed', 'rel', array('size' => '76x76'));
$this->addHeadLink($uriFavicon.'apple-touch-icon-152x152.png', 'apple-touch-icon-precomposed', 'rel', array('size' => '152x152'));
$this->addHeadLink($uriFavicon.'favicon-196x196.png', 'icon', 'rel', array('type' => 'image/png', 'size' => '196x196'));
$this->addHeadLink($uriFavicon.'favicon-96x96.png', 'icon', 'rel', array('type' => 'image/png', 'size' => '96x96'));
$this->addHeadLink($uriFavicon.'favicon-32x32.png', 'icon', 'rel', array('type' => 'image/png', 'size' => '32x32'));
$this->addHeadLink($uriFavicon.'favicon-16x16.png', 'icon', 'rel', array('type' => 'image/png', 'size' => '16x16'));
$this->addHeadLink($uriFavicon.'favicon-128.png', 'icon', 'rel', array('type' => 'image/png', 'size' => '128x128'));
$document->setMetaData('application-name', '&nbsp;');
$document->setMetaData('msapplication-TileColor', '#FFFFFF');
$document->setMetaData('msapplication-TileImage', $uriFavicon.'mstile-144x144.png');
$document->setMetaData('msapplication-square70x70logo', $uriFavicon.'mstile-70x70.png');
$document->setMetaData('msapplication-square150x150logo', $uriFavicon.'mstile-150x150.png');
$document->setMetaData('msapplication-wide310x150logo', $uriFavicon.'mstile-310x150.png');
$document->setMetaData('msapplication-square310x310logo', $uriFavicon.'mstile-310x310.png');

$logoimageurl	= ($logotype == 'image' && $logoimage) ? JURI::base(false) . '/' . $logoimage : null;

if($logotype == 'image' && $logoimage) {
	$document->addStyleDeclaration(sprintf("
		.logo-image h1 {
			background-image: url(%s);
		}
	", $logoimageurl));
}
?>

<header id="t3-header" class="t3-header clearfix">
	<?php $this->addScript(T3_URL.'/js/nav-collapse.js'); ?>
	

	<div class="logo-<?php echo $logotype ?>">
		<a href="<?php echo JURI::base(true) ?>">
			<h1><?php echo $sitename ?></h1>
		</a>
		<div class="site-slogan">
			<?php //echo $slogan ?><br/>
		</div>
	</div>

	<ul class="navbar-right list-inline">
		<?php /*
		<li class="dropdown" href="#" data-toggle="dropdown">
			<a class="btn btn-default dropdown-toggle" data-toggle="dropdown" href="#">
				<i class="fa fa-user fa-fw"></i>  <i class="fa fa-caret-down"></i>
			</a>
			<div class="dropdown-menu dropdown-user">
				<jdoc:include type="modules" name="<?php $this->_p('head-user') ?>" style="raw" />
			</div>
		</li>*/?>
		<li>
			<jdoc:include type="modules" name="<?php $this->_p('head-user') ?>" style="raw" />
		</li>
		<li>
			<button class="btn-collapse btn btn-default" data-target=".t3-mainnav">
				<i class="fa fa-bars"></i>
			</button>
		</li>
	</ul>
</header>