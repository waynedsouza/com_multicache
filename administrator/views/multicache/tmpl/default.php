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
defined('_JEXEC') or die();

JHtml::_('formbehavior.chosen', 'select');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));
?>

<form
	action="<?php
echo JRoute::_('index.php?option=com_multicache&view=multicache');
?>"
	method="post" name="adminForm" id="adminForm">
	<div id="j-sidebar-container" class="span2">
		<?php
echo $this->sidebar;
?>
	</div>
	<div id="j-main-container" class="span10">

		<div class="clearfix"></div>
		<div class="row-fluid">
			<div class="container-fluid content-fluid alert alert-info">
				<h3 class="small">
			<?php
echo JText::_('COM_MULTICACHE_SIMULATIONDASHBOARD_GLOBAL_STAT_SUMMARY');
?>
		</h3>

				<div class="span3 inline"><?php
    echo JText::_('COM_MULTICACHE_PAGE_CACHE_CACHE_SIZE_LABEL');
    ?> : <?php
    echo false !== $this->hitstats ? JHtml::_('number.bytes', $this->hitstats->filesize * 1024) : 'na';
    ?> </div>
				<div class="span3 inline"><?php
    echo JText::_('COM_MULTICACHE_PAGE_CACHE_GET_RATE_LABEL');
    ?> : <?php
    echo false !== $this->hitstats ? number_format($this->hitstats->getrate * 100, 1) : 'na';
    ?>% </div>
				<div class="span3 inline"><?php
    echo JText::_('COM_MULTICACHE_PAGE_CACHE_DELETE_RATE_LABEL');
    ?> : <?php
    echo false !== $this->hitstats ? number_format($this->hitstats->deleterate * 100, 1) : 'na';
    ?>% </div>

				<div class="span3 inline"><?php
    echo JText::_('COM_MULTICACHE_PAGE_CACHE_TIME_STAMP_LABEL');
    ?> : <?php
    echo false !== $this->hitstats ? $this->hitstats->timestamp : 'na';
    ?></div>
				<div class="span2 inline"><?php
    echo JText::_('COM_MULTICACHE_PAGE_CACHE_UPTIME_LABEL');
    ?> : <?php
    echo false !== $this->hitstats ? gmdate('h:i:s', $this->hitstats->uptime) : 'na';
    ?></div>
				<div class="span2 inline"><?php
    echo JText::_('COM_MULTICACHE_PAGE_CACHE_GET_HITS_LABEL');
    ?> : <?php
    echo false !== $this->hitstats ? number_format($this->hitstats->get_hits, 0) : 'na';
    ?> </div>
				<div class="span2 inline"><?php
    echo JText::_('COM_MULTICACHE_PAGE_CACHE_GET_MISSES_LABEL');
    ?> : <?php
    echo false !== $this->hitstats ? number_format($this->hitstats->get_misses, 0) : 'na';
    ?> </div>
				<div class="span2 inline"><?php
    echo JText::_('COM_MULTICACHE_PAGE_CACHE_DELETE_HITS_LABEL');
    ?> : <?php
    echo false !== $this->hitstats ? number_format($this->hitstats->delete_hits, 0) : 'na';
    ?> </div>
				<div class="span2 inline"><?php
    echo JText::_('COM_MULTICACHE_PAGE_CACHE_DELETE_MISSES_LABEL');
    ?> : <?php
    echo false !== $this->hitstats ? number_format($this->hitstats->delete_misses, 0) : 'na';
    ?> </div>
				<div class="span2 inline"><?php
    echo JText::_('COM_MULTICACHE_PAGE_CACHE_CURRENT_ITEMS_LABEL');
    ?> : <?php
    echo false !== $this->hitstats ? number_format($this->hitstats->curr_items, 0) : 'na';
    ?> </div>

				<p class="small" style="margin-top: 1em;"><?php
    if (! empty($this->filtermessage)) echo 'activefilters :' . $this->filtermessage;
    ?></p>

			</div>
		</div>

		<table class="table table-striped">
			<thead>
				<tr>
					<th width="20">
					<?php
    echo JHtml::_('grid.checkall');
    ?>
				</th>
					<th class="title nowrap">
					<?php
    echo JHtml::_('grid.sort', 'COM_MULTICACHE_GROUP', 'group', $listDirn, $listOrder);
    ?>
				</th>
					<th width="5%" class="center nowrap">
					<?php
    echo JHtml::_('grid.sort', 'COM_MULTICACHE_NUMBER_OF_FILES', 'count', $listDirn, $listOrder);
    ?>
				</th>
					<th width="10%" class="center">
					<?php
    echo JHtml::_('grid.sort', 'COM_MULTICACHE_SIZE', 'size', $listDirn, $listOrder);
    ?>
				</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="4">
				<?php
    echo $this->pagination->getListFooter();
    ?>
				</td>
				</tr>
			</tfoot>
			<tbody>
			<?php
$i = 0;
if (isset($this->data))
:
    foreach ($this->data as $folder => $item)
    :
        ?>
				<tr class="row<?php
        echo $i % 2;
        ?>">
					<td><input type="checkbox"
						id="cb<?php
        echo $i;
        ?>" name="cid[]"
						value="<?php
        echo $item->group;
        ?>"
						onclick="Joomla.isChecked(this.checked);" /></td>
					<td><strong><?php
        echo $item->group;
        ?></strong></td>
					<td class="center">
						<?php
        echo $item->count;
        ?>
					</td>
					<td class="center">
						<?php
        echo JHtml::_('number.bytes', $item->size * 1024);
        ?>
					</td>
				</tr>
			<?php
        $i ++;
    endforeach
    ;







endif;
?>
		</tbody>
		</table>

		<input type="hidden" name="task" value="" /> <input type="hidden"
			name="boxchecked" value="0" /> <input type="hidden" name="client"
			value="<?php
echo $this->client->id;
?>" /> <input type="hidden" name="filter_order"
			value="<?php
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