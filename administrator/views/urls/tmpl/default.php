<?php
/**
 *
 * @version 1.0.1.5
 * @package com_multicache
 * @copyright Copyright (C) Multicache.org 2015. All rights reserved.
 * @licenseEULA see LICENSE.txt - http://multicache.org/terms-conditions/end-user-license-agreement.html
 * @author Wayne DSouza <consulting@OnlineMarketingConsultants.in> - http://OnlineMarketingConsultants.in
 *        
 */
// No direct access
defined('_JEXEC') or die();

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));
$params = $this->state->get('params');
$params = json_decode($params);
?>
<form
	action="<?php
echo JRoute::_('index.php?option=com_multicache&view=urls');
?>"
	method="post" name="adminForm" id="adminForm">
<?php
/* echo JLayoutHelper::render('joomla.searchtools.default', array('view' => 'memcaches' )); */
?>
<?php

if (! empty($this->sidebar))
:
    ?>
<div id="j-sidebar-container" class="span2">

<?php
    echo $this->sidebar;
    ?>
</div>
	<div id="j-main-container" class="span10">








<?php
else
:
    ?>
<div id="j-main-container" class="span12">








<?php

endif;
?>

<div class="clearfix"></div>
			<div class="row-fluid">
				<div class="alert alert-info">
					<h3 class="small">
			<?php
echo JText::_('COM_MULTICACHE_URLS_GLOBAL_STAT_SUMMARY');
?>
		</h3>
					<div style="display: inline;">Google urls : <?php
    echo ! empty($this->urlstats) ? $this->urlstats[google] : 'na';
    ?>  </div>
					<div style="display: inline; margin-left: 1.2em;">Manual urls  : <?php
    echo ! empty($this->urlstats) ? $this->urlstats[manual] : 'na';
    ?>  </div>

				</div>
			</div>



			<table class="table table-striped" id="gtresultslist">
				<thead>
					<tr>
						<th width="1%" class="hidden-phone">
<?php
echo JHtml::_('grid.checkall');
?>
</th>
						<th width="1%" class="nowrap center hidden-phone">
<?php
echo JHtml::_('grid.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder);
?>
</th>

						<th width="10%" class=" nowrap">
<?php
echo JHtml::_('grid.sort', 'COM_MULTICACHE_URL_VIEW_URL_PARAM', 'a.url', $listDirn, $listOrder);
?>
</th>
						<th width="10%" class="center nowrap">
<?php
echo JHtml::_('grid.sort', 'COM_MULTICACHE_URL_VIEW_VIEW_PARAM', 'a.views', $listDirn, $listOrder);
?>
</th>
						<th width="10%" class="nowrap center">
<?php
echo JHtml::_('grid.sort', 'COM_MULTICACHE_URL_VIEW_FREQUENCY_DISTRIBUTION', 'a.f_dist', $listDirn, $listOrder);
?>
</th>
						<th width="10%" class="nowrap center">
<?php
echo JHtml::_('grid.sort', 'COM_MULTICACHE_URL_VIEW_NATURAL_LOGRITHM', 'a.ln_dist', $listDirn, $listOrder);
?>
</th>
						<th width="10%" class="nowrap center">
<?php
echo JHtml::_('grid.sort', 'COM_MULTICACHE_URL_VIEW_TYPE_PARAM', 'a.gzip_factor', $listDirn, $listOrder);
?>
</th>

						<th class="date center nowrap" width="5%">
<?php
echo JHtml::_('grid.sort', 'JDATE', 'a.created', $listDirn, $listOrder);
?>
</th>
					</tr>
				</thead>

				<tbody>

<?php
foreach ($this->Items as $i => $item)
:
    ?>
<?php

    $f_dist = number_format($item->f_dist * 100, 2);
    $ln_dist = number_format($item->ln_dist, 2);
    ?>
<tr class="row<?php
    echo $i % 2;
    ?> ">
						<td class="center hidden-phone">
<?php
    echo JHtml::_('grid.id', $i, $item->id);
    ?>
</td>
						<td class=" center hidden-phone has-context">
<?php
    echo $item->id;
    ?>
</td>
						<td class="nowrap  ">
<?php
    echo $item->url;
    ?>
</td>
						<td class="nowrap center ">
<?php
    echo $item->views;
    ?>
</td>
						<td class="nowrap hascontext center">
<?php
    echo $f_dist . ' %';
    ?>

</td>
						<td class="nowrap hascontext center">
<?php
    echo $ln_dist;
    ?>

</td>
						<td class="nowrap center ">
<?php
    echo $item->type;
    ?>
</td>

						<td class="nowrap center ">
<?php
    echo $item->created;
    ?>
</td>

					</tr>

<?php
endforeach
;
?>

</tbody>
				<tfoot>
					<tr>
						<td colspan="10">
<?php
echo $this->pagination->getListFooter();
?>
</td>
					</tr>
				</tfoot>
			</table>

			<input type="hidden" name="task" value="" /> <input type="hidden"
				name="boxchecked" value="0" /> <input type="hidden"
				name="filter_order" value="<?php
    echo $listOrder;
    ?>" /> <input type="hidden" name="filter_order_Dir"
				value="<?php
    echo $listDirn;
    ?>" />
<?php
echo JHtml::_('form.token');
?>
</div>

</form>