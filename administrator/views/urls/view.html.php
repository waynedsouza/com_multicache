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

jimport('joomla.application.component.view');

class MulticacheViewUrls extends JViewLegacy
{

    protected $items;

    protected $pagination;

    protected $state;

    public function display($tpl = null)
    {

        $this->state = $this->get('State');
        $this->Items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->urlstats = $this->get('UrlStats');
        $this->activeFilters = $this->getActiveFilters();
        
        if (count($errors = $this->get('Errors')))
        {
            throw new Exception(implode("\n", $errors));
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
        
        JToolBarHelper::title(JText::_('COM_MULTICACHE_TITLE_URL_ANALYSER_LABEL'), 'urls.png');
        
        // Check if the form exists before showing the add/edit buttons
        $formPath = JPATH_COMPONENT_ADMINISTRATOR . '/views/config';
        if (file_exists($formPath))
        {
            
            if ($canDo->get('core.edit'))
            {
                // JToolBarHelper::addNew('config.add', 'COM_MULTICACHE_CONFIG_SETTINGS');
                JToolBarHelper::custom('config.getconfig', 'apply', 'apply_f2.png', 'config', false);
                JToolBarHelper::custom('urls.devolveconfig', 'apply', 'apply_f2.png', 'update multicache', false);
                JToolbarHelper::custom('urls.delete', 'delete.png', 'delete_f2.png', 'JTOOLBAR_DELETE', true);
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
                JToolBarHelper::custom('simulationdashboard.publish', 'publish.png', 'publish_f2.png', 'JTOOLBAR_PUBLISH', true);
                JToolBarHelper::custom('simulationdashboard.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
            }
            else if (isset($this->items[0]))
            {
                // If this component does not use state then show a direct delete button as we can not trash
                JToolBarHelper::deleteList('', 'simulationdashboard.delete', 'JTOOLBAR_DELETE');
            }
            
            if (isset($this->items[0]->state))
            {
                JToolBarHelper::divider();
                JToolBarHelper::archiveList('simulationdashboard.archive', 'JTOOLBAR_ARCHIVE');
            }
            if (isset($this->items[0]->checked_out))
            {
                JToolBarHelper::custom('simulationdashboard.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
            }
        }
        
        // Show trash and delete for components that uses the state field
        if (isset($this->items[0]->state))
        {
            if ($state->get('filter.state') == - 2 && $canDo->get('core.delete'))
            {
                JToolBarHelper::deleteList('', 'simulationdashboard.delete', 'JTOOLBAR_EMPTY_TRASH');
                JToolBarHelper::divider();
            }
            else if ($canDo->get('core.edit.state'))
            {
                JToolBarHelper::trash('simulationdashboard.trash', 'JTOOLBAR_TRASH');
                JToolBarHelper::divider();
            }
        }
        
        if ($canDo->get('core.admin'))
        {
            JToolBarHelper::preferences('com_multicache');
            JToolbarHelper::divider();
            $help_url = "//multicache.org/table/documentation/urls/";
            JToolbarHelper::help('COM_MULTICACHE_VIEW_MULTICACHE_HELP', false, $help_url);
        }
        $app = JFactory::getApplication();
        
        $typeflagobj = new stdClass();
        $typeflagobj->value = 'google';
        $typeflagobj->text = 'COM_MULTICACHE_OPTIONS_URL_VIEW_GOOGLE';
        $typeflagobj->disable = false;
        $typeflagobj2 = new stdClass();
        $typeflagobj2->value = 'manual';
        $typeflagobj2->text = 'COM_MULTICACHE_OPTIONS_URL_VIEW_MANUAL';
        $typeflagobj2->disable = false;
        $typeflagarray = array(
            $typeflagobj,
            $typeflagobj2
        );
        
        JHtmlSidebar::setAction('index.php?option=com_multicache&view=urls');
        
        JHtmlSidebar::addFilter(JText::_('JOPTION_COM_MULTICACHE_SELECT_SHOW_TYPE_RESULTS'), 'filter_typeflag', JHtml::_('select.options', $typeflagarray, 'value', 'text', $this->state->get('filter.typeflag'), true));
        
        // JHtml::_('select.options', $simarray ,'value', 'text', $this->state->get('filter.datefrom'), true);
        
        // $this->extra_sidebar = '';
        //
    }

    protected function getSortFields()
    {

        return array();
    
    }

    protected function getActiveFilters()
    {

        $filters = array();
        
        foreach ($this->state as $key => $st)
        {
            if (stristr($key, 'filter.'))
            {
                if (! empty($st))
                {
                    $filters[$key] = $st;
                }
            }
        }
        Return $filters;
    
    }

}