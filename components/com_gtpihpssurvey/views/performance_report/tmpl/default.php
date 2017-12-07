<?php

// No direct access
defined('_JEXEC') or die('Restricted access');

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::_('behavior.tooltip');
JHtml::_('behavior.multiselect');
?>

<div id="com_gtpihps" class="item-page<?php echo $this->params->get('pageclass_sfx'); ?>">
	<?php if ($this->params->get('show_page_heading', 1)): ?>
	<div class="page-header" style="position:relative">
		<h1><?php echo $this->page_title; ?></h1>
	</div>
	<?php endif; ?>

	
	<?php echo $this->loadTemplate('form'); ?>
	<br/>
	<?php echo $this->loadTemplate('table'); ?>
</div>
