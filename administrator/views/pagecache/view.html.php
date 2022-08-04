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

class MulticacheViewPagecache extends JViewLegacy
{

    protected $client;

    protected $data;

    protected $pagination;

    protected $state;

    public function display($tpl = null)
    {

        $this->Items = $this->get('Data');
        $this->hitstats = $this->get('Hitstats');
        $this->client = $this->get('Client');
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');
        if(false !== $this->hitstats && is_object($this->hitstats))
        {
        $this->hitstats->total = $this->get('Total');
        }
        else {
        	//create a default empty class 
        	$this->hitstats = new stdClass();
        }
        
        // Check for errors.
        if (count($errors = $this->get('Errors')))
        {
            JError::raiseError(500, implode("\n", $errors));
            return false;
        }
        
        $this->addToolbar();
        $this->sidebar = JHtmlSidebar::render();
        parent::display($tpl);
    
    }

    protected function addToolbar()
    {

        require_once JPATH_COMPONENT . '/helpers/multicache.php';
        
        $state = $this->get('State');
        $canDo = MulticacheHelper::getActions($state->get('filter.category_id'));
        
        JToolBarHelper::title(JText::_('COM_MULTICACHE_TITLE_PAGE_CACHE_DASHBOARD'), 'pagecache.png');
        
        // Check if the form exists before showing the add/edit buttons
        $formPath = JPATH_COMPONENT_ADMINISTRATOR . '/views/config';
        if (file_exists($formPath))
        {
            
            if ($canDo->get('core.edit'))
            {
                
                JToolBarHelper::custom('config.getconfig', 'apply', 'apply_f2.png', 'config', false);
            }
            if ($canDo->get('core.delete'))
            {
                JToolBarHelper::divider();
                
                JToolBarHelper::divider();
            }
            if ($canDo->get('core.edit') && isset($this->items[0]))
            {
                JToolBarHelper::editList('config.edit', 'JTOOLBAR_EDIT');
            }
        }
        
        if ($canDo->get('core.edit.state'))
        {
            
            if (isset($this->items[0]->state))
            {
                JToolBarHelper::divider();
                JToolBarHelper::custom('pagecache.publish', 'publish.png', 'publish_f2.png', 'JTOOLBAR_PUBLISH', true);
                JToolBarHelper::custom('pagecache.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
            }
            else if (isset($this->Items[0]))
            {
                JToolBarHelper::divider();
                // If this component does not use state then show a direct delete button as we can not trash
                JToolBarHelper::deleteList('', 'pagecache.delete', 'JTOOLBAR_DELETE');
            }
            
            if (isset($this->items[0]->state))
            {
                JToolBarHelper::divider();
                JToolBarHelper::archiveList('pagecache.archive', 'JTOOLBAR_ARCHIVE');
            }
            if (isset($this->items[0]->checked_out))
            {
                JToolBarHelper::custom('pagecache.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
            }
        }
        
        if (isset($this->items[0]->state))
        {
            if ($state->get('filter.state') == - 2 && $canDo->get('core.delete'))
            {
                JToolBarHelper::deleteList('', 'pagecache.delete', 'JTOOLBAR_EMPTY_TRASH');
                JToolBarHelper::divider();
            }
            else if ($canDo->get('core.edit.state'))
            {
                JToolBarHelper::trash('pagecache.trash', 'JTOOLBAR_TRASH');
                JToolBarHelper::divider();
            }
        }
        
        if ($canDo->get('core.admin'))
        {
            JToolBarHelper::preferences('com_multicache');
        }
        $help_url = "//multicache.org/table/documentation/page-cache/";
        JToolbarHelper::help('COM_MULTICACHE_VIEW_MULTICACHE_HELP', false, $help_url);
        
        JHtmlSidebar::setAction('index.php?option=com_multicache');
        
        JHtmlSidebar::addFilter(JText::_('COM_MULTICACHE_SELECT_CACHE_STANDARD_OPTION_LABEL'), 'filter_cache_standard', JHtml::_('select.options', MulticacheHelper::getCacheStandardOptions(), 'value', 'text', $this->state->get('cacheStandard')));
    
    }

}