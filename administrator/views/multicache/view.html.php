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

class MulticacheViewMulticache extends JViewLegacy
{

    protected $client;

    protected $data;

    protected $pagination;

    protected $state;

    public function display($tpl = null)
    {

        $this->data = $this->get('Data');
        $this->client = $this->get('Client');
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');
        $this->get('PingStats');
        $this->hitstats = $this->get('Hitstats');
        
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
        JToolbarHelper::title(JText::_('COM_MULTICACHE_CLEAR_CACHE'), 'lightning clear');
        $formPath = JPATH_COMPONENT_ADMINISTRATOR . '/views/config';
        if (file_exists($formPath))
        {
            
            if ($canDo->get('core.edit'))
            {
                
                JToolBarHelper::custom('config.getconfig', 'apply', 'apply_f2.png', 'config', false);
            }
        }
        JToolbarHelper::custom('delete', 'delete.png', 'delete_f2.png', 'JTOOLBAR_DELETE', true);
        JToolbarHelper::divider();
        if (JFactory::getUser()->authorise('core.admin', 'com_multicache'))
        {
            JToolbarHelper::preferences('com_multicache');
        }
        JToolbarHelper::divider();
        $help_url = "//multicache.org/table/documentation/group-cache/";
        JToolbarHelper::help('COM_MULTICACHE_VIEW_MULTICACHE_HELP', false, $help_url);
        
        JHtmlSidebar::setAction('index.php?option=com_multicache');
        
        JHtmlSidebar::addFilter(

        '', 'filter_client_id', JHtml::_('select.options', MulticacheHelper::getClientOptions(), 'value', 'text', $this->state->get('clientId')));
        $config = JFactory::getConfig();
        
        if ($config->get('cache_handler') == 'fastcache')
        {
            JHtmlSidebar::addFilter(

            JText::_('JOPTION_SELECT_CACHE_TYPES'), 'filter_cache_type', JHtml::_('select.options', MulticacheHelper::getCacheTypes(), 'value', 'text', $this->state->get('cacheType')));
        }
    
    }

}