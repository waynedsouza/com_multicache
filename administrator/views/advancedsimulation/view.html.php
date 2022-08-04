<?php

/*
 * @version 1.0.1.5
 * @package com_multicache
 * @copyright Copyright (C) Multicache.org 2015. All rights reserved.
 * @licenseEULA see LICENSE.txt - http://multicache.org/terms-conditions/end-user-license-agreement.html
 * @author Wayne DSouza <consulting@OnlineMarketingConsultants.in> - http://OnlineMarketingConsultants.in
 */
// No direct access
defined('_JEXEC') or die();

jimport('joomla.application.component.view');
JLoader::register('RadioOptions', JPATH_COMPONENT . '/lib/radiooptions.php');

class MulticacheViewAdvancedsimulation extends JViewLegacy
{

    protected $items;

    protected $pagination;

    protected $state;

    public function display($tpl = null)
    {

        $this->state = $this->get('State');
        $this->handlers = $this->get('Distcachehandlers');
        $this->test_pages = $this->get('Distpages');
        $this->precache_options = $this->get('Distprecacheoptions');
        $this->lz_options = $this->get('Distlzoptions');
        $this->mdistribution = $this->get('Distmdistribution');
        $this->Items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->multicacheconfig = $this->get('MulticacheConfig');
        $this->statglobal = $this->get('globalStat');
        $this->form = $this->get('form');
        // $this->_params = JComponentHelper::getParams();
        // $this->formmd = $this->form->getField('multicachedistribution');
        // $this->formmdoptions = RadioOptions::getOptionObj($this->formmd);
        $this->activeFilters = $this->getActiveFilters();
        $this->filtermessage = $this->preparefiltermessage();
        $this->testgroup_stats = $this->get('TestGroupStats');
        $this->get('LastTestCheck');
        /*
         * A check for plugin
         * availability
         * activation
         */
        $this->get('CheckMulticachePlugin');
        
        if (count($errors = $this->get('Errors')))
        {
            throw new Exception(implode("\n", $errors));
        }
        
        $this->addToolbar();
        $this->sidebar = JHtmlSidebar::render();
        $date_from_setting = $this->state->get('filter.datefrom');
        $date_to_setting = $this->state->get('filter.dateto');
        
        // string up the date fields to the sidebar
        $this->sidebar = $this->sidebar . '<div class="span12 small" style="position:relative;margin:10px;"><div><em>from date</div></em>' . JHtml::calendar($date_from_setting, 'filter_datefrom', 'filter_datefrom', '%d-%m-%Y', array(
            'size' => '11',
            'maxlength' => '10',
            'class' => ' span12 small',
            'title' => 'Select from date',
            'data-original-title' => "Select the date from which you like results",
            'onchange' => 'this.form.submit()'
        )) . "</div>" . '<div class="span12 small" style="position:relative;margin:10px"><div><em>to date</div></em>' . JHtml::calendar($date_to_setting, 'filter_dateto', 'filter_dateto', '%d-%m-%Y', array(
            'size' => '11',
            'maxlength' => '10',
            'class' => ' span12 small',
            'title' => 'Select to date',
            'data-original-title' => "Select the date upto which you like results",
            'onchange' => 'this.form.submit()'
        )) . "</div>";
        
        parent::display($tpl);
    
    }

    /**
     * Add the page title and toolbar.
     *
     * @since 1.6
     */
    protected function addToolbar()
    {

        require_once JPATH_COMPONENT . '/helpers/multicache.php';
        
        $state = $this->get('State');
        $canDo = MulticacheHelper::getActions($state->get('filter.category_id'));
        
        JToolBarHelper::title(JText::_('COM_MULTICACHE_TITLE_ADVANCED_SIMULATION_DASHBOARD'), 'advancedsimulation.png');
        
        // Check if the form exists before showing the add/edit buttons
        $formPath = JPATH_COMPONENT_ADMINISTRATOR . '/views/config';
        if (file_exists($formPath))
        {
            
            if ($canDo->get('core.edit'))
            {
                // JToolBarHelper::addNew('config.add', 'COM_MULTICACHE_CONFIG_SETTINGS');
                JToolBarHelper::custom('config.getconfig', 'apply', 'apply_f2.png', 'config', false);
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
                JToolBarHelper::custom('advancedsimulation.publish', 'publish.png', 'publish_f2.png', 'JTOOLBAR_PUBLISH', true);
                JToolBarHelper::custom('advancedsimulation.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
            }
            else if (isset($this->Items[0]))
            {
                JToolBarHelper::divider();
                // If this component does not use state then show a direct delete button as we can not trash
                JToolBarHelper::deleteList('', 'advancedsimulation.delete', 'JTOOLBAR_DELETE');
            }
            
            if (isset($this->items[0]->state))
            {
                JToolBarHelper::divider();
                JToolBarHelper::archiveList('advancedsimulation.archive', 'JTOOLBAR_ARCHIVE');
            }
            if (isset($this->items[0]->checked_out))
            {
                JToolBarHelper::custom('advancedsimulation.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
            }
        }
        
        // Show trash and delete for components that uses the state field
        if (isset($this->items[0]->state))
        {
            if ($state->get('filter.state') == - 2 && $canDo->get('core.delete'))
            {
                JToolBarHelper::deleteList('', 'advancedsimulation.delete', 'JTOOLBAR_EMPTY_TRASH');
                JToolBarHelper::divider();
            }
            else if ($canDo->get('core.edit.state'))
            {
                JToolBarHelper::trash('advancedsimulation.trash', 'JTOOLBAR_TRASH');
                JToolBarHelper::divider();
            }
        }
        
        if ($canDo->get('core.admin'))
        {
            JToolBarHelper::preferences('com_multicache');
            JToolbarHelper::divider();
            $help_url = "//multicache.org/table/documentation/simulation-dashboard/";
            JToolbarHelper::help('COM_MULTICACHE_VIEW_CONFIG_HELP', false, $help_url);
        }
        $app = JFactory::getApplication();
        if (! empty($this->handlers))
        {
            foreach ($this->handlers as $key => $handle)
            {
                $handler_obj[$key] = new stdClass();
                $handler_obj[$key]->value = $handle;
                $handler_obj[$key]->text = $handle;
                $handler_obj[$key]->disable = false;
            }
        }
        if (! empty($this->test_pages))
        {
            foreach ($this->test_pages as $key => $page)
            {
                $page_obj[$key] = new stdClass();
                $page_obj[$key]->value = $page;
                $page_obj[$key]->text = $page;
                $page_obj[$key]->disable = false;
            }
        }
        /*
         * foreach ($this->mdistribution as $key => $mdist)
         * {
         *
         * $mdist_obj[$key] = new stdClass();
         * $mdist_obj[$key]->value = $mdist;
         * $mdist_obj[$key]->text = $this->formmdoptions[$mdist];
         * $mdist_obj[$key]->disable = false;
         * }
         */
        if (! empty($this->precache_options))
        {
            foreach ($this->precache_options as $key => $precache_factor_option)
            {
                $precache_factor_obj[$key] = new stdClass();
                $precache_factor_obj[$key]->value = $precache_factor_option;
                $precache_factor_obj[$key]->text = $precache_factor_option;
                $precache_factor_obj[$key]->disable = false;
            }
        }
        if (! empty($this->lz_options))
        {
            foreach ($this->lz_options as $key => $lz_factor_option)
            {
                $lz_factor_obj[$key] = new stdClass();
                $lz_factor_obj[$key]->value = $lz_factor_option;
                $lz_factor_obj[$key]->text = $lz_factor_option;
                $lz_factor_obj[$key]->disable = false;
            }
        }
        
        // Set sidebar action - New in 3.0
        JHtmlSidebar::setAction('index.php?option=com_multicache&view=advancedsimulation');
        JHtmlSidebar::addFilter(JText::_('JOPTION_SELECT_SIMULATION'), 'filter_simflag', JHtml::_('select.options', MulticacheHelper::getSimObj(), 'value', 'text', $this->state->get('filter.simflag'), true));
        JHtmlSidebar::addFilter(JText::_('JOPTION_SELECT_SHOW_INPROGRESS_RESULTS'), 'filter_completeflag', JHtml::_('select.options', MulticacheHelper::getCompleteFlag(), 'value', 'text', $this->state->get('filter.completeflag'), true));
        JHtmlSidebar::addFilter(JText::_('JOPTION_SELECT_TOLERANCE_RESULTS'), 'filter_toleranceflag', JHtml::_('select.options', MulticacheHelper::getTolerances(), 'value', 'text', $this->state->get('filter.toleranceflag'), true));
        
        if (! empty($handler_obj))
        {
            JHtmlSidebar::addFilter(JText::_('JOPTION_SELECT_HANDLERS_RESULTS'), 'filter_handlersflag', JHtml::_('select.options', $handler_obj, 'value', 'text', $this->state->get('filter.handlersflag'), true));
        }
        if (! empty($page_obj))
        {
            JHtmlSidebar::addFilter(JText::_('JOPTION_SELECT_TEST_PAGES_FILTER_RESULTS'), 'filter_testpagesflag', JHtml::_('select.options', $page_obj, 'value', 'text', $this->state->get('filter.testpagesflag'), true));
        }
        if (! empty($precache_factor_obj))
        {
            JHtmlSidebar::addFilter(JText::_('JOPTION_SELECT_PRECACHE_FACTOR_FILTER_RESULTS'), 'filter_precacheflag', JHtml::_('select.options', $precache_factor_obj, 'value', 'text', $this->state->get('filter.precacheflag'), true));
        }
        if (! empty($lz_factor_obj))
        {
            JHtmlSidebar::addFilter(JText::_('JOPTION_SELECT_LZ_FACTOR_FILTER_RESULTS'), 'filter_lzfactorflag', JHtml::_('select.options', $lz_factor_obj, 'value', 'text', $this->state->get('filter.lzfactorflag'), true));
        }
        
        JHtmlSidebar::addFilter(JText::_('JOPTION_SELECT_HAMMER_MODE_FILTER_RESULTS'), 'filter_hammerflag', JHtml::_('select.options', MulticacheHelper::getHammerOptions(), 'value', 'text', $this->state->get('filter.hammerflag'), true));
    
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
                if (! empty($st) || $st != '')
                {
                    $filters[$key] = $st;
                }
            }
        }
        
        Return $filters;
    
    }

    protected function preparefiltermessage()
    {

        if (empty($this->activeFilters))
        {
            Return '';
        }
        $filtermessage = '';
        $search_matrix = array(
            'filter.',
            'flag'
        );
        $hammeroptions = MulticacheHelper::getHammerOptions();
        foreach ($this->activeFilters as $key => $message)
        {
            // $filtermessage .= '<em style=\"padding-left:3em;\">'.str_ireplace($search_matrix , '' ,$key).' - '.$message.'</em> ';
            $key = str_ireplace($search_matrix, '', $key);
            $filtermessage .= '<em style=\"display:inline;padding-left:2em;\">' . JText::_('COM_MULTICACHE_DASHBOARD_SUMMARY_' . strtoupper($key)) . ' -    ';
            if ($key == 'testpages' || $key == 'precache' || $key == 'lzfactor' || $key == 'datefrom' || $key == 'dateto')
            {
                $filtermessage .= JText::_(strtolower($message)) . '</em>';
            }
            elseif ($key == 'hammer')
            {
                $textobj = $hammeroptions[$message];
                $filtermessage .= JText::_($textobj->text) . '</em>';
            }
            else
            {
                $filtermessage .= JText::_('COM_MULTICACHE_DASHBOARD_SUMMARY_MESSAGE_' . strtoupper($message)) . '</em>';
            }
        }
        Return $filtermessage;
    
    }

}