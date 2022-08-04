<?php
/**
 *
 * @version 1.0.1.5
 * @package com_multicache
 * @copyright Copyright (C) Multicache.org 2015. All rights reserved.
 * @licenseEULA see LICENSE.txt - http://multicache.org/terms-conditions/end-user-license-agreement.html
 * @author Wayne DSouza <consulting@OnlineMarketingConsultants.in> - http://OnlineMarketingConsultants.in
 */
// No direct access
defined('_JEXEC') or die();

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$params    = $this->state->get('params');
$params    = json_decode($params);
?>
<form
	action="<?php
echo JRoute::_('index.php?option=com_multicache&view=advancedsimulation');
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
					<div class="span2 inline">Average : <?php
echo number_format($this->statglobal->average_page_load_time / 1000, 2);
?> seconds</div>
					<div class="span2 inline">Minimum  : <?php
echo number_format($this->statglobal->minimum_page_load_time / 1000, 2);
?> seconds</div>
					<div class="span2 inline">Maximum  : <?php
echo number_format($this->statglobal->maximum_page_load_time / 1000, 2);
?> seconds</div>
					<div class="span3 inline">Standard Deviation  : <?php
echo number_format($this->statglobal->standarddeviation_page_load_time / 1000, 4);
?> seconds</div>
					<div class="span3 inline">Variance : <?php
echo number_format($this->statglobal->variance_page_load_time / 1000000, 4);
?> </div>
					<div class="span2 inline">Cycles : <?php
echo false  !== $this->testgroup_stats ? number_format($this->testgroup_stats->cycles, 0) : 0;
?> </div>
					<div class="span2  inline">Remaining tests : <?php
if (false  !== $this->testgroup_stats  && is_string($this->testgroup_stats->remaining_tests))
{
    echo $this->testgroup_stats->remaining_tests;
}
elseif (false  !== $this->testgroup_stats  && is_numeric($this->testgroup_stats->remaining_tests))
{
    echo number_format($this->testgroup_stats->remaining_tests, 0);
}
?> </div>
					<div class="span3   inline">Expected end date : <?php
echo false  !== $this->testgroup_stats ? $this->testgroup_stats->expected_end_date : 'na';
?> </div>
					<div class="span2 inline">@Tests per day : <?php
if (false  !== $this->testgroup_stats  && is_string($this->testgroup_stats->testsperday))
{
    echo $this->testgroup_stats->testsperday;
}
elseif (false  !== $this->testgroup_stats  && is_numeric($this->testgroup_stats->testsperday))
{
    echo number_format($this->testgroup_stats->testsperday, 0);
}
?> </div>
					<div class="span3 inline">Test mode : <?php
echo false  !== $this->testgroup_stats ? $this->testgroup_stats->advanced: 'na';
?> </div>

					<p class="small span10" style="margin-top: 1em;"><?php
if (!empty($this->filtermessage))
    echo 'activefilters :' . $this->filtermessage;
?></p>

				</div>
			</div>

			<table class="table table-striped" id="gtresultslist">
				<thead>
					<tr>
						<th width="1%" class="hidden-phone">
							<!--<input type="checkbox" name="checkall-toggle" value="" title="<?php
echo JText::_('JGlobal_Check_All');
?>" onclick="Joomla!.checkAll(this)" />-->
<?php
echo JHtml::_('grid.checkall');
?>
</th>
						<th width="1%" class="nowrap center hidden-phone">
<?php
echo JHtml::_('grid.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder);
?>
</th>
						<th class="date center nowrap" width="5%">
<?php
echo JHtml::_('grid.sort', 'JDATE', 'a.mtime', $listDirn, $listOrder);
?>
</th>
						<th width="8%" class="center nowrap">
<?php
echo JHtml::_('grid.sort', 'COM_MULTICACHE_TEST_REPORTS_PAGE_LOAD_TIME', 'a.page_load_time', $listDirn, $listOrder);
?>
</th>
						<th width="8%" class="center nowrap">
<?php
echo JHtml::_('grid.sort', 'COM_MULTICACHE_TEST_REPORTS_HTML_LOAD_TIME', 'a.html_load_time', $listDirn, $listOrder);
?>
</th>
						<th width="8%" class="nowrap center">
<?php
echo JHtml::_('grid.sort', 'COM_MULTICACHE_TEST_REPORTS_COMPLETE_REPORT', 'a.report_url', $listDirn, $listOrder);
?>
</th>
						<th width="5%" class="nowrap center">
<?php
echo JHtml::_('grid.sort', 'COM_MULTICACHE_TEST_REPORTS_PRECACHE_FACTOR', 'a.precache_factor', $listDirn, $listOrder);
?>
</th>
						<th width="5%" class="nowrap center">
<?php
echo JHtml::_('grid.sort', 'COM_MULTICACHE_TEST_REPORTS_MEMORY_COMPRESSION_FACTOR', 'a.gzip_factor', $listDirn, $listOrder);
?>
</th>
						<th width="5%" class="nowrap center hidden-phone">
<?php
echo JHtml::_('grid.sort', 'COM_MULTICACHE_TEST_REPORTS_GOOGLE_PAGESPEED_SCORE', 'a.pagespeed_score', $listDirn, $listOrder);
?>
</th>
						<th width="5%" class="nowrap center hidden-phone">
<?php
echo JHtml::_('grid.sort', 'COM_MULTICACHE_TEST_REPORTS_YSLOW_YAHOO_SCORE', 'a.pagespeed_score', $listDirn, $listOrder);
?>
</th>
						<th width="5%" class="nowrap center hidden-phone">
<?php
echo JHtml::_('grid.sort', 'COM_MULTICACHE_TEST_REPORTS_PAGE_ELEMENTS', 'a.page_elements', $listDirn, $listOrder);
?>
</th>
						<th width="5%" class="nowrap center hidden-phone">
<?php
echo JHtml::_('grid.sort', 'COM_MULTICACHE_TEST_REPORTS_HTML_SIZE', 'a.html_bytes', $listDirn, $listOrder);
?>
</th>
						<th width="5%" class="nowrap center hidden-phone">
<?php
echo JHtml::_('grid.sort', 'COM_MULTICACHE_TEST_REPORTS_PAGE_SIZE', 'a.page_bytes', $listDirn, $listOrder);
?>
</th>

					</tr>
				</thead>

				<tbody>

<?php
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
    echo JHtml::_('grid.id', $i, $item->id);
?>
</td>
						<td class=" center hidden-phone has-context">
<?php
    echo $item->id;
?>
</td>
						<td class="nowrap center ">
<?php
    echo $item->date_of_test;
?>
</td>
						<td class="nowrap hascontext center">
<?php
    echo $pageLoadTime;
?>

</td>
						<td class="nowrap hascontext center">
<?php
    echo number_format(($item->html_load_time) / 1000, 2);
?>

</td>
						<td class="nowrap hascontext center">
<?php
    // echo $item->status;
    if ($item->status == 'test_abandoned'):
        echo JText::_('COM_MULTICACHE_SETTINGS_TEST_ABANDONED_LABEL');
    elseif ($item->status == 'complete'):
?>
<a href="<?php
        echo $item->report_url;
?>"
							target="_blank"><?php
        echo preg_replace('/\/$/', '', (str_ireplace('http://', '', str_ireplace('https://', '', $item->test_page))));
?></a>

    
    <?php
    elseif ($item->status == 'initiated' || $item->status == 'test_started' || $item->status == 'test_recorded' || $item->status == 'cache_strategy_ready' || $item->status == 'page_pinged' || $item->status == 'cache_cleaned'):
        echo JText::_('COM_MULTICACHE_SETTINGS_TEST_WIP_LABEL');
    elseif ($item->status == 'test_on_hold'):
        echo JText::_('COM_MULTICACHE_SETTINGS_TEST_ON_HOLD_LABEL');
    elseif ($item->status == 'daily_budget_complete'):
        echo JText::_('COM_MULTICACHE_SETTINGS_AWAITING_CREDIT_TOPUP_LABEL');
    endif;
?>
</td>
						<td class="nowrap hascontext center">
<?php
    echo $item->precache_factor;
?>

</td>
						<td class="nowrap hascontext center">
<?php
    echo number_format($item->cache_compression_factor, 2);
?>

</td>
						<td class="nowrap hascontext center">
<?php
    echo $item->pagespeed_score;
?>

</td>
						<td class="nowrap hascontext center">
<?php
    echo $item->yslow_score;
?>

</td>

						<td class="nowrap hascontext center">
<?php
    echo $item->page_elements;
?>

</td>
						<td class="nowrap hascontext center">
<?php
    echo number_format(($item->html_bytes / 1024), 2);
?>

</td>
						<td class="nowrap hascontext center">
<?php
    echo number_format(($item->page_bytes / 1024), 2);
?>

</td>

					</tr>

<?php
endforeach;
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


