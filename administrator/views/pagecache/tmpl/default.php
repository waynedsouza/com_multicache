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
JHtml::_('bootstrap.framework');
JHTML::_('behavior.modal', 'a.modal');
JHtml::_('behavior.tooltip');
JHtml::_('behavior.keepalive');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$params    = $this->state->get('params');
$params    = json_decode($params);
?>
<script>
jmulticache = jQuery.noConflict();
jmulticache(document).ready(function() {
    var width = jmulticache(window).width();
    var height = jmulticache(window).height();


    jmulticache('a.modal').attr('rel','{handler: "iframe", size: {x: '+(width-(width*0.10))+', y: '+(height-(height*0.10))+'}}');

});
</script>
<form
	action="<?php
echo JRoute::_('index.php?option=com_multicache&view=pagecache');
?>"
	method="post" name="adminForm" id="adminForm">

<?php
if (!empty($this->sidebar)):
?>
<div id="j-sidebar-container" class="span2">

<?php
    echo $this->sidebar;
?>
</div>
	<div id="j-main-container" class="span10">

<?php
else:
?>
<div id="j-main-container" class="span12">

<?php
endif;
?>

<div class="clearfix"></div>
			<div class="row-fluid">
				<div class="container-fluid content-fluid alert alert-info">
					<h3 class="small">
			<?php
echo JText::_('COM_MULTICACHE_SIMULATIONDASHBOARD_GLOBAL_STAT_SUMMARY');
?>
		</h3>

					<div class="span3 inline"><?php
echo JText::_('COM_MULTICACHE_PAGE_CACHE_TOTAL_PAGES_LABEL');
?> : <?php
echo property_exists($this->hitstats ,'total') ? $this->hitstats->total : 'na';
?> </div>
					<div class="span3 inline"><?php
echo JText::_('COM_MULTICACHE_PAGE_CACHE_GET_RATE_LABEL');
?> : <?php
echo property_exists($this->hitstats ,'getrate') ? number_format($this->hitstats->getrate * 100, 1) : 'na';
?>% </div>
					<div class="span3 inline"><?php
echo JText::_('COM_MULTICACHE_PAGE_CACHE_DELETE_RATE_LABEL');
?> : <?php
echo property_exists($this->hitstats ,'deleterate') ? number_format($this->hitstats->deleterate * 100, 1) : 'na';
?>% </div>

					<div class="span3 inline"><?php
echo JText::_('COM_MULTICACHE_PAGE_CACHE_TIME_STAMP_LABEL');
?> : <?php
echo property_exists($this->hitstats ,'timestamp') ? $this->hitstats->timestamp : 'na';
?></div>
					<div class="span2 inline"><?php
echo JText::_('COM_MULTICACHE_PAGE_CACHE_UPTIME_LABEL');
?> : <?php
echo property_exists($this->hitstats ,'uptime') ? gmdate('h:i:s', $this->hitstats->uptime) : 'na';
?></div>
					<div class="span2 inline"><?php
echo JText::_('COM_MULTICACHE_PAGE_CACHE_GET_HITS_LABEL');
?> : <?php
echo property_exists($this->hitstats ,'get_hits') ? number_format($this->hitstats->get_hits, 0) : 'na';
?> </div>
					<div class="span2 inline"><?php
echo JText::_('COM_MULTICACHE_PAGE_CACHE_GET_MISSES_LABEL');
?> : <?php
echo property_exists($this->hitstats ,'get_misses') ?number_format($this->hitstats->get_misses, 0) : 'na';
?> </div>
					<div class="span2 inline"><?php
echo JText::_('COM_MULTICACHE_PAGE_CACHE_DELETE_HITS_LABEL');
?> : <?php
echo property_exists($this->hitstats ,'delete_hits') ? number_format($this->hitstats->delete_hits, 0) : 'na';
?> </div>
					<div class="span2 inline"><?php
echo JText::_('COM_MULTICACHE_PAGE_CACHE_DELETE_MISSES_LABEL');
?> : <?php
echo property_exists($this->hitstats ,'delete_misses') ? number_format($this->hitstats->delete_misses, 0) : 'na';
?> </div>
					<div class="span2 inline"><?php
echo JText::_('COM_MULTICACHE_PAGE_CACHE_CURRENT_ITEMS_LABEL');
?> : <?php
echo property_exists($this->hitstats ,'curr_items') ? number_format($this->hitstats->curr_items, 0) : 'na';
?> </div>

					<p class="small" style="margin-top: 1em;"><?php
if (!empty($this->filtermessage))
    echo 'activefilters :' . $this->filtermessage;
?></p>

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
echo JHtml::_('grid.sort', 'JGRID_HEADING_ID', 'id', $listDirn, $listOrder);
?>
</th>

						<th width="8%" class="center nowrap">
<?php
echo JHtml::_('grid.sort', 'COM_MULTICACHE_PAGE_CACHE_URL_LABEL', 'url', $listDirn, $listOrder);
?>
</th>
						<th width="8%" class="center nowrap">
<?php
echo JHtml::_('grid.sort', 'COM_MULTICACHE_PAGE_CACHE_VIEWS_LABEL', 'views', $listDirn, $listOrder);
?>
</th>

						<th width="5%" class="nowrap center">
<?php
echo JHtml::_('grid.sort', 'COM_MULTICACHE_PAGE_CACHE_CACHEID_LABEL', 'cache_id', $listDirn, $listOrder);
?>
</th>
						<th width="5%" class="nowrap center">
<?php
echo JHtml::_('grid.sort', 'COM_MULTICACHE_PAGE_CACHE_TYPE_LABEL', 'type', $listDirn, $listOrder);
?>
</th>


					</tr>
				</thead>

				<tbody>

<?php
if (!empty($this->Items)):
    foreach ($this->Items as $i => $item):
?>
<?php
        
        $pageLoadTime = number_format(($item->page_load_time) / 1000, 2);
        if ($item->status == 'test_abandoned'):
?>
<tr
						class="row<?php
            echo $i % 2;
?> bg-danger alert alert-danger">
        <?php
        elseif (($pageLoadTime > $params->danger_tolerance_factor * $this->multicacheconfig->targetpageloadtime) && $item->status == 'complete'):
?>



					
					
					
					<tr class="row<?php
            echo $i % 2;
?> " style="color:<?php
            echo $params->danger_tolerance_color;
?>">

        <?php
        elseif (($pageLoadTime > $params->warning_tolerance_factor * $this->multicacheconfig->targetpageloadtime) && $item->status == 'complete'):
?>



					
					
					
					<tr class="row<?php
            echo $i % 2;
?>" style="color:<?php
            echo $params->warning_tolerance_color;
?>">

        <?php
        elseif ($pageLoadTime < $this->multicacheconfig->targetpageloadtime && $item->status == 'complete'):
?>



					
					
					
					<tr class="row<?php
            echo $i % 2;
?> " style="color:<?php
            echo $params->success_tolerance_color;
?>">

        <?php
        else:
?>



					
					
					
					<tr class="row<?php
            echo $i % 2;
?> ">

        <?php
        endif;
?>
<td class="center hidden-phone">
<?php
        echo JHtml::_('grid.id', $i, $item['cache_id']);
?>
</td>
						<td class="  hidden-phone has-context">
<?php
        echo $item['id'];
?>
</td>

						<td class="nowrap hascontext ">
<?php
        echo '<a href="/administrator/index.php?option=com_multicache&view=pagecache&format=manifest&id=' . $item['cache_id'] . '" class="modal hasTooltip"   rel="{handler: \'iframe\', size: {x: 600, y: 450}}" title="' . JText::_('COM_MULTICACHE_PAGECACHE_VIEW_MANIFEST_LABEL') . '"> ' . $item['url'] . '</a>';
?>

</td>
						<td class="nowrap hascontext ">
<?php
        if ($item['views'] == 0)
        {
            echo "na";
        }
        else
        {
            echo $item['views'];
        }
?>

</td>

						<td class="nowrap hascontext ">
<?php
        echo '<a href="/administrator/index.php?option=com_multicache&view=pagecache&format=code&id=' . $item['cache_id'] . '" class="modal hasTooltip"   rel="{handler: \'iframe\', size: {x: 600, y: 450}}" title="' . JText::_('COM_MULTICACHE_PAGECACHE_VIEW_CODE_LABEL') . '"> ' . $item['cache_id'] . '</a>';
?>

</td>
						<td class="nowrap hascontext ">
<?php
        echo $item['type'];
?>

</td>


					</tr>

<?php
    endforeach;
endif;
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
