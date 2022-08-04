<?php

/**
 *
 * @version 1.0.1.5
 * @package com_multicache
 * @copyright Copyright (C) Multicache.org 2015. All rights reserved.
 * @licenseEULA see LICENSE.txt - http://multicache.org/terms-conditions/end-user-license-agreement.html
 * @author Wayne DSouza <consulting@OnlineMarketingConsultants.in> - http://OnlineMarketingConsultants.in
 */
defined('_JEXEC') or die();

jimport('joomla.application.component.modellist');
JLoader::register('Loadinstruction', JPATH_ROOT . '/components/com_multicache/lib/loadinstruction.php');

/**
 * Methods supporting a list of Multicache records.
 */
class MulticacheModelAdvancedsimulation extends JModelList
{

    /**
     * Constructor.
     *
     * @param
     *        array An optional associative array of configuration settings.
     * @see JController
     * @since 1.6
     */
    public function __construct($config = array())
    {

        if (empty($config['filter_fields']))
        {
            $config['filter_fields'] = array(
                'id',
                'a.id',
                'group_id',
                'a.group_id',
                'page_load_time',
                'a.page_load_time',
                'mtime',
                'a.mtime',
                'html_load_time',
                'a.html_load_time',
                'precache_factor',
                'a.precache_factor',
                'gzip_factor',
                'a.gzip_factor',
                'pagespeed_score',
                'a.pagespeed_score',
                'yslow_score',
                'a.yslow_score',
                'page_elements',
                'a.page_elements',
                'html_bytes',
                'a.html_bytes',
                'page_bytes',
                'a.page_bytes',
                'test_date',
                'a.test_date'
            );
        }
        
        parent::__construct($config);
    
    }

    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     */
    protected function populateState($ordering = null, $direction = null)
    {
        // Initialise variables.
        $app = JFactory::getApplication('administrator');
        
        // Load the filter state.
        $search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);
        
        $published = $app->getUserStateFromRequest($this->context . '.filter.state', 'filter_published', '', 'string');
        $this->setState('filter.state', $published);
        
        $sim_flag = $app->getUserStateFromRequest($this->context . '.filter.simflag', 'filter_simflag', '', 'string');
        $this->setState('filter.simflag', $sim_flag);
        
        $complete_flag = $app->getUserStateFromRequest($this->context . '.filter.completeflag', 'filter_completeflag', '', 'string');
        $this->setState('filter.completeflag', $complete_flag);
        
        $tolerance_flag = $app->getUserStateFromRequest($this->context . '.filter.toleranceflag', 'filter_toleranceflag', '', 'string');
        $this->setState('filter.toleranceflag', $tolerance_flag);
        
        $date_flag_from = $app->getUserStateFromRequest($this->context . '.filter.datefrom', 'filter_datefrom', '', 'string');
        $this->setState('filter.datefrom', $date_flag_from);
        
        $date_flag_to = $app->getUserStateFromRequest($this->context . '.filter.dateto', 'filter_dateto', '', 'string');
        $this->setState('filter.dateto', $date_flag_to);
        
        $handlers_flag = $app->getUserStateFromRequest($this->context . '.filter.handlersflag', 'filter_handlersflag', '', 'string');
        $this->setState('filter.handlersflag', $handlers_flag);
        
        $testpages_flag = $app->getUserStateFromRequest($this->context . '.filter.testpagesflag', 'filter_testpagesflag', '', 'string');
        $this->setState('filter.testpagesflag', $testpages_flag);
        
        $precache_flag = $app->getUserStateFromRequest($this->context . '.filter.precacheflag', 'filter_precacheflag', '', 'string');
        $this->setState('filter.precacheflag', $precache_flag);
        
        $lzfactor_flag = $app->getUserStateFromRequest($this->context . '.filter.lzfactorflag', 'filter_lzfactorflag', '', 'string');
        $this->setState('filter.lzfactorflag', $lzfactor_flag);
        
        $hammer_flag = $app->getUserStateFromRequest($this->context . '.filter.hammerflag', 'filter_hammerflag', '', 'string');
        $this->setState('filter.hammerflag', $hammer_flag);
        
        // Load the parameters.
        $params = JComponentHelper::getParams('com_multicache');
        $this->setState('params', $params);
        
        // List state information.
        parent::populateState('a.id', 'DESC');
    
    }

    public function getForm($data = array(), $loadData = true)
    {
        
        // Initialise variables.
        $app = JFactory::getApplication();
        
        // Get the form.
        $form = $this->loadForm('com_multicache.config', 'config', array(
            'control' => 'jform',
            'load_data' => $loadData
        ));
        
        if (empty($form))
        {
            return false;
        }
        
        return $form;
    
    }

    public function getMulticacheConfig()
    {

        static $config = null;
        if (isset($config))
        {
            Return $config;
        }
        $db = JFactory::getDbo();
        $query = $db->getQuery('true');
        $query->SELECT('*');
        $query->from($db->quoteName('#__multicache_config'));
        $query->where($db->quoteName('id') . ' = ' . $db->quote('1'));
        $db->setQuery($query);
        $result = $db->loadObject();
        if (empty($result))
        {
            Return false;
        }
        $config = $result;
        return $config;
    
    }

    public function getLastTestCheck()
    {

        $multicacheconfig = $this->getMulticacheConfig();
        if (empty($multicacheconfig))
        {
            Return;
        }
        
        if ($multicacheconfig->gtmetrix_testing && $multicacheconfig->gtmetrix_allow_simulation && $multicacheconfig->simulation_advanced)
        {
            $this->checkLastTest();
            
            if (class_exists('MulticachePageScripts') && property_exists('MulticachePageScripts', 'working_script_array'))
            {
                Return true;
            }
            $app = JFactory::getApplication();
            $app->enqueueMessage(JText::_('COM_MULTICACHE_ADVANCED_SETTINGS_REQUIRES_JAVASCRIPT_INIT_ERROR'), 'notice');
        }
    
    }

    public function getglobalStat()
    {

        $sim_flag = $this->getState('filter.simflag');
        $complete_flag = $this->getState('filter.completeflag');
        $tolerance_flag = $this->getState('filter.toleranceflag');
        $datefrom_flag = $this->getState('filter.datefrom');
        $dateto_flag = $this->getState('filter.dateto');
        $handlers_flag = $this->getState('filter.handlersflag');
        $testpages_flag = $this->getState('filter.testpagesflag');
        $precache_flag = $this->getState('filter.precacheflag');
        $lz_factor_flag = $this->getState('filter.lzfactorflag');
        $hammer_flag = $this->getState('filter.hammerflag');
        $filters = ($sim_flag != '') || ($complete_flag != '') || ($datefrom_flag != '') || ($dateto_flag != '') || ($handlers_flag != '') || ($testpages_flag != '') || ($precache_flag != '') || ($lz_factor_flag != '') || ($hammer_flag != '') ? true : null;
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->Select(' AVG(' . $db->quoteName('page_load_time') . ') as average_page_load_time');
        $query->Select(' VARIANCE(' . $db->quoteName('page_load_time') . ') as variance_page_load_time');
        $query->Select(' MIN(' . $db->quoteName('page_load_time') . ') as minimum_page_load_time');
        $query->Select(' MAX(' . $db->quoteName('page_load_time') . ') as maximum_page_load_time');
        $query->Select(' STDDEV(' . $db->quoteName('page_load_time') . ') as standarddeviation_page_load_time');
        $query->from($db->quoteName('#__multicache_advanced_test_results') . ' As a');
        if (! empty($sim_flag))
        {
            $sim_var = ($sim_flag == 'simulation') ? 'simulation' : 'off';
            $query->where($db->quoteName('simulation') . ' = ' . $db->quote($sim_var));
        }
        if (! empty($complete_flag))
        {
            $complete_var = ($complete_flag == 'show_only_complete') ? 'complete' : NULL;
            if ($complete_var)
            {
                $query->where($db->quoteName('status') . ' = ' . $db->quote($complete_var));
            }
            else
            {
                $query->where($db->quoteName('status') . ' != ' . $db->quote('complete'));
            }
        }
        $multicacheconfig = $this->getMulticacheConfig();
        if (! empty($tolerance_flag) && ! empty($multicacheconfig))
        {
            
            $params = $this->state->get('params');
            $params = json_decode($params);
            
            if ($tolerance_flag == 'show_danger_tolerance')
            {
                $query->where($db->quoteName('page_load_time') . ' > ' . $db->quote($params->danger_tolerance_factor * $multicacheconfig->targetpageloadtime * 1000));
                $query->where($db->quoteName('status') . ' =' . $db->quote('complete'));
            }
            elseif ($tolerance_flag == 'show_warning_tolerance')
            {
                $query->where($db->quoteName('page_load_time') . ' < ' . $db->quote($params->danger_tolerance_factor * $multicacheconfig->targetpageloadtime * 1000));
                $query->where($db->quoteName('page_load_time') . ' > ' . $db->quote($params->warning_tolerance_factor * $multicacheconfig->targetpageloadtime * 1000));
                $query->where($db->quoteName('status') . ' = ' . $db->quote('complete'));
            }
            elseif ($tolerance_flag == 'show_success_tolerance')
            {
                $query->where($db->quoteName('page_load_time') . ' < ' . $db->quote($multicacheconfig->targetpageloadtime * 1000));
                $query->where($db->quoteName('status') . ' = ' . $db->quote('complete'));
            }
            elseif ($tolerance_flag == 'show_unhighlighted_tolerance')
            {
                $query->where($db->quoteName('page_load_time') . ' > ' . $db->quote($multicacheconfig->targetpageloadtime * 1000));
                $query->where($db->quoteName('page_load_time') . ' < ' . $db->quote($params->warning_tolerance_factor * $multicacheconfig->targetpageloadtime * 1000));
                $query->where($db->quoteName('status') . ' = ' . $db->quote('complete'));
            }
            // $query->where($db->quoteName('simulation') .' = '. $db->quote($sim_var));
        }
        if (! empty($datefrom_flag))
        {
            $convert_date = strtotime($datefrom_flag);
            $converted_date = date("Y-m-d", $convert_date);
            $query->where($db->quoteName('test_date') . ' >= ' . $db->quote($converted_date));
        }
        if (! empty($dateto_flag))
        {
            $convert_date = strtotime($dateto_flag);
            $converted_date = date("Y-m-d", $convert_date);
            $query->where($db->quoteName('test_date') . ' <= ' . $db->quote($converted_date));
        }
        if (! empty($handlers_flag))
        {
            
            $query->where($db->quoteName('cache_handler') . ' = ' . $db->quote($handlers_flag));
        }
        
        if (! empty($testpages_flag))
        {
            
            $query->where($db->quoteName('test_page') . ' = ' . $db->quote($testpages_flag));
        }
        
        if ($precache_flag != '')
        {
            
            $query->where($db->quoteName('precache_factor') . ' = ' . $db->quote($precache_flag));
        }
        
        if ($lz_factor_flag != '')
        {
            
            $query->where($db->quoteName('cache_compression_factor') . ' = ' . $db->quote($lz_factor_flag));
        }
        if ($hammer_flag != '')
        {
            $query->where($db->quoteName('hammer_mode') . ' = ' . $db->quote($hammer_flag));
        }
        if (! isset($filters))
        {
            $query->where($db->quoteName('status') . ' = ' . $db->quote('complete'));
        }
        
        $db->setQuery($query);
        $statobj = $db->loadObject();
        
        Return $statobj;
    
    }

    public function getDistpages()
    {

        $db = JFactory::getDbo();
        $query = $db->getQuery('true');
        $query->select('DISTINCT ' . $db->quoteName('test_page'));
        $query->from($db->quoteName('#__multicache_advanced_test_results'));
        $db->setQuery($query);
        $result = $db->loadColumn();
        Return $result;
    
    }

    public function getDistmdistribution()
    {

        $db = JFactory::getDbo();
        $query = $db->getQuery('true');
        $query->select('DISTINCT ' . $db->quoteName('hammer_mode'));
        $query->from($db->quoteName('#__multicache_advanced_test_results'));
        $db->setQuery($query);
        $result = $db->loadColumn();
        Return $result;
    
    }

    public function getDistcachehandlers()
    {

        $db = JFactory::getDbo();
        $query = $db->getQuery('true');
        $query->select('DISTINCT ' . $db->quoteName('cache_handler'));
        $query->from($db->quoteName('#__multicache_advanced_test_results'));
        $query->where($db->quoteName('cache_handler') . ' != ' . $db->quote(''));
        $db->setQuery($query);
        $result = $db->loadColumn();
        Return $result;
    
    }

    public function getDistlzoptions()
    {

        $db = JFactory::getDbo();
        $query = $db->getQuery('true');
        $query->select('DISTINCT ' . $db->quoteName('cache_compression_factor'));
        $query->from($db->quoteName('#__multicache_advanced_test_results'));
        $db->setQuery($query);
        $result = $db->loadColumn();
        Return $result;
    
    }

    public function getDistprecacheoptions()
    {

        $db = JFactory::getDbo();
        $query = $db->getQuery('true');
        $query->select('DISTINCT ' . $db->quoteName('precache_factor'));
        $query->from($db->quoteName('#__multicache_advanced_test_results'));
        $db->setQuery($query);
        $result = $db->loadColumn();
        Return $result;
    
    }

    public function getItems()
    {

        $items = parent::getItems();
        
        return $items;
    
    }

    public function delete($pks)
    {

        $app = JFactory::getApplication();
        
        $user = JFactory::getUser();
        if (! $user->authorise('core.delete', $this->option))
        {
            $app->enqueueMessage('COM_MULTICACHE_SIMULATIONDASHBOARD_USER_NOT_AUTHORISED_TO_DELETE');
            Return false;
        }
        $db = JFactory::getDBO();
        
        foreach ($pks as $i => $pk)
        {
            
            $query = $db->getQuery('true');
            $conditions = array(
                $db->quoteName('id') . ' = ' . $pk
            );
            $query->delete($db->quoteName('#__multicache_advanced_test_results'));
            $query->where($conditions);
            
            $db->setQuery($query);
            
            $result = $db->execute();
        }
    
    }

    public function getCheckMulticachePlugin()
    {

        $app = JFactory::getApplication();
        $isenabled = JPluginHelper::isEnabled("system", "multicache");
        if (! $isenabled)
        {
            $app->enqueueMessage(JText::_('COM_MULTICACHE_CONFIG_SYSTEM_PLUGIN_NOT_ENABLED_MESSAGE'), 'warning');
        }
        $isenabled = JPluginHelper::isEnabled("system", "cache");
        if (! $isenabled)
        {
            $app->enqueueMessage(JText::_('COM_MULTICACHE_CONFIG_SYSTEM_PAGE_CACHE_PLUGIN_NOT_ENABLED_MESSAGE'), 'warning');
        }
    
    }

    public function getTestGroupStats()
    {

        $app = JFactory::getApplication();
        $test_group = $this->getLastTestGroup();
        if (empty($test_group))
        {
            Return false;
        }
        $multicacheconfig = $this->getMulticacheConfig();
        if (empty($multicacheconfig))
        {
            Return false;
        }
        $testgroup_stats = new stdClass();
        $testgroup_stats->cycles = $test_group->cycles;
        $testgroup_stats->cycles_complete = $test_group->cycles_complete;
        $testgroup_stats->expected_tests = $test_group->expected_tests;
        $testgroup_stats->advanced = $multicacheconfig->simulation_advanced ? 'advanced' : 'normal';
        $testgroup_stats->start_time = $test_group->start_time;
        $testgroup_stats->group_id = $test_group->id;
        $testgroup_stats->remaining_tests = $test_group->expected_tests; // init incase test info is not available
        $test_info = $this->getTestsbyGroup($testgroup_stats->group_id);
        
        if (empty($test_info))
        {
            $testgroup_stats->remaining_tests = JText::_('COM_MULTICACHE_ADVANCED_SIMULATION_UPDATING');
            $testgroup_stats->testsperday = JText::_('COM_MULTICACHE_ADVANCED_SIMULATION_UPDATING');
            $testgroup_stats->expected_end_date = JText::_('COM_MULTICACHE_ADVANCED_SIMULATION_UPDATING');
            Return $testgroup_stats;
        }
        foreach ($test_info as $key => $test)
        {
            
            if ($test->status == 'complete')
            {
                
                $tests_complete[$key] = $test;
            }
        }
        
        if ($multicacheconfig->simulation_advanced != 1 && $test_group->advanced == 'advanced')
        {
            $min_precache = (int) $multicacheconfig->precache_factor_min;
            $max_precache = (int) $multicacheconfig->precache_factor_max;
            $min_cachecompression = (float) $multicacheconfig->gzip_factor_min; // $min_gzip -> $min_cachecompression
            $max_cachecompression = (float) $multicacheconfig->gzip_factor_max; // $max_gzip -> $max_cachecompression
            $step_cachecompression = (float) $multicacheconfig->gzip_factor_step; // $step_gzip -> $step_cachecompression
            
            $precache_sequences = ($max_precache - $min_precache) + 1;
            $step_cachecompression = empty($step_cachecompression) ? 1 : $step_cachecompression; // filtering the input for 0
            $cachecompression_sequences = (int) (($max_cachecompression - $min_cachecompression) / $step_cachecompression);
            $cachecompression_sequences = ($cachecompression_sequences <= 1) ? 1 : $cachecompression_sequences;
            
            $testgroup_stats->remaining_tests = $cachecompression_sequences * $precache_sequences * $multicacheconfig->gtmetrix_cycles;
            $testgroup_stats->testsperday = $multicacheconfig->gtmetrix_api_budget;
            if ($testgroup_stats->testsperday)
            {
                $expectedendtime = microtime(true) + ($testgroup_stats->remaining_tests / $testgroup_stats->testsperday) * 24 * 60 * 60;
                $testgroup_stats->expected_end_date = date("l jS F ", $expectedendtime);
            }
            else
            {
                $testgroup_stats->expected_end_date = 'na';
            }
            
            Return $testgroup_stats;
        }
        
        if ($multicacheconfig->simulation_advanced == 1 && $test_group->advanced == 'normal')
        {
            
            if (class_exists('Loadinstruction') && property_exists('Loadinstruction', 'loadinstruction'))
            {
                $min_precache = (int) $multicacheconfig->precache_factor_min;
                $max_precache = (int) $multicacheconfig->precache_factor_max;
                $min_cachecompression = (float) $multicacheconfig->gzip_factor_min; // $min_gzip -> $min_cachecompression
                $max_cachecompression = (float) $multicacheconfig->gzip_factor_max; // $max_gzip -> $max_cachecompression
                $step_cachecompression = (float) $multicacheconfig->gzip_factor_step; // $step_gzip -> $step_cachecompression
                
                $precache_sequences = ($max_precache - $min_precache) + 1;
                $step_cachecompression = empty($step_cachecompression) ? 1 : $step_cachecompression; // filtering the input for 0
                $cachecompression_sequences = (int) (($max_cachecompression - $min_cachecompression) / $step_cachecompression);
                $cachecompression_sequences = ($cachecompression_sequences <= 1) ? 1 : $cachecompression_sequences;
                $load_states = empty(Loadinstruction::$loadinstruction) ? 1 : count(Loadinstruction::$loadinstruction);
                $testgroup_stats->testsperday = $multicacheconfig->gtmetrix_api_budget;
                
                $testgroup_stats->remaining_tests = $cachecompression_sequences * $precache_sequences * $multicacheconfig->gtmetrix_cycles * $load_states;
                if ($testgroup_stats->testsperday)
                {
                    $expectedendtime = microtime(true) + ($testgroup_stats->remaining_tests / $testgroup_stats->testsperday) * 24 * 60 * 60;
                    $testgroup_stats->expected_end_date = date("l jS F ", $expectedendtime);
                }
                else
                {
                    $testgroup_stats->expected_end_date = 'na';
                }
                
                Return $testgroup_stats;
            }
            $testgroup_stats->remaining_tests = JText::_('COM_MULTICACHE_ADVANCED_SIMULATION_UPDATING');
            $testgroup_stats->testsperday = JText::_('COM_MULTICACHE_ADVANCED_SIMULATION_UPDATING');
            $testgroup_stats->expected_end_date = JText::_('COM_MULTICACHE_ADVANCED_SIMULATION_UPDATING');
            Return $testgroup_stats;
        }
        $testgroup_stats->remaining_tests = $test_group->expected_tests - count($tests_complete);
        
        $testgroup_stats->testsperday = $test_info[0]->max_tests;
        // see changelog
        if ($testgroup_stats->remaining_tests/*$testgroup_stats->testsperday*/)
        {
            $expectedendtime = microtime(true) + ($testgroup_stats->remaining_tests / $testgroup_stats->testsperday) * 24 * 60 * 60;
            $testgroup_stats->expected_end_date = date("l jS F ", $expectedendtime);
        }
        else
        {
            $testgroup_stats->expected_end_date = 'na';
        }
        
        Return $testgroup_stats;
    
    }

    protected function checkLastTest()
    {

        $app = JFactory::getApplication();
        $db = JFactory::getDBO();
        $query = $db->getQuery('true');
        $query->select('*');
        $query->from($db->quoteName('#__multicache_advanced_test_results'));
        $query->order($db->quoteName('id') . '  DESC');
        $db->setQuery($query);
        $last_test = $db->LoadObject();
        if ($last_test->status == 'test_on_hold') $app->enqueueMessage(JText::_('COM_MULTICACHE_ADVANCED_SIMULATION_LAST_TEST_ON_HOLD_LABEL'), 'error');
    
    }

    protected function getListQuery()
    {

        $sim_flag = $this->getState('filter.simflag');
        $complete_flag = $this->getState('filter.completeflag');
        $tolerance_flag = $this->getState('filter.toleranceflag');
        $datefrom_flag = $this->getState('filter.datefrom');
        $dateto_flag = $this->getState('filter.dateto');
        $handlers_flag = $this->getState('filter.handlersflag');
        $testpages_flag = $this->getState('filter.testpagesflag');
        $precache_flag = $this->getState('filter.precacheflag');
        $lz_factor_flag = $this->getState('filter.lzfactorflag');
        $hammer_flag = $this->getState('filter.hammerflag');
        
        $multicacheconfig = $this->getMulticacheConfig();
        
        $ccomp_step = ! empty($multicacheconfig) ? $multicacheconfig->gzip_factor_step : 0.1;
        
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->Select($this->getState('list.select', 'a.*'));
        $query->from($db->quoteName('#__multicache_advanced_test_results') . ' As a');
        if (! empty($sim_flag))
        {
            $sim_var = ($sim_flag == 'simulation') ? 'simulation' : 'off';
            $query->where($db->quoteName('simulation') . ' = ' . $db->quote($sim_var));
        }
        if (! empty($complete_flag))
        {
            $complete_var = ($complete_flag == 'show_only_complete') ? 'complete' : NULL;
            if ($complete_var)
            {
                $query->where($db->quoteName('status') . ' = ' . $db->quote($complete_var));
            }
            else
            {
                $query->where($db->quoteName('status') . ' != ' . $db->quote('complete'));
            }
        }
        if (! empty($tolerance_flag) && ! empty($multicacheconfig))
        {
            
            $params = $this->state->get('params');
            $params = json_decode($params);
            
            if ($tolerance_flag == 'show_danger_tolerance')
            {
                $query->where($db->quoteName('page_load_time') . ' > ' . $db->quote($params->danger_tolerance_factor * $multicacheconfig->targetpageloadtime * 1000));
                // $query->where($db->quoteName('status') . ' =' . $db->quote('complete'));
            }
            elseif ($tolerance_flag == 'show_warning_tolerance')
            {
                $query->where($db->quoteName('page_load_time') . ' < ' . $db->quote($params->danger_tolerance_factor * $multicacheconfig->targetpageloadtime * 1000));
                $query->where($db->quoteName('page_load_time') . ' > ' . $db->quote($params->warning_tolerance_factor * $multicacheconfig->targetpageloadtime * 1000));
                // $query->where($db->quoteName('status') . ' = ' . $db->quote('complete'));
            }
            elseif ($tolerance_flag == 'show_success_tolerance')
            {
                $query->where($db->quoteName('page_load_time') . ' < ' . $db->quote($multicacheconfig->targetpageloadtime * 1000));
                $query->where($db->quoteName('page_load_time') . ' != ' . $db->quote(0));
                // $query->where($db->quoteName('status') . ' = ' . $db->quote('complete'));
            }
            elseif ($tolerance_flag == 'show_unhighlighted_tolerance')
            {
                $query->where($db->quoteName('page_load_time') . ' > ' . $db->quote($multicacheconfig->targetpageloadtime * 1000));
                $query->where($db->quoteName('page_load_time') . ' < ' . $db->quote($params->warning_tolerance_factor * $multicacheconfig->targetpageloadtime * 1000));
                // $query->where($db->quoteName('status') . ' = ' . $db->quote('complete'));
            }
            // $query->where($db->quoteName('simulation') .' = '. $db->quote($sim_var));
        }
        if (! empty($datefrom_flag))
        {
            $convert_date = strtotime($datefrom_flag);
            $converted_date = date("Y-m-d", $convert_date);
            $query->where($db->quoteName('test_date') . ' >= ' . $db->quote($converted_date));
        }
        if (! empty($dateto_flag))
        {
            $convert_date = strtotime($dateto_flag);
            $converted_date = date("Y-m-d", $convert_date);
            $query->where($db->quoteName('test_date') . ' <= ' . $db->quote($converted_date));
        }
        if (! empty($handlers_flag))
        {
            
            $query->where($db->quoteName('cache_handler') . ' = ' . $db->quote($handlers_flag));
        }
        
        if (! empty($testpages_flag))
        {
            
            $query->where($db->quoteName('test_page') . ' = ' . $db->quote($testpages_flag));
        }
        
        if ($precache_flag != '')
        {
            
            $query->where($db->quoteName('precache_factor') . ' = ' . $db->quote($precache_flag));
        }
        
        if ($lz_factor_flag != '')
        {
            $step_pre = (float) $lz_factor_flag - 0.1 * (float) $ccomp_step;
            $step_post = (float) $lz_factor_flag + 0.1 * (float) $ccomp_step;
            $query->where($db->quoteName('cache_compression_factor') . ' < ' . $db->quote($step_post));
            $query->where($db->quoteName('cache_compression_factor') . ' > ' . $db->quote($step_pre));
        }
        if ($hammer_flag != '')
        {
            $query->where($db->quoteName('hammer_mode') . ' = ' . $db->quote($hammer_flag));
        }
        $orderCol = $this->state->get('list.ordering');
        $orderDirn = $this->state->get('list.direction');
        $query->order($db->escape($orderCol . '  ' . $orderDirn));
        
        return $query;
    
    }

    protected function getLastTestGroup()
    {

        $db = JFactory::getDBO();
        $query = $db->getQuery('true');
        $query->select('*');
        $query->from($db->quoteName('#__multicache_advanced_testgroups'));
        $query->order($db->quoteName('id') . '  DESC');
        $db->setQuery($query);
        Return $db->LoadObject();
    
    }

    protected function getStoreId($id = '')
    {
        // Compile the store id.
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.state');
        
        return parent::getStoreId($id);
    
    }

    protected function getTestsbyGroup($id)
    {

        $db = JFactory::getDBO();
        $query = $db->getQuery('true');
        $query->select('*');
        $query->from($db->quoteName('#__multicache_advanced_test_results'));
        $query->where($db->quoteName('group_id') . ' = ' . $db->quote($id));
        
        $query->order($db->quoteName('id') . '  DESC');
        $db->setQuery($query);
        Return $db->LoadObjectList();
    
    }

}