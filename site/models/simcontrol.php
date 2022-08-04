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
JLoader::register('Services_WTF_Test', JPATH_ADMINISTRATOR . '/components/com_multicache/lib/Services_WTF_Test.php');
jimport('joomla.log.log');
JLog::addLogger(array(
    'text_file' => 'errors.php'
), JLog::ALL, array(
    'errors'
));
JLoader::register('MulticachePageScripts', JPATH_ROOT . '/administrator/components/com_multicache/lib/pagescripts.php');
JLoader::register('MulticacheFrontendHelper', JPATH_ROOT . '/components/com_multicache/helpers/multicache.php');
JLoader::register('JsStrategy', JPATH_ROOT . '/administrator/components/com_multicache/lib/jscachestrategy.php');
jimport('joomla.application.component.modeladmin');

class MulticacheModelSimcontrol extends JModelList
{
    // loadinstruc used to create the loadinstruction class
    public static $_loadinstruction = null;

    public static $_working_script_array_loadinstruction = null;

    public static $_original_script_array_loadinstruction = null;

    public static $_preset_script_array = null;
    // stores group name as key and url as value. The url points to the loaction the group script is located in
    protected static $_groups = null;
    // a group needs to be loaded only once. This array stores as Keys the groups that are loaded in the loadsection iteration
    protected static $_groups_loaded = null;
    
    // if class exists set at constructloadinstruc used in simulation and testing
    protected static $_linstruction = null;

    protected static $_loadsections = null;

    protected $_lnparams = null;

    protected $_test_group = null;

    protected static $_principle_jquery_scope = null;

    protected static $_mediaVersion = null;

    protected static $_delayable_segment = null;

    protected static $_social_segment = null;

    protected static $_advertisement_segment = null;

    protected static $_async_segment = null;

    protected static $_cdn_segment = null;

    protected static $_signature_hash = null;

    protected $_advanced_simulation = null;

    protected static $_jscomments = null;

    protected static $_simcontrol_pagescript = null;

    protected static $_excluded_components = null;

    protected static $dontmove_urls_js = null;

    protected static $dontmove_js = null;

    public function __construct($properties = null)
    {

        parent::__construct($properties);
        if ($this->_lnparams == null)
        {
            $this->_lnparams = $this->getlnparams();
        }
        
        if (! empty($this->_lnparams->debug_mode))
        {
            define("MULTICACHESIMULATIONDEBUG", true);
        }
        
        if (class_exists('Loadinstruction') && property_exists('Loadinstruction', 'loadinstruction'))
        {
            self::$_linstruction = Loadinstruction::$loadinstruction;
        }
        if ($this->_test_group == null)
        {
            $this->_test_group = $this->gettestgroup($this->_lnparams->simulation_advanced);
        }
        if (! isset(self::$_principle_jquery_scope) && $this->_test_group->advanced == 'advanced')
        {
            $this->setprincipleJqueryscopeoperator();
        }
        if (! isset(self::$_mediaVersion))
        {
            self::$_mediaVersion = MulticacheFrontendHelper::getMediaFormat();
        }
    
    }

    /*
     * A function that can be called from anywhere prior to simcontrol that initialises the
     * important class for simcontrol.
     * Currently this is being called from config on save.
     */
    public function getSimcontrol($page_obj = null)
    {

        $app = JFactory::getApplication();
        if (isset($page_obj))
        {
            self::$_simcontrol_pagescript = $page_obj;
        }
        
        $script_count = $this->getScriptCount();
        // $loadpriority = $this->getWorkingscriptloadinstruc();
        $this->setLoadInstructionArray('working_script_array', 1);
        $this->setLoadInstructionArray('original_script_array', 1);
        
        self::$_preset_script_array = empty(self::$_working_script_array_loadinstruction) ? null : self::$_working_script_array_loadinstruction;
        $this->getLoadinstruction($script_count);
        $pageScripts = $this->getPageScripts();
        $allow_multiple_orphaned = property_exists('JsStrategy', 'allow_multiple_orphaned') ? JsStrategy::$allow_multiple_orphaned : null;
        $return = Multicachefrontendhelper::writeLoadInstructions(self::$_preset_script_array, self::$_loadinstruction, self::$_working_script_array_loadinstruction, self::$_original_script_array_loadinstruction, $pageScripts, $allow_multiple_orphaned);
        if ($return)
        {
            $app->enqueueMessage(JText::_('COM_MULTICACHE_SIMCONTROL_GETSIMCONTROL_CHANGES_DEVOLVED'), 'message');
        }
        Return $return;
    
    }

    protected function getPageScripts()
    {

        if (! class_exists('MulticachePageScripts') && ! isset(self::$_simcontrol_pagescript))
        {
            Return false;
        }
        if (property_exists('MulticachePageScripts', 'working_script_array'))
        {
            $page_scripts = array();
            $page_scripts['working_script_array'] = $this->loadProperty('working_script_array', 'MulticachePageScripts');
            $page_scripts['delayed'] = $this->loadProperty('delayed', 'MulticachePageScripts');
            $page_scripts['social'] = $this->loadProperty('social', 'MulticachePageScripts');
            $page_scripts['advertisements'] = $this->loadProperty('advertisements', 'MulticachePageScripts');
            $page_scripts['async'] = $this->loadProperty('async', 'MulticachePageScripts');
            $page_scripts['dontmove'] = $this->loadProperty('dontmove', 'MulticachePageScripts');
        }
        elseif (isset(self::$_simcontrol_pagescript['working_script_array']))
        {
            $page_scripts = array();
            $page_scripts['working_script_array'] = ! empty(self::$_simcontrol_pagescript['working_script_array']) ? self::$_simcontrol_pagescript['working_script_array'] : null;
            $page_scripts['delayed'] = ! empty(self::$_simcontrol_pagescript['delayable']) ? self::$_simcontrol_pagescript['delayable'] : null;
            $page_scripts['social'] = ! empty(self::$_simcontrol_pagescript['social']) ? self::$_simcontrol_pagescript['social'] : null;
            $page_scripts['advertisements'] = ! empty(self::$_simcontrol_pagescript['advertisements']) ? self::$_simcontrol_pagescript['advertisements'] : null;
            $page_scripts['async'] = ! empty(self::$_simcontrol_pagescript['async']) ? self::$_simcontrol_pagescript['async'] : null;
            $page_scripts['dontmove'] = ! empty(self::$_simcontrol_pagescript['dontmove']) ? self::$_simcontrol_pagescript['dontmove'] : null;
        }
        Return $page_scripts;
    
    }

    protected function closeTestGroup($advanced = 'normal')
    {

        if ($advanced == 'advanced')
        {
            MulticacheFrontendHelper::lockSimControl(false); // release lock protection
        }
    
    }

    protected function closeAllrelatedTests($test_group)
    {

        if (empty($test_group))
        {
            Return false;
        }
        // select all tests with the group id that are not complete and mark them test_abandoned
        // close the parent to abandoned
        $db = JFactory::getDBO();
        $query = $db->getQuery(true);
        $fields = array(
            $db->quoteName('status') . ' =   ' . $db->quote('test_abandoned')
        );
        $conditions = array(
            $db->quoteName('group_id') . '  =   ' . $db->quote($test_group->id),
            $db->quoteName('status') . '  NOT LIKE  ' . $db->quote('complete')
        );
        $query->update($db->quoteName('#__multicache_advanced_test_results'))
            ->set($fields)
            ->where($conditions);
        $db->setQuery($query);
        $result = $db->execute();
        
        $parentobj = new stdClass();
        $parentobj->id = $test_group->id;
        $parentobj->status = "abandoned";
        $result = $db->updateObject('#__multicache_advanced_testgroups', $parentobj, 'id');
    
    }

    /*
     * heurestic Test process flow
     * NULL
     * initiated
     * cache cleaned
     * -> prepare_cache_strategy ->cachestrategy_ready
     * -> prepare_test_page -> page_prepared
     * page_pinged
     * test_started
     * test_recorded
     *
     */
    public function getSimulatedIterationTest()
    {

        $db = JFactory::getDbo();
        $app = JFactory::getApplication();
        $conf = JFactory::getConfig();
        $lnparams = $this->getlnparams();
        $testing_on = $this->_lnparams->gtmetrix_testing;
        // GtMetrix Testing Control switch
        if (empty($testing_on))
        {
            if (defined("MULTICACHESIMULATIONDEBUG"))
            {
                echo JText::_("COM_MULTICACHE_SIMCONTROL_DEBUG_GTMETRIX_TESTING_OFF");
            }
            Return;
        }
        $test_url = $this->_lnparams->gtmetrix_test_url;
        $simulation = (bool) $this->_lnparams->gtmetrix_allow_simulation ? 'simulation' : 'off';
        
        if ($simulation == 'off')
        {
            if (defined("MULTICACHESIMULATIONDEBUG"))
            {
                echo JText::_("COM_MULTICACHE_SIMCONTROL_DEBUG_GTMETRIX_SIMULATION_MODE_OFF");
                echo "<br>";
            }
            Return false;
        }
        if (defined("MULTICACHESIMULATIONDEBUG"))
        {
            echo JText::_("COM_MULTICACHE_SIMCONTROL_DEBUG_GTMETRIX_SIMULATION_MODE");
            echo "<br>";
        }
        // $test_group = $this->gettestgroup();
        $pagecache_enabled = JPluginHelper::isEnabled('system', 'cache');
        $multicache_enabled = JPluginHelper::isEnabled('system', 'multicache');
        $caching = $conf->get('caching');
        if (empty($pagecache_enabled) || empty($multicache_enabled) || empty($caching))
        {
            if (defined("MULTICACHESIMULATIONDEBUG"))
            {
                if (empty($caching))
                {
                    echo JText::_("COM_MULTICACHE_SIMCONTROL_DEBUG_CACHING_GLOBAL_NOTENABLED");
                    echo "<br>";
                }
                
                if (empty($pagecache_enabled))
                {
                    echo JText::_("COM_MULTICACHE_SIMCONTROL_DEBUG_SYSTEM_PAGECACHE_NOTENABLED");
                    echo "<br>";
                }
                if (empty($multicache_enabled))
                {
                    echo JText::_("COM_MULTICACHE_SIMCONTROL_DEBUG_SYSTEM_MULTICACHE_NOTENABLED");
                    echo "<br>";
                }
            }
            
            $app->close();
        }
        
        if (! $this->_test_group)
        {
            
            $this->initiatetestgroup($this->_lnparams->simulation_advanced);
            $app->close();
        }
        if ($this->_test_group->status == 'close_all_related_tests')
        {
            if (defined("MULTICACHESIMULATIONDEBUG"))
            {
                echo JText::_("COM_MULTICACHE_SIMCONTROL_DEBUG_SIMCONTROL_CLOSING_RELATED_TESTS");
            }
            $this->closeAllrelatedTests($this->_test_group);
        }
        
        if ($this->_test_group->status == 'initiated')
        {
            $last_test = $this->getlasttest('simulation', $this->_test_group->advanced);
            
            if (empty($last_test))
            {
                $this->loadNextTest(null, null, null, null, $this->_test_group->advanced);
                if (defined("MULTICACHESIMULATIONDEBUG"))
                {
                    echo JText::_("COM_MULTICACHE_SIMCONTROL_DEBUG_GTMETRIX_TEST_GROUP_INITIATED");
                    echo "<br>";
                }
                $app->close();
            }
        }
        
        elseif ($this->_test_group->status == 'factors_ready_to_devolve')
        {
            if (defined("MULTICACHESIMULATIONDEBUG"))
            {
                echo JText::_("COM_MULTICACHE_SIMCONTROL_DEBUG_FACTORS_READY_TO_DEVOLVE");
                echo "<br>";
            }
            // ascertain factors basis deployment method
            // devolve the factors
            // update status to factors_devolved
            
            switch ($this->_lnparams->deployment_method)
            {
                
                case 3:
                    if (defined("MULTICACHESIMULATIONDEBUG"))
                    {
                        echo JText::_("COM_MULTICACHE_SIMCONTROL_DEBUG_DEPLOYING_ALGORITHM");
                        echo "<br>";
                    }
                    $this->deploy_algorithm();
                    break;
                case 2:
                    if (defined("MULTICACHESIMULATIONDEBUG"))
                    {
                        echo JText::_("COM_MULTICACHE_SIMCONTROL_DEBUG_DEPLOYING_BLT");
                        echo "<br>";
                    }
                    $this->deploy_bestloadtime();
                    break;
                
                case 1:
                    if (defined("MULTICACHESIMULATIONDEBUG"))
                    {
                        echo JText::_("COM_MULTICACHE_SIMCONTROL_DEBUG_DEPLOYING_DEFAULT_SETTINGS");
                        echo "<br>";
                    }
                    $this->deploy_defaultsettings();
                    break;
                
                case 0:
                    if (defined("MULTICACHESIMULATIONDEBUG"))
                    {
                        echo JText::_("COM_MULTICACHE_SIMCONTROL_DEBUG_EXCEPTION_CASE0");
                        echo "<br>";
                    }
                    
                    $updateObj = new stdClass();
                    $updateObj->id = $this->_test_group->id;
                    $updateObj->status = 'factors_devolved_none';
                    $result = $db->updateObject('#__multicache_advanced_testgroups', $updateObj, 'id');
            }
            $app->close();
        }
        elseif ($this->_test_group->status == 'factors_devolved')
        {
            // test factors
            // update status to complete
            // turn simulation off
            if (defined("MULTICACHESIMULATIONDEBUG"))
            {
                echo JText::_("COM_MULTICACHE_SIMCONTROL_DEBUG_FACTORS_DEVOLVED");
                echo "<br>";
            }
            
            $config = JFactory::getConfig();
            // $plugins_params = JPluginHelper::getPlugin('system', 'cache');
            // $params = new JRegistry($plugins_params->params);
            $devolved_precache_factor = $config->get('precache_factor');
            $devolved_ccomp_factor = $config->get('gzip_factor');
            $last_test = $this->getlasttest('simulation', $this->_test_group->advanced);
            $updateobj = new stdClass();
            $updateobj->id = $last_test->id;
            $updateobj->status = 'complete';
            $result = $db->updateObject('#__multicache_advanced_test_results', $updateobj, 'id');
            
            if ($devolved_precache_factor == $this->_test_group->loaded_precache_factor && $devolved_ccomp_factor == $this->_test_group->loaded_cache_compression_factor)
            {
                
                $updateObj = new stdClass();
                $updateObj->id = 1;
                $updateObj->gtmetrix_allow_simulation = 0;
                $result = $db->updateObject('#__multicache_config', $updateObj, 'id');
                $updateObj = new stdClass();
                $updateObj->id = $this->_test_group->id;
                $updateObj->status = 'complete';
                $result = $db->updateObject('#__multicache_advanced_testgroups', $updateObj, 'id');
                // unlock the sim protection for advanced tests
                $this->closeTestGroup($this->_test_group->advanced);
                $emessage = "COM_MULTICACHESIMCONTROL_SIMULATION_TURNEDOFF_BY_FACTORSDEVOLVED";
                JLog::add(JText::_($emessage), JLog::INFO);
                if (defined("MULTICACHESIMULATIONDEBUG"))
                {
                    echo JText::_("COM_MULTICACHE_SIMCONTROL_DEBUG_PARENT_GROUP_TESTS_COMPLETE");
                }
                $app->close();
            }
            else
            {
                $emessage = "COM_MULTICACHESIMCONTROL_SIMULATION_FACTORMISMATCHINDEVOLVE_TRYINGAGAIN";
                JLog::add(JText::_($emessage), JLog::INFO);
                if (defined("MULTICACHESIMULATIONDEBUG"))
                {
                    echo JText::_($emessage);
                }
                $this->devolveRepeat();
                /*
                 * $updateObj->id = $this->_test_group->id;
                 * $updateObj->status = 'factors_ready_to_devolve';
                 * $result = $db->updateObject('#__multicache_advanced_testgroups', $updateObj, 'id');
                 */
                
                $app->close();
            }
        }
        elseif ($this->_test_group->status == 'factors_devolved_none')
        {
            $last_test = $this->getlasttest();
            $updateobj = new stdClass();
            $updateobj->id = $last_test->id;
            $updateobj->status = 'complete';
            $result = $db->updateObject('#__multicache_advanced_test_results', $updateobj, 'id');
            // update status to complete
            // turn simulation off
            $updateObj = new stdClass();
            $updateObj->id = 1;
            $updateObj->gtmetrix_allow_simulation = 0;
            $result = $db->updateObject('#__multicache_config', $updateObj, 'id');
            $updateObj = new stdClass();
            $updateObj->id = $this->_test_group->id;
            $updateObj->status = 'close_all_related_tests';
            $result = $db->updateObject('#__multicache_advanced_testgroups', $updateObj, 'id');
            // unlock sim protection for advanced tests
            $this->closeTestGroup($this->_test_group->advanced);
            $emessage = "COM_MULTICACHE_SIMULATION_TURNEDOFF_BY_FACTORSDEVOLVEDNONE";
            JLog::add(JText::_($emessage), JLog::INFO);
            if (defined("MULTICACHESIMULATIONDEBUG"))
            {
                echo JText::_("COM_MULTICACHE_SIMCONTROL_DEBUG_PARENT_GROUP_TESTS_INCOMPLETE");
                echo "<br>";
            }
            $app->close();
        }
        
        // the else if conditions to devolve the factors to usage
        
        // self healing functions
        if ($last_test->status == 'test_started' && strtotime(date("Y-m-d H:i:s")) - strtotime($last_test->test_date) > 3600)
        {
            $this->abandonLastTest($last_test);
            
            $precache_factor = $last_test->precache_factor;
            $cache_compression_factor = $last_test->cache_compression_factor;
            $load_key = $last_test->loadinstruc_key;
            
            $this->loadNextTest($last_test, $precache_factor, $cache_compression_factor, $load_key, $this->_test_group->advanced);
            if (defined("MULTICACHESIMULATIONDEBUG"))
            {
                echo JText::_("COM_MULTICACHE_SIMCONTROL_DEBUG_SELF_HEALING");
                echo "<br>";
            }
        }
        // process of indiviual tests
        
        // lets weed out the process for inbetween changes from advanced to normal
        if ($this->_test_group->advanced == 'advanced')
        {
            // mandatory self::$_linstruction should be set
            if (! isset(self::$_linstruction))
            {
                if (class_exists('MulticachePageScripts') && property_exists('MulticachePageScripts', 'working_script_array'))
                {
                    $this->getSimcontrol();
                    $emessage = "COM_MULTICACHESIMCONTROL_CALLED_GETSIMCONTROL";
                    JLog::add(JText::_($emessage), JLog::WARNING);
                    $app->close();
                }
                // we will need to put the test group on hold and the last test on hold
                if (defined("MULTICACHESIMULATIONDEBUG"))
                {
                    echo JText::_("COM_MULTICACHE_SIMCONTROL_DEBUG_INBETWEEN_CHANGES");
                    echo "<br>";
                }
                $this->onholdLastTest($last_test);
                $app->close();
            }
        }
        // end weed out process
        
        if ($last_test->status == 'test_on_hold')
        {
            if (! in_array($last_test->loadinstruc_state, self::$_linstruction))
            {
                if (defined("MULTICACHESIMULATIONDEBUG"))
                {
                    echo JText::_("COM_MULTICACHE_SIMCONTROL_DEBUG_MULTICACHE_ABANDONING_LAST_TEST");
                    echo "<br>";
                }
                $this->abandonLastTest($last_test);
                $app->close();
            }
            if (defined("MULTICACHESIMULATIONDEBUG"))
            {
                echo JText::_("COM_MULTICACHE_SIMCONTROL_DEBUG_MULTICACHE_REINITIATE_LAST_TEST");
                echo "<br>";
            }
            $this->reinitiateLastTest($last_test);
            $app->close();
        }
        
        if ($last_test->status == 'daily_budget_complete')
        {
            $mtime = microtime(true);
            if (! ($mtime % 3))
            {
                if (defined("MULTICACHESIMULATIONDEBUG"))
                {
                    echo JText::_("COM_MULTICACHE_SIMCONTROL_DEBUG_DAILY_BUDGET_COMPLETE_CHECKING_WITH_GTM");
                    echo "<br>";
                }
                $gtObj = new Services_WTF_Test($this->_lnparams->gtmetrix_email, $this->_lnparams->gtmetrix_token);
                $status = $gtObj->status();
                
                if ($status["api_credits"] >= 1)
                {
                    if (defined("MULTICACHESIMULATIONDEBUG"))
                    {
                        echo JText::_("COM_MULTICACHE_SIMCONTROL_DEBUG_TOPUP_COMPLETE_REINITIATING_LAST_TEST");
                        echo "<br>";
                    }
                    $this->reinitiateLastTest($last_test);
                }
                else
                {
                    if (defined("MULTICACHESIMULATIONDEBUG"))
                    {
                        
                        echo JText::_("COM_MULTICACHE_SIMCONTROL_DEBUG_DAILY_BUDGET_COMPLETE_NO_CREDTS_AVAILABLE");
                        echo "<br>";
                    }
                }
                $app->close();
            }
        }
        
        if ($last_test->status == 'initiated')
        {
            if (defined("MULTICACHESIMULATIONDEBUG"))
            {
                echo JText::_("COM_MULTICACHE_SIMCONTROL_DEBUG_TEST_INITIATED");
                echo "<br>";
            }
            $precache_factor = $last_test->precache_factor;
            $cache_compression_factor = $last_test->cache_compression_factor;
            // as js_simulation is an alias of js_switch we will use an advanced flag lest collision dwhen js_switch is turn opposite to js_simulation
            $test_page = JURI::getInstance($last_test->test_page);
            if ($this->_test_group->advanced == 'advanced')
            {
                if ($this->_lnparams->jssimulation_parse == 2)
                {
                    
                    $test_page->setVar('multicachesimulation', $last_test->loadinstruc_state);
                    MulticacheFrontendHelper::setJsSimulation(1, $this->_test_group->advanced, null);
                }
                else
                {
                    MulticacheFrontendHelper::setJsSimulation(1, $this->_test_group->advanced, $last_test->loadinstruc_state);
                }
            }
            else
            {
                MulticacheFrontendHelper::setJsSimulation(1);
            }
            
            MulticacheFrontendHelper::establish_factors($precache_factor, $cache_compression_factor);
            MulticacheFrontendHelper::clean_cache('com_plugins', $test_page->toString());
            
            $updateobj = new stdClass();
            $updateobj->id = $last_test->id;
            $updateobj->mtime = microtime(true);
            $updateobj->status = 'cache_cleaned';
            $db->updateObject('#__multicache_advanced_test_results', $updateobj, 'id');
        }
        
        if ($last_test->status == 'cache_cleaned')
        {
            if (defined("MULTICACHESIMULATIONDEBUG"))
            {
                echo JText::_("COM_MULTICACHE_SIMCONTROL_DEBUG_TEST_CACHE_CLEAN_STAGE");
                echo "<br>";
            }
            $stime = $last_test->mtime;
            $ntime = microtime(true);
            $diff = $ntime - $stime;
            if ($diff <= '30')
            {
                if (defined("MULTICACHESIMULATIONDEBUG"))
                {
                    echo sprintf(JText::_('COM_MULTICACHE_SIMCONTROL_MANDATORY_DELAY_MESSAGE'), 30);
                }
                $app->close();
            }
            //
            
            if (! json_decode(JPluginHelper::getPlugin('system', 'multicache')->params)->js_simulation || ($this->_test_group->advanced == 'advanced' && ! json_decode(JPluginHelper::getPlugin('system', 'multicache')->params)->js_advanced))
            {
                $emessage = "COM_MULTICACHE_SIMCONTROL_CACHE_CLEANED_JSSIMULATION_SWITCH_NOTSET_REVERTING_ONESTAGE";
                if (defined("MULTICACHESIMULATIONDEBUG"))
                {
                    echo JText::_($emessage);
                    echo "<br>";
                }
                JLog::add(JText::_($emessage), JLog::ERROR);
                $updateobj = new stdClass();
                $updateobj->id = $last_test->id;
                $updateobj->mtime = microtime(true);
                $updateobj->status = 'initiated';
                $db->updateObject('#__multicache_advanced_test_results', $updateobj, 'id');
                $app->close();
            }
            if ($this->_test_group->advanced == 'advanced')
            {
                if (defined("MULTICACHESIMULATIONDEBUG"))
                {
                    echo JText::_("COM_MULTICACHE_SIMCONTROL_DEBUG_ADVANCED_MODE_PREPARE_CACHE_STRATEGY");
                    echo "<br>";
                }
                $prepared = $this->prepareCacheStrategy($last_test);
            }
            else
            {
                if (defined("MULTICACHESIMULATIONDEBUG"))
                {
                    echo JText::_("COM_MULTICACHE_SIMCONTROL_DEBUG_NORMAL_MODE_PREPARE_CACHE_STRATEGY");
                    echo "<br>";
                }
                $prepared = true; // skip Jscache strategy
            }
            
            if ($prepared)
            {
                
                $updateobj = new stdClass();
                $updateobj->id = $last_test->id;
                $updateobj->mtime = microtime(true);
                $updateobj->status = 'cache_strategy_ready';
                $result = $db->updateObject('#__multicache_advanced_test_results', $updateobj, 'id');
            }
            else
            {
                if (defined("MULTICACHESIMULATIONDEBUG"))
                {
                    echo JText::_("COM_MULTICACHE_SIMCONTROL_DEBUG_NOTABLETO_PREPARE_CACHE_STRATEGY");
                    echo "<br>";
                }
            }
        }
        
        if ($last_test->status == 'cache_strategy_ready')
        {
            
            $stime = $last_test->mtime;
            $ntime = microtime(true);
            $diff = $ntime - $stime;
            if ($diff <= '30')
            {
                if (defined("MULTICACHESIMULATIONDEBUG"))
                {
                    echo sprintf(JText::_('COM_MULTICACHE_SIMCONTROL_MANDATORY_DELAY_MESSAGE'), 30);
                }
                $app->close();
            }
            // ping page only if the precache,ccomp and loadinstruc are set
            $ready = $this->confirmParamsSet($last_test, $this->_test_group);
            if (! $ready)
            {
                if (defined("MULTICACHESIMULATIONDEBUG"))
                {
                    echo JText::_("COM_MULTICACHE_SIMCONTROL_DEBUG_REINITIATING_TEST");
                    echo "<br>";
                }
                $this->reinitiateLastTest($last_test);
                $app->close();
            }
            
            $test_page = JURI::getInstance($last_test->test_page);
            
            if ($this->_test_group->advanced == 'advanced' && $this->_lnparams->jssimulation_parse == 2)
            {
                $test_page->setVar('multicachesimulation', $last_test->loadinstruc_state);
            }
            if (defined("MULTICACHESIMULATIONDEBUG"))
            {
                echo JText::_("COM_MULTICACHE_SIMCONTROL_DEBUG_PINGING_SITE_TOCREATE_PAGE");
                echo "<br>";
            }
            $code = MulticacheFrontendHelper::get_web_page($test_page->toString());
            
            if ($code['http_code'] == 200)
            {
                $updateobj = new stdClass();
                $updateobj->id = $last_test->id;
                $updateobj->mtime = microtime(true);
                $updateobj->status = 'page_pinged';
                $result = $db->updateObject('#__multicache_advanced_test_results', $updateobj, 'id');
            }
            else
            {
                if (defined("MULTICACHESIMULATIONDEBUG"))
                {
                    echo JText::_("COM_MULTICACHE_SIMCONTROL_PAGE_PING_FALIED") . '   ' . $code['http_code'];
                    echo "<br>";
                }
            }
        }
        
        if ($last_test->status == 'page_pinged')
        {
            
            $stime = $last_test->mtime;
            $ntime = microtime(true);
            $diff = $ntime - $stime;
            if ($diff <= '30')
            {
                if (defined("MULTICACHESIMULATIONDEBUG"))
                {
                    echo sprintf(JText::_('COM_MULTICACHE_SIMCONTROL_MANDATORY_DELAY_MESSAGE'), 30);
                }
                $app->close();
            }
            $ready = $this->confirmParamsSet($last_test, $this->_test_group);
            if (! $ready)
            {
                if (defined("MULTICACHESIMULATIONDEBUG"))
                {
                    echo JText::_("COM_MULTICACHE_SIMCONTROL_DEBUG_PRETEST_PARAMS_FAILED");
                    echo "<br>";
                }
                $this->reinitiateLastTest($last_test);
                $app->close();
            }
            
            $test_page = JURI::getInstance($last_test->test_page);
            
            if ($this->_test_group->advanced == 'advanced' && $this->_lnparams->jssimulation_parse == 2)
            {
                $test_page->setVar('multicachesimulation', $last_test->loadinstruc_state);
            }
            if (defined("MULTICACHESIMULATIONDEBUG"))
            {
                echo JText::_('COM_MULTICACHE_SIMCONTROL_MEASURING_PAGE_ELEMENTS');
            }
            $gtObj = new Services_WTF_Test($this->_lnparams->gtmetrix_email, $this->_lnparams->gtmetrix_token);
            $testid = $gtObj->test(array(
                'url' => $test_page->toString(),
                'x-metrix-adblock' => $this->_lnparams->gtmetrix_adblock
            ));
            
            if ($testid)
            {
                
                $updateobj = new stdClass();
                $updateobj->id = $last_test->id;
                $updateobj->test_id = $testid;
                $updateobj->mtime = microtime(true);
                $updateobj->status = 'test_started';
                $result = $db->updateObject('#__multicache_advanced_test_results', $updateobj, 'id');
            }
            else
            {
                if (! is_string($gtObj->error()))
                {
                    if (defined("MULTICACHESIMULATIONDEBUG"))
                    {
                        echo JText::_("COM_MULTICACHE_SIMCONTROL_DEBUG_GTMETRIX_ERROR");
                        echo "<br>";
                        die($gtObj->error() . "\n");
                    }
                    $app->close();
                }
                if (strstr($gtObj->error(), 'Maximum number of API calls reached'))
                {
                    if (defined("MULTICACHESIMULATIONDEBUG"))
                    {
                        echo JText::_("COM_MULTICACHE_SIMCONTROL_DEBUG_GTMETRIX_MAXEDOUT");
                        echo "<br>";
                    }
                    $precache_factor = isset($this->_lnparams->precache_factor_default) ? $this->_lnparams->precache_factor_default : $last_test->precache_factor;
                    $cache_compression_factor = isset($this->_lnparams->gzip_factor_default) ? $this->_lnparams->gzip_factor_default : $last_test->cache_compression_factor;
                    MulticacheFrontendHelper::setJsSimulation(0, $this->_test_group->advanced, 0);
                    MulticacheFrontendHelper::establish_factors($precache_factor, $cache_compression_factor);
                    MulticacheFrontendHelper::clean_cache('com_plugins', null);
                    $this->setDailyBudgetComplete($last_test);
                }
                die($gtObj->error() . "\n");
            }
            $gtObj->get_results();
            $testid = $gtObj->get_test_id();
            
            $results = $gtObj->results();
            if (! empty($results))
            {
                $config = JFactory::getConfig();
                $cache_handler = $config->get('cache_handler');
                $updateobj = new stdClass();
                $updateobj->id = $last_test->id;
                $updateobj->test_id = $testid;
                $updateobj->page_load_time = $results['page_load_time'];
                $updateobj->html_bytes = $results['html_bytes'];
                $updateobj->page_elements = $results['page_elements'];
                $updateobj->report_url = $results['report_url'];
                $updateobj->html_load_time = $results['html_load_time'];
                $updateobj->page_bytes = $results['page_bytes'];
                $updateobj->pagespeed_score = $results['pagespeed_score'];
                $updateobj->yslow_score = $results['yslow_score'];
                $updateobj->mtime = microtime(true);
                $updateobj->cache_handler = $cache_handler;
                $updateobj->hammer_mode = $this->_lnparams->multicachedistribution;
                $updateobj->status = 'test_recorded';
                $result = $db->updateObject('#__multicache_advanced_test_results', $updateobj, 'id');
            }
        }
        
        if ($last_test->status == 'test_recorded')
        {
            if (defined("MULTICACHESIMULATIONDEBUG"))
            {
                echo JText::_("COM_MULTICACHE_SIMCONTROL_DEBUG_LAST_TEST_RECORDED");
                echo "<br>";
            }
            // resetting factors
            
            $precache_factor = isset($this->_lnparams->precache_factor_default) ? $this->_lnparams->precache_factor_default : $last_test->precache_factor;
            $cache_compression_factor = isset($this->_lnparams->gzip_factor_default) ? $this->_lnparams->gzip_factor_default : $last_test->cache_compression_factor;
            MulticacheFrontendHelper::setJsSimulation(0, $this->_test_group->advanced, 0);
            MulticacheFrontendHelper::establish_factors($precache_factor, $cache_compression_factor);
            MulticacheFrontendHelper::clean_cache('com_plugins', null);
            // get the number of tests conducted today
            
            $num_rows = $this->checkTestsTime();
            if ($num_rows >= $last_test->max_tests)
            {
                if (defined("MULTICACHESIMULATIONDEBUG"))
                {
                    echo JText::_("COM_MULTICACHE_SIMCONTROL_DEBUG_DAILY_BUDGET_COMPLETE");
                    echo "<br>";
                }
                
                $app->close();
            }
            $this->prepare_next_test($last_test);
        }
    
    }

    public function getNonSimulatedTest()
    {

        $db = JFactory::getDbo();
        $app = JFactory::getApplication();
        $lnparams = $this->getlnparams();
        $testing_on = $this->_lnparams->gtmetrix_testing;
        // GtMetrix Testing Control switch
        if (empty($testing_on))
        {
            Return;
        }
        $test_url = $this->_lnparams->gtmetrix_test_url;
        $simulation = (bool) $this->_lnparams->gtmetrix_allow_simulation ? 'simulation' : 'off';
        
        if ($simulation == 'simulation')
        {
            Return false;
        }
        
        $last_test = $this->getlasttest('off');
        if ($last_test->status == 'test_started' && strtotime(date("Y-m-d H:i:s")) - strtotime($last_test->test_date) > 86400)
        {
            if (defined("MULTICACHESIMULATIONDEBUG"))
            {
                echo JText::_("COM_MULTICACHE_SIMCONTROL_NONSIMMODE_ABANDONINGTEST_EXPIRY");
                echo "<br>";
            }
            $this->abandonLastTest($last_test);
        }
        if (empty($last_test) || $last_test->status == 'complete' || $last_test->status == 'test_abandoned')
        {
            $this->loadNextNonSimTest();
            $app->close();
        }
        
        if ($last_test->status == 'initiated')
        {
            
            $stime = $last_test->mtime;
            $ntime = microtime(true);
            $diff = $ntime - $stime;
            if ($diff <= '300')
            {
                Return false;
            }
            $gtObj = new Services_WTF_Test($this->_lnparams->gtmetrix_email, $this->_lnparams->gtmetrix_token);
            $testid = $gtObj->test(array(
                'url' => $last_test->test_page,
                'x-metrix-adblock' => $this->_lnparams->gtmetrix_adblock
            ));
            
            if ($testid)
            {
                if (defined("MULTICACHESIMULATIONDEBUG"))
                {
                    echo JText::_("COM_MULTICACHE_SIMCONTROL_NONSIMMODE_TEST_SUCCESSFUL");
                    echo "<br>";
                }
                $updateobj = new stdClass();
                $updateobj->id = $last_test->id;
                $updateobj->test_id = $testid;
                $updateobj->mtime = microtime(true);
                $updateobj->status = 'test_started';
                $result = $db->updateObject('#__multicache_advanced_test_results', $updateobj, 'id');
            }
            else
            {
                if (defined("MULTICACHESIMULATIONDEBUG"))
                {
                    echo JText::_("COM_MULTICACHE_SIMCONTROL_NONSIMMODE_TEST_UNSUCCESSFUL");
                    echo "<br>";
                }
                die($gtObj->error() . "\n");
            }
            $gtObj->get_results();
            $testid = $gtObj->get_test_id();
            
            $results = $gtObj->results();
            if (! empty($results))
            {
                $config = JFactory::getConfig();
                $cache_handler = $config->get('cache_handler');
                
                $updateobj = new stdClass();
                $updateobj->id = $last_test->id;
                $updateobj->test_id = $testid;
                $updateobj->page_load_time = $results['page_load_time'];
                $updateobj->html_bytes = $results['html_bytes'];
                $updateobj->page_elements = $results['page_elements'];
                $updateobj->report_url = $results['report_url'];
                $updateobj->html_load_time = $results['html_load_time'];
                $updateobj->page_bytes = $results['page_bytes'];
                $updateobj->pagespeed_score = $results['pagespeed_score'];
                $updateobj->yslow_score = $results['yslow_score'];
                $updateobj->mtime = microtime(true);
                $updateobj->cache_handler = $cache_handler;
                $updateobj->status = 'test_recorded';
                $result = $db->updateObject('#__multicache_advanced_test_results', $updateobj, 'id');
            }
        }
        
        if ($last_test->status == 'test_recorded')
        {
            if (defined("MULTICACHESIMULATIONDEBUG"))
            {
                echo JText::_("COM_MULTICACHE_SIMCONTROL_NONSIMMODE_TEST_RECORDED_SUCCESSFUL");
                echo "<br>";
            }
            // get the number of tests conducted today
            
            $checktimestart = microtime(true) - (86400);
            
            $check_query = $db->getQuery(true);
            
            $check_query->select('*');
            $check_query->from($db->quoteName('#__multicache_advanced_test_results'));
            $check_query->where($db->quoteName('mtime') . "  >= " . $db->quote($checktimestart));
            $check_query->order($db->quoteName('id') . ' DESC');
            $db->setQuery($check_query);
            $all_day_load = $db->loadObjectlist();
            
            $db->execute();
            $num_rows = $db->getNumRows();
            if ($num_rows >= $last_test->max_tests)
            {
                if (defined("MULTICACHESIMULATIONDEBUG"))
                {
                    echo JText::_("COM_MULTICACHE_SIMCONTROL_NONSIMMODE_DAILY_BUDGET_COMPLETE");
                }
                
                $app->close();
            }
            if (defined("MULTICACHESIMULATIONDEBUG"))
            {
                echo JText::_("COM_MULTICACHE_SIMCONTROL_NONSIMMODE_PREPARING_NEXT_TEST");
                echo "<br>";
            }
            $this->prepare_next_nonsim_test($last_test);
        }
    
    }

    protected function confirmParamsSet($last_test, $test_group)
    {

        $config = JFactory::getConfig();
        $config_precache = $config->get('precache_factor');
        $config_ccomp = $config->get('gzip_factor');
        // check the precache
        if ($config_precache !== $last_test->precache_factor)
        {
            
            $emessage = "COM_MULTICACHE_SIMCONTROL_CONFIRMPARAMS_MISPATCH_IN_PRECACHE_TEST_RESET";
            JLog::add(JText::_($emessage) . ' config-' . $config_precache . '	required-' . $last_test->precache_factor, JLog::ERROR);
            Return false;
        }
        if ($config_ccomp !== $last_test->cache_compression_factor)
        {
            
            $emessage = "COM_MULTICACHE_SIMCONTROL_CONFIRMPARAMS_MISPATCH_IN_CCOMP_TEST_RESET";
            JLog::add(JText::_($emessage) . ' config-' . $config_ccomp . '	required-' . $last_test->cache_compression_factor, JLog::ERROR);
            Return false;
        }
        
        // check that advanced and switch are activated in multicache plugin
        $plugin = JPluginHelper::getPlugin('system', 'multicache');
        $params = new JRegistry($plugin->params);
        
        if ($params->get('js_simulation') !== 1)
        {
            $emessage = "COM_MULTICACHE_SIMCONTROL_CONFIRMPARAMS_MULTICACHE_PLUGIN_JSSIMULATION_OFF";
            JLog::add(JText::_($emessage) . ' jssimulation-' . $params->get('js_simulation') . '	required-1', JLog::ERROR);
            Return false;
        }
        
        if ($last_test->advanced === 'advanced' && $params->get('js_advanced') !== 1)
        {
            $emessage = "COM_MULTICACHE_SIMCONTROL_CONFIRMPARAMS_MULTICACHE_PLUGIN_JSADVANCED_NOTSET";
            JLog::add(JText::_($emessage) . ' param jsadvanced-' . $params->get('js_advanced') . '	required lasttest-' . $last_test->advanced, JLog::ERROR);
            Return false;
        }
        
        if ($last_test->advanced === 'normal' && $params->get('js_advanced') === 1)
        {
            $emessage = "COM_MULTICACHE_SIMCONTROL_CONFIRMPARAMS_MULTICACHE_PLUGIN_JSADVANCED_NOTSETTONORMAL";
            JLog::add(JText::_($emessage) . ' param jsadvanced-' . $params->get('js_advanced') . '	required lasttest-' . $last_test->advanced, JLog::ERROR);
            Return false;
        }
        
        if ($last_test->advanced === 'advanced' && $last_test->loadinstruc_state !== $params->get('js_loadinstruction'))
        {
            $emessage = "COM_MULTICACHE_SIMCONTROL_CONFIRMPARAMS_MULTICACHE_ADVANCED_LOADINSTRUCTION_MISMATCH";
            JLog::add(JText::_($emessage) . ' params jsloadinstruction-' . $params->get('js_loadinstruction') . '	required loadinstruction-' . $last_test->loadinstruc_state, JLog::ERROR);
            Return false;
        }
        Return true;
    
    }

    protected function setprincipleJqueryscopeoperator()
    {

        $app = JFactory::getApplication();
        if (isset($this->_lnparams->principle_jquery_scope) && $this->_lnparams->principle_jquery_scope == 0)
        {
            self::$_principle_jquery_scope = "jQuery";
        }
        elseif (isset($this->_lnparams->principle_jquery_scope) && $this->_lnparams->principle_jquery_scope == 1)
        {
            self::$_principle_jquery_scope = "$";
        }
        elseif (isset($this->_lnparams->principle_jquery_scope) && $this->_lnparams->principle_jquery_scope == 2)
        {
            if (! empty($this->_lnparams->principle_jquery_scope_other))
            {
                self::$_principle_jquery_scope = trim($this->_lnparams->principle_jquery_scope_other);
            }
            else
            {
                
                self::$_principle_jquery_scope = "jQuery";
                $emessage = "COM_MULTICACHE_SIMCONTROL_JQUERY_SCOPE_NOT_DEFINED_SETTING_TO_JQUERY";
                JLog::add(JText::_($emessage), JLog::ERROR);
            }
        }
        else
        {
            self::$_principle_jquery_scope = "jQuery";
            $emessage = "COM_MULTICACHE_SIMCONTROL_JQUERY_SCOPE_NOT_DEFINED_SETTING_TO_JQUERY";
            JLog::add(JText::_($emessage), JLog::ERROR);
        }
    
    }

    protected function prepare_next_nonsim_test($ltest)
    {

        if (empty($ltest))
        {
            Return false;
        }
        $db = JFactory::getDBO();
        $db->getQuery(true);
        $params = $this->_lnparams;
        
        // close last test
        $updateobj = new stdClass();
        $updateobj->id = $ltest->id;
        $updateobj->status = 'complete';
        $result = $db->updateObject('#__multicache_advanced_test_results', $updateobj, 'id');
        
        $this->loadNextNonSimTest($ltest);
    
    }

    protected function loadNextNonSimTest($last_test = NULL)
    {

        $param = $this->_lnparams;
        $db = JFactory::getDBO();
        $lquery = $db->getQuery(true);
        if (! $last_test)
        {
            $current_test = 1;
        }
        else
        {
            $current_test = (int) $last_test->current_test;
            if (++ $current_test > $last_test->max_tests)
            {
                $current_test = 1;
            }
        }
        $dateoftest = date("d-m-Y");
        $testdate_dbops = date("Y-m-d H:i:s");
        $mtime = microtime(true);
        $max_tests = $param->gtmetrix_api_budget;
        $test_page = $param->gtmetrix_test_url;
        $config = JFactory::getConfig();
        // $plugins_params = JPluginHelper::getPlugin('system', 'cache');
        // $params_plugin = new JRegistry($plugins_params->params);
        
        $precache_factor = $config->get('precache_factor');
        
        $ccomp_factor = $config->get('gzip_factor');
        
        $insertobj = new stdClass();
        $insertobj->date_of_test = $dateoftest;
        $insertobj->mtime = $mtime;
        $insertobj->max_tests = $max_tests;
        $insertobj->current_test = $current_test;
        $insertobj->precache_factor = $precache_factor;
        $insertobj->cache_compression_factor = $ccomp_factor;
        $insertobj->test_page = $test_page;
        $insertobj->status = 'initiated';
        $insertobj->simulation = 'off';
        $insertobj->test_date = $testdate_dbops;
        $result = $db->insertObject('#__multicache_advanced_test_results', $insertobj);
    
    }

    protected function loadProperty($property_name, $classname = "MulticachePageScripts")
    {

        if (empty($property_name))
        {
            Return false;
        }
        
        if (! class_exists($classname))
        {
            Return null;
        }
        
        if (! property_exists($classname, $property_name))
        {
            Return null;
        }
        
        Return $classname::$$property_name;
    
    }

    /*
     * The next three methods are powerful loadinstruction class creators.
     * They have a dont care array as well. This allows the accomadation
     * of both the theoretical aspects & pragmatism.
     */
    protected function getLoadinstruction($count_scripts, $min = 1, $max = 4, $dont_care = array(1, 3))
    {

        for ($i = 1; $i <= $count_scripts; $i ++)
        {
            $start_count .= (string) $min;
            if (in_array($max, $dont_care))
            {
                foreach ($dont_care as $key => $dc)
                {
                    if (! in_array(-- $max, $dont_care))
                    {
                        break;
                    }
                }
            }
            $end_count .= (string) $max;
        }
        /*
         * if ((int) $end_count <= (int) $start_count)
         * {
         * Return false;
         * }
         */
        // start transport from WP
        // Need to workaroun PHP_MAX_INT here
        $compare = MulticacheFrontendHelper::largeIntCompare($end_count, $start_count);
        if ($compare === false)
        {
            /*
             * MulticacheHelper::log_error(__('Simcontrol GetLoadInstructon Error with number types', 'multicache-plugin'), array(
             * 'end_count' => $end_count,
             * 'start_count' => $start_count
             * ));
             */
            $emessage = "COM_MULTICACHE_SIMCONTROL_GETLOADINSTRUCTION_ERROR_WITH_NUMBER_TYPES";
            JLog::add(JText::_($emessage) . '	 end_count-' . $end_count . '	start_count-' . $start_count, JLog::ERROR);
            Return false;
        }
        if ($compare === 0 || $compare === - 1)
        {
            /*
             * MulticacheHelper::log_error(__('Simcontrol GetLoadInstructon Error end less than start', 'multicache-plugin'), array(
             * 'end_count' => $end_count,
             * 'start_count' => $start_count
             * ));
             */
            $emessage = "COM_MULTICACHE_SIMCONTROL_ERROR_END_LESS_THAN_START";
            JLog::add(JText::_($emessage) . '	 end_count-' . $end_count . '	start_count-' . $start_count, JLog::ERROR);
            Return false;
        }
        // end transport from WP
        $counter = $start_count;
        for ($j = 0; $j <= 10000; $j ++)
        {
            $counter = $this->incrementLoadsectionCounter($counter, $min, $max, $dont_care);
            self::$_loadinstruction[] = isset($counter) ? $counter : null;
            if ($counter >= $end_count || ! isset($counter))
            {
                break;
            }
        }
        Return true;
    
    }

    protected function incrementLoadsectionCounter($c, $min = 1, $max = 4, $dont_care = array(1, 3))
    {

        $app = JFactory::getApplication();
        $number_string = $c;
        $places = strlen($c);
        $increment_flag = true;
        $carry_over = 0;
        // moderate min & max for dont_care states
        if (in_array($min, $dont_care))
        {
            foreach ($dont_care as $key => $dc)
            {
                if (! in_array(++ $min, $dont_care))
                {
                    break;
                }
            }
        }
        if (in_array($max, $dont_care))
        {
            foreach ($dont_care as $key => $dc)
            {
                if (! in_array(-- $max, $dont_care))
                {
                    break;
                }
            }
        }
        if ($max < $min)
        {
            
            $emessage = "COM_MULTICACHE_SIMCONTROLLER_MAX_LESS_MIN_ERROR_MESSAGE";
            JLog::add(JText::_($emessage) . '	 max-' . $max . '	min-' . $min, JLog::ERROR);
            Return null;
        }
        // min load level moderator
        for ($i = 1; $i <= $places; $i ++)
        {
            $initial_state .= (string) $min;
            $digit = (int) $number_string[$i - 1];
            if ($digit < $min)
            {
                $digit = $min;
                $position = $i - 1;
                $number_string = substr_replace($number_string, $digit, $position, 1);
            }
        }
        if ($number_string == $initial_state && $c != $initial_state)
        {
            Return $number_string;
        }
        
        // main increment counter
        for ($i = 1; $i <= $places; $i ++)
        {
            $digit = $number_string[$places - $i];
            if ($increment_flag || $carry_over)
            {
                $digit = (int) $digit;
                $digit ++;
                // moderate dont care states
                if (in_array($digit, $dont_care))
                {
                    foreach ($dont_care as $key => $dc)
                    {
                        if (! in_array(++ $digit, $dont_care))
                        {
                            break;
                        }
                    }
                }
                //
                if ($digit > $max)
                {
                    $digit = $min;
                    $carry_over = 1;
                }
                else
                {
                    $carry_over = 0;
                }
                // get the digit back to string
                $digit = (string) $digit;
                $position = - $i;
                // replace the number_string
                $number_string = substr_replace($number_string, $digit, $position, 1);
                $increment_flag = false;
            }
        }
        // precedence moderator
        
        for ($i = 1; $i <= $places - 1; $i ++)
        {
            // compare the MSB to MSB +1
            $msd = (int) ($number_string[$i - 1]); // msd more significant digit
            $nsd = (int) ($number_string[$i]); // nsd next significant digit
            if ($msd > $nsd)
            {
                $nsd = $msd;
                $position = $i;
                $number_string = substr_replace($number_string, $nsd, $position, 1);
            }
        }
        Return $number_string;
    
    }

    protected function getScriptCount()
    {

        if (class_exists('MulticachePageScripts') && property_exists('MulticachePageScripts', 'working_script_array'))
        {
            Return count(MulticachePageScripts::$working_script_array);
        }
        elseif (! empty(self::$_simcontrol_pagescript["working_script_array"]))
        {
            Return count(self::$_simcontrol_pagescript["working_script_array"]);
        }
        Return null;
    
    }

    protected function initialisecounter($count = 0)
    {

        if (! $count)
        {
            Return false;
        }
        for ($i = 1; $i <= $count; $i ++)
        {
            $counter .= "1";
        }
        Return $counter;
    
    }

    protected function setLoadInstructionArray($property, $min = 0)
    {

        if (empty($property))
        {
            Return false;
        }
        $lp = null;
        if (class_exists('MulticachePageScripts') && property_exists('MulticachePageScripts', $property))
        {
            $scrpt = MulticachePageScripts::$$property;
            $property_name = "_" . $property . "_loadinstruction";
            $lp = true;
            foreach ($scrpt as $key => $script)
            {
                self::$$property_name .= isset($script["loadsection"]) && $script["loadsection"] >= $min ? (string) $script["loadsection"] : (string) $min;
            }
        }
        elseif (isset(self::$_simcontrol_pagescript[$property]))
        {
            $scrpt = self::$_simcontrol_pagescript[$property];
            $property_name = "_" . $property . "_loadinstruction";
            $lp = true;
            foreach ($scrpt as $key => $script)
            {
                self::$$property_name .= isset($script["loadsection"]) && $script["loadsection"] >= $min ? (string) $script["loadsection"] : (string) $min;
            }
        }
        
        Return $lp;
    
    }

    protected function getlnparams()
    {

        static $params = null;
        if (isset($params))
        {
            Return $params;
        }
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('*');
        $query->from($db->quoteName('#__multicache_config'));
        $query->where($db->quoteName('id') . ' = ' . $db->quote('1'));
        $db->setQuery($query);
        $res = $db->loadObject();
        $params = $res;
        Return $res;
    
    }

    protected function gettestgroup($adv_flag = 0)
    {

        $advanced = empty($adv_flag) ? 'normal' : 'advanced';
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('*');
        $query->from($db->quoteName('#__multicache_advanced_testgroups'));
        $query->where($db->quoteName('status') . " NOT LIKE " . $db->quote('complete'));
        $query->where($db->quoteName('status') . " NOT LIKE " . $db->quote('abandoned'));
        $query->where($db->quoteName('advanced') . " = " . $db->quote($advanced));
        $query->order($db->quoteName('id') . ' DESC');
        $db->setQuery($query);
        Return $db->loadObject();
    
    }

    protected function initiatetestgroup($adv_flag = 0)
    {

        if (! empty($adv_flag) && ! class_exists('Loadinstruction'))
        {
            if (defined("MULTICACHESIMULATIONDEBUG"))
            {
                echo JText::_("COM_MULTICACHE_SIMCONTROL_ADVANCED_LOADINSTRUCTION_DOESNOTEXIST");
            }
            Return false;
        }
        if (! empty($adv_flag))
        {
            $success = $this->conductPreliminaryChecks();
            if (! $success)
            {
                if (defined("MULTICACHESIMULATIONDEBUG"))
                {
                    $e_message = "COM_MULTICACHE_SIMCONTROL_ADVANCED_JAVASCRIPT_INITIALIZATION_ERROR";
                    echo JText::_($e_message);
                }
                JLog::add(JText::_($emessage), JLog::NOTICE);
                Return false;
            }
        }
        
        $app = JFactory::getApplication();
        $advanced = empty($adv_flag) ? 'normal' : 'advanced';
        $params = $this->_lnparams;
        $start_date = date('d-m-Y');
        $start_time = microtime(true);
        $cycles = $params->gtmetrix_cycles;
        $min_precache = (int) $params->precache_factor_min;
        $max_precache = (int) $params->precache_factor_max;
        $min_cachecompression = (float) $params->gzip_factor_min; // $min_gzip -> $min_cachecompression
        $max_cachecompression = (float) $params->gzip_factor_max; // $max_gzip -> $max_cachecompression
        $step_cachecompression = (float) $params->gzip_factor_step; // $step_gzip -> $step_cachecompression
        $test_page = $params->gtmetrix_test_url;
        
        if (strtolower($test_page) == strtolower(substr(JURI::root(), 0, - 1)))
        {
            $test_page = strtolower(JURI::root());
        }
        
        $precache_sequences = ($max_precache - $min_precache) + 1;
        if (empty($step_cachecompression))
        {
            $emessage = "COM_MULTICACHE_ERROR_SIM_CONTROL_STEP_EMPTY";
            JLog::add(JText::_($emessage), JLog::NOTICE);
        }
        $step_cachecompression = empty($step_cachecompression) ? 1 : $step_cachecompression; // filtering the input for 0
        $cachecompression_sequences = 1 + (($max_cachecompression - $min_cachecompression) / $step_cachecompression); // $gzip_sequences -> $cachecompression_sequences
        
        if ($cachecompression_sequences <= 1)
        {
            $emessage = "COM_MULTICACHE_ERROR_SIM_CONTROL_CACHE_COMPRESSION_SEQUENCES_ERROR";
            JLog::add(JText::_($emessage), JLog::NOTICE);
        }
        $cachecompression_sequences = ($cachecompression_sequences <= 1) ? 1 : $cachecompression_sequences; // filtering wrong input in cache compression sequences
        if ($advanced == 'advanced')
        {
            $load_states = empty(self::$_linstruction) ? 1 : count(self::$_linstruction);
        }
        else
        {
            $load_states = 1;
        }
        
        $expected_tests = $cachecompression_sequences * $precache_sequences * $load_states * $cycles;
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $insertobj = new stdClass();
        $insertobj->advanced = $advanced;
        $insertobj->cycles = $cycles;
        $insertobj->cycles_complete = 0;
        $insertobj->expected_tests = $expected_tests;
        $insertobj->start_date = $start_date;
        $insertobj->start_time = $start_time;
        $insertobj->status = 'initiated';
        $insertobj->test_page = $test_page;
        
        if (! empty($adv_flag))
        {
            $success = MulticacheFrontendHelper::lockSimControl(true); // a lock protection mechanism
            if ($success && defined("MULTICACHESIMULATIONDEBUG"))
            {
                echo JText::_("COM_MULTICACHE_SIMCONTROL_DEBUG_SIMCONTROL_SUCCESFULLY_LOCKED_INADVANCEDMODE");
            }
        }
        if ((! empty($adv_flag) && $success) || empty($adv_flag))
        {
            $result = $db->insertObject('#__multicache_advanced_testgroups', $insertobj);
        }
    
    }

    protected function conductPreliminaryChecks()
    {

        /*
         * 1. get the oth object of self::$_linstruction
         * 2. get the string length
         * string length should be equal to count of working script array
         */
        if (empty(self::$_linstruction) || ! (class_exists('MulticachePageScripts') && property_exists('MulticachePageScripts', 'working_script_array')))
        {
            Return false;
        }
        $loadinstruction_length = strlen(self::$_linstruction['0']);
        $working_scripts = MulticachePageScripts::$working_script_array;
        $working_scripts_count = count($working_scripts);
        if ($loadinstruction_length === $working_scripts_count)
        {
            Return true;
        }
        else
        {
            Return false;
        }
    
    }

    protected function getlasttest($sim = 'simulation', $advanced = 'normal')
    {

        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $test_group = $this->_test_group;
        $query->select('*');
        $query->from($db->quoteName('#__multicache_advanced_test_results'));
        if ($sim == 'simulation')
        {
            $query->where($db->quoteName('test_page') . " = " . $db->quote($test_group->test_page));
            $query->where($db->quoteName('group_id') . " = " . $db->quote($test_group->id));
            $query->where($db->quoteName('simulation') . "  LIKE " . $db->quote('simulation'));
            $query->where($db->quoteName('advanced') . "  LIKE " . $db->quote($advanced));
        }
        else
        {
            $query->where($db->quoteName('simulation') . "  LIKE " . $db->quote('off'));
        }
        $query->order($db->quoteName('id') . ' DESC');
        $db->setQuery($query);
        Return $db->loadObject();
    
    }

    protected function loadNextTest($last_test = NULL, $precache_factor = NULL, $cache_compression_factor = NULL, $linstruc_key = NULL, $advanced = 'normal')
    {

        $param = $this->_lnparams;
        $parent = $this->_test_group;
        $db = JFactory::getDBO();
        $lquery = $db->getQuery(true);
        if (! $last_test)
        {
            $current_test = 1;
        }
        else
        {
            $current_test = (int) $last_test->current_test;
            if (++ $current_test > $last_test->max_tests)
            {
                $current_test = 1;
            }
        }
        // set the loadinstruc key and state
        $dateoftest = date("d-m-Y");
        $testdate_dbops = date("Y-m-d H:i:s");
        $mtime = microtime(true);
        $max_tests = $param->gtmetrix_api_budget;
        if (! isset($precache_factor))
        {
            $precache_factor = $param->precache_factor_min;
        }
        if (! isset($cache_compression_factor))
        {
            $cache_compression_factor = $param->gzip_factor_min;
        }
        if ($advanced == 'advanced')
        {
            if (! isset($linstruc_key))
            {
                $load_key = 0;
            }
            else
            {
                $load_key = $linstruc_key;
            }
            $load_state = empty(self::$_linstruction) ? NULL : self::$_linstruction[$load_key];
        }
        else
        {
            $load_key = NULL;
            $load_state = NULL;
        }
        
        $test_page = $parent->test_page;
        $parent_groupid = $parent->id;
        
        $insertobj = new stdClass();
        $insertobj->date_of_test = $dateoftest;
        $insertobj->group_id = $parent_groupid;
        $insertobj->mtime = $mtime;
        $insertobj->max_tests = $max_tests;
        $insertobj->current_test = $current_test;
        $insertobj->loadinstruc_key = $load_key;
        $insertobj->loadinstruc_state = $load_state;
        $insertobj->precache_factor = $precache_factor;
        $insertobj->cache_compression_factor = $cache_compression_factor;
        $insertobj->test_page = $test_page;
        $insertobj->status = 'initiated';
        $insertobj->simulation = 'simulation';
        $insertobj->advanced = $advanced;
        $insertobj->test_date = $testdate_dbops;
        $insertobj->cache_handler = $param->cache_handler;
        $insertobj->hammer_mode = $param->multicachedistribution;
        $result = $db->insertObject('#__multicache_advanced_test_results', $insertobj);
    
    }

    protected function alignCombinePageScripts($page_script_object, $load_key, $load_state)
    {

        $app = JFactory::getApplication();
        if (! isset($page_script_object) || ! isset($load_key) || ! isset($load_state))
        {
            $emessage = "COM_MULTICACHE_SIMCONTROL_ALIGNCOMBINEPAGESCRIPTS_NOT_SET_ERROR";
            JLog::add(JText::_($emessage), JLog::WARNING);
            Return false;
        }
        
        $lsate_count = strlen($load_state);
        if (empty($lsate_count) || $lsate_count != count($page_script_object))
        {
            $emessage = "COM_MULTICACHE_SIMCONTROL_ALIGNCOMBINEPAGESCRIPTS_LOADSTATE_DIFFERENT_ERROR";
            JLog::add(JText::_($emessage), JLog::WARNING);
            Return false;
        }
        $pointer = 0;
        foreach ($page_script_object as $key => $script)
        {
            $page_script_object[$key]["loadsection"] = $load_state[$pointer ++];
        }
        
        Return $page_script_object;
    
    }

    protected function getPreviousKey($key, $jsarray)
    {

        $key --;
        while ($key >= 0)
        {
            if (isset($jsarray[$key]))
            {
                Return $key;
            }
            $key --;
        }
        Return false;
    
    }

    protected function getNextKey($key, $jsarray)
    {

        if (empty($jsarray))
        {
            Return false;
        }
        $max_key = max(array_keys($jsarray));
        $key ++;
        while ($key <= $max_key)
        {
            if (isset($jsarray[$key]))
            {
                Return $key;
            }
            $key ++;
        }
        Return false;
    
    }

    protected function assignGroups($jsarray)
    {

        if (empty($jsarray))
        {
            Return false;
        }
        
        foreach ($jsarray as $key => $js)
        {
            
            // exclude libraries
            if ($jsarray[$key]["library"])
            {
                $skip_increment_group = true;
                continue;
            }
            // exclude cdns
            if ($jsarray[$key]["cdnalias"])
            {
                $skip_increment_group = true;
                continue;
            }
            // block external scripts from being in groups abs_internal = $jsarray[$key]["internal"] || $jsarray[$key]["code"]
            if (! ($jsarray[$key]["internal"] || $jsarray[$key]["code"]))
            {
                $skip_increment_group = true;
                continue;
            }
            $prev_key = $this->getPreviousKey($key, $jsarray);
            $next_key = $this->getNextKey($key, $jsarray);
            // if any one side is internal but not library group but not cdn
            if (((! empty($prev_key) || $prev_key === 0) && isset($jsarray[$prev_key]) && // following rules only if the previous key exists
! $jsarray[$prev_key]["library"] && // dont group with libraries
($jsarray[$prev_key]["loadsection"] == $jsarray[$key]["loadsection"]) && // dont group varying loadsections
($jsarray[$prev_key]["internal"] || $jsarray[$prev_key]["code"]) && // should not be external link
! $jsarray[$prev_key]["cdnalias"]) || // should not be a cdn cdnalias
                                                  // can be grouped with next key
            ((! empty($next_key) || $next_key === 0) && isset($jsarray[$next_key]) && ! $jsarray[$next_key]["library"] && ($jsarray[$next_key]["loadsection"] == $jsarray[$key]["loadsection"]) && ($jsarray[$next_key]["internal"] || $jsarray[$next_key]["code"]) && // checking next internal
! $jsarray[$next_key]["cdnalias"]))
            {
                // initialise the group counter
                if (! isset($group_counter))
                {
                    $group_counter = 0;
                }
                if ($skip_increment_group)
                {
                    // increment the group counter
                    // reset the flag
                    $group_counter ++;
                    $skip_increment_group = false;
                }
                $group_code = "group-" . $group_counter;
                
                $jsarray[$key]["group"] = $group_code;
            }
        }
        
        Return $jsarray;
    
    }

    protected function initialiseGroupHash($jsarray, $load_state)
    {

        if (empty($jsarray))
        {
            Return false;
        }
        
        foreach ($jsarray as $key => $value)
        {
            if ($jsarray[$key]["group"])
            {
                $group_number = $jsarray[$key]["group"];
                
                self::$_groups[$load_state][$group_number]["name"] = $group_number . '-' . $load_state;
                self::$_groups[$load_state][$group_number]["url"] = null; // this is the raw url
                self::$_groups[$load_state][$group_number]["callable_url"] = null; // a getScript code with url embed
                self::$_groups[$load_state][$group_number]["script_tag_url"] = null; // a script taged url
                
                self::$_groups[$load_state][$group_number]["combined_code"] = null;
                self::$_groups[$load_state][$group_number]["success"] = null;
                
                self::$_groups[$load_state][$group_number]["items"][] = $value;
            }
        }
    
    }

    protected function getCombinedCode($grp)
    {

        if (empty($grp))
        {
            Return false;
        }
        $app = JFactory::getApplication();
        
        foreach ($grp as $key => $group)
        {
            
            $begin_comment = "/* Inserted by MulticacheSimulationControl source code insert	key-" . $key . "	rank-" . $group["rank"] . "  src-" . substr($group["src"], 0, 10) . " */";
            $end_comment = "/* end MulticacheSimulationControl insert */";
            $begin_comment_code = "/* Inserted by MulticacheSimulationControl  code insert	key-" . $key . "	rank-" . $group["rank"] . "   */";
            $end_comment_code = "/* end MulticacheSimulationControl code insert */";
            
            if ($group["internal"])
            {
                // actual question here is is this source or is this code
                // source the code and place it here
                // ensure it ends with ;
                $url = $group["absolute_src"];
                
                $curl_obj = MulticacheFrontendHelper::get_web_page($url);
                if ($curl_obj["http_code"] == 200)
                {
                    // $code_string .= $begin_comment . MulticacheFrontendHelper::clean_code(trim($curl_obj["content"])) . $end_comment;
                    $code_string .= ! empty(self::$_jscomments) ? $begin_comment . MulticacheFrontendHelper::clean_code(trim($curl_obj["content"])) . $end_comment : MulticacheFrontendHelper::clean_code(trim($curl_obj["content"]));
                }
                else
                {
                    // register error
                    
                    $e_message = "	" . $curl_obj["errmsg"];
                    $emessage = "COM_MULTICACHE_ERROR_SIMCONTROL_GETCOMBINECODE_CURL_ERROR";
                    JLog::add(JText::_($emessage) . $e_message, JLog::WARNING);
                    Return false;
                }
            }
            else
            {
                // unserialize and tie code here
                if (! empty($group["serialized_code"]))
                {
                    // $code_string .= $begin_comment_code . MulticacheFrontendHelper::clean_code(trim(unserialize($group["serialized_code"]))) . $end_comment_code;
                    $code_string .= ! empty(self::$_jscomments) ? $begin_comment_code . MulticacheFrontendHelper::clean_code(trim(unserialize($group["serialized_code"]))) . $end_comment_code : MulticacheFrontendHelper::clean_code(trim(unserialize($group["serialized_code"])));
                }
                else
                {
                    // register error
                    $emessage = "COM_MULTICACHE_ERROR_SIMCONTROL_GROUP_NOT_INTERNAL_CODE_EMPTY_ROOT_SCRIPT_DETECT_ERROR";
                    JLog::add(JText::_($emessage), JLog::WARNING);
                    Return false;
                }
            }
        }
        
        Return serialize($code_string);
    
    }

    protected function combineGroupCode($load_state)
    {

        if (empty(self::$_groups[$load_state]))
        {
            Return false;
        }
        foreach (self::$_groups[$load_state] as $group_name => $group)
        {
            self::$_groups[$load_state][$group_name]["combined_code"] = $this->getCombinedCode($group["items"]);
            self::$_groups[$load_state][$group_name]["success"] = ! empty(self::$_groups[$load_state][$group_name]["combined_code"]) ? true : false;
        }
    
    }

    protected function prepareGrouploadableUrl($load_state)
    {

        if (! isset(self::$_groups[$load_state]))
        {
            Return false;
        }
        
        foreach (self::$_groups[$load_state] as $key => $grp)
        {
            if ($grp["success"])
            {
                
                self::$_groups[$load_state][$key]["url"] = MulticacheFrontendHelper::getJScodeUrl($load_state, $key, "raw_url", self::$_principle_jquery_scope, self::$_mediaVersion);
                self::$_groups[$load_state][$key]["callable_url"] = MulticacheFrontendHelper::getJScodeUrl($load_state, $key, null, self::$_principle_jquery_scope, self::$_mediaVersion);
                self::$_groups[$load_state][$key]["script_tag_url"] = MulticacheFrontendHelper::getJScodeUrl($load_state, $key, "script_url", self::$_principle_jquery_scope, self::$_mediaVersion);
            }
        }
    
    }

    protected function writeGroupCode($load_state)
    {

        if (empty($load_state) || ! isset(self::$_groups[$load_state]))
        {
            Return false;
        }
        foreach (self::$_groups[$load_state] as $key => $grp)
        {
            
            if ($grp["success"])
            {
                $file_name = $grp["name"] . ".js";
                $success = MulticacheFrontendHelper::writeJsCache(unserialize($grp["combined_code"]), $file_name);
                self::$_groups[$load_state][$key]["success"] = ! empty($success) ? true : false;
            }
        }
    
    }

    /* The delay group is held constant over the iterations hence there is no requirement to segregate basis test ids or flags */
    protected function makeDelaycode()
    {
        // writes the first level js to be called by the main page
        if (empty(self::$_delayable_segment))
        {
            Return false;
        }
        
        foreach (self::$_delayable_segment as $key => $value)
        {
            $delay_code = MulticacheFrontendHelper::getdelaycode($key, self::$_principle_jquery_scope, self::$_mediaVersion); // initialises the delay code
            
            if (! empty($delay_code))
            {
                self::$_delayable_segment[$key]["delay_executable_code"] = $delay_code["code"];
                self::$_delayable_segment[$key]["delay_callable_url"] = $delay_code["url"];
            }
        }
    
    }

    protected function setCDNtosignature($jsarray)
    {

        if (empty($jsarray))
        {
            Return false;
        }
        
        // get all cdn signatures
        foreach ($jsarray as $key => $js)
        {
            if (! empty($js["cdn_url"]))
            {
                $sig = $js["signature"];
                self::$_cdn_segment[$sig] = $js["cdn_url"];
            }
        }
        foreach ($jsarray as $key => $js)
        {
            $sig = $js["signature"];
            if (isset(self::$_cdn_segment[$sig]) && empty($js["cdn_url"]))
            {
                $jsarray[$key]["cdnalias"] = 1;
                $jsarray[$key]["cdn_url"] = self::$_cdn_segment[$sig];
            }
        }
        Return $jsarray;
    
    }

    protected function placeDelayedCode($grp)
    {

        if (empty($grp["items"]) || empty($grp["delay_callable_url"]))
        {
            Return false;
        }
        $app = JFactory::getApplication();
        
        // start
        if (! isset(self::$_principle_jquery_scope))
        {
            self::$_principle_jquery_scope = "jQuery";
        }
        $begin_comment = "/* Begin delay prepared by MulticacheSimControl for " . $grp["delay_callable_url"] . "	*/";
        // $code_string = $begin_comment;
        if (! empty(self::$_jscomments))
        {
            $code_string = $begin_comment;
        }
        
        foreach ($grp["items"] as $key => $group)
        {
            $sig = $group["signature"];
            
            if (isset(self::$_cdn_segment[$sig]) && (bool) self::$_cdn_segment[$sig] == true)
            {
                $url = self::$_cdn_segment[$sig];
                $c_string = '
' . self::$_principle_jquery_scope . '.getScript( "' . $url . '", function() {


}).fail(function() {

    console.log("loading failed in ' . $url . '" );


        });

';
                $code_string .= $c_string;
            }
            
            elseif (isset($group["internal"]) && $group["internal"] == true)
            {
                // this is src and internal
                // as were callingafter delay no need to curl ;
                $url = $group["absolute_src"];
                
                $begin_comment = "/* Inserted by MulticacheSimControl InternalDelay source code insert	url-" . $url . "	 */";
                $end_comment = "/* end MulticacheSimControl InternalDelay insert */";
                $curl_obj = MulticacheFrontendHelper::get_web_page($url);
                if ($curl_obj["http_code"] == 200)
                {
                    // $c_string .= $begin_comment . MulticacheFrontendHelper::clean_code(trim($curl_obj["content"])) . $end_comment;
                    $c_string .= ! empty(self::$_jscomments) ? $begin_comment . MulticacheFrontendHelper::clean_code(trim($curl_obj["content"])) . $end_comment : MulticacheFrontendHelper::clean_code(trim($curl_obj["content"]));
                }
                else
                {
                    // register error
                    
                    $e_message = "	" . $curl_obj["errmsg"];
                    $app->enqueueMessage(JText::_('COM_MULTICACHE_SIMCONTROL_ERROR_PAGESCRIPT_INTERNALDELAY_CURL_ERROR') . $e_message, 'warning');
                    Return false;
                }
                /*
                 * $c_string = '
                 * ' . self::$_principle_jquery_scope . '.getScript( "' . $url . '", function() {
                 *
                 *
                 * }).fail(function() {
                 *
                 * console.log("loading failed in ' . $url . '" );
                 *
                 *
                 * });
                 *
                 * ';
                 */
                $code_string .= $c_string;
            }
            elseif (isset($group["internal"]) && $group["internal"] == false)
            {
                $url = $group["src"];
                $c_string = '
' . self::$_principle_jquery_scope . '.getScript( "' . $url . '", function() {


}).fail(function() {

    console.log("loading failed in ' . $url . '");


        });

';
                $code_string .= $c_string;
            }
            elseif (! isset($group["internal"]) && $group["code"])
            {
                $begin_comment = "/* MulticacheSimControl Insert for  code   " . str_replace("'", "", str_replace('"', "", substr($group["code"], 0, 10))) . " */";
                
                $end_comment = "/* end insert of code 	  " . str_replace("'", "", str_replace('"', "", substr($group["code"], 0, 10))) . " */";
                // unserialize and tie code here
                if (isset($group["serialized_code"]))
                {
                    // $code_string .= $begin_comment . MulticacheFrontendHelper::clean_code(trim(unserialize($group["serialized_code"]))) . $end_comment;
                    $code_string .= ! empty(self::$_jscomments) ? $begin_comment . MulticacheFrontendHelper::clean_code(trim(unserialize($group["serialized_code"]))) . $end_comment : MulticacheFrontendHelper::clean_code(trim(unserialize($group["serialized_code"])));
                }
                else
                {
                    // register error
                    
                    // $app->enqueueMessage(JText::_('COM_MULTICACHE_CLASS_MULTICACHE_PAGE_SCRIPTS_URL_NOT_INTERNAL_NOT_CODE_INDELAY_ERROR'), 'error');
                    $emessage = "COM_MULTICACHE_SIMCONTROL_PLACEDELAYEDCODE_URL_NOT_INTERNAL_NOT_CODE_INDELAY_ERROR";
                    JLog::add(JText::_($emessage), JLog::ERROR);
                    Return false;
                }
            }
        }
        $end_comment = "/* End of  delay prepared by MulticacheSimControl for " . $grp["delay_callable_url"] . "	*/";
        // $code_string .= $end_comment;
        if (! empty(self::$_jscomments))
        {
            $code_string .= $end_comment;
        }
        
        ob_start();
        echo $code_string;
        $buffer = ob_get_clean();
        $return = MulticacheFrontendHelper::writeJsCache($buffer, $grp["delay_callable_url"]);
        
        Return $return;
        
        // stop
    }

    protected function segregatePlaceDelay()
    {

        if (empty(self::$_delayable_segment))
        {
            Return false;
        }
        $app = JFactory::getApplication();
        
        foreach (self::$_delayable_segment as $key_delaytype => $delay_seg)
        {
            $success = $this->placeDelayedCode($delay_seg);
            if ($success)
            {
                self::$_delayable_segment[$key_delaytype]["success"] = true;
            }
            else
            {
                self::$_delayable_segment[$key_delaytype]["success"] = false;
                $app->enqueueMessage(JText::_('COM_MULTICACHE_SIMCONTROL_CLASS_MULTICACHE_PAGE_SCRIPTS_PLACE_DELAY_FAILED') . $key_delaytype, 'error');
                
                $emessage = "COM_MULTICACHE_SIMCONTROL_PLACE_DELAY_FAILED";
                JLog::add(JText::_($emessage) . $key_delaytype, JLog::ERROR);
            }
        }
    
    }

    protected function getLoadSection($section, $jsarray_obj, $load_state)
    {

        if (empty($jsarray_obj) || empty($section))
        {
            Return false;
        }
        $app = JFactory::getApplication();
        
        foreach ($jsarray_obj as $obj)
        {
            
            if ($obj["loadsection"] != $section)
            {
                continue;
            }
            
            if (isset($obj["group"]) && (bool) ($group_name = $obj["group"]) == true && isset(self::$_groups[$load_state][$group_name]["success"]) && self::$_groups[$load_state][$group_name]["success"] == true)
            {
                
                if (! isset(self::$_groups_loaded[$load_state][$group_name]))
                {
                    
                    $load_string .= unserialize(self::$_groups[$load_state][$group_name]["script_tag_url"]); //
                    
                    /*
                     * OTHER OPTIONS
                     *
                     * $load_string .= MulticacheHelper::getloadableSourceScript(self::$_groups[$group_name]["url"] , false);
                     *
                     * $load_string .= MulticacheHelper::getloadableSourceScript(unserialize( self::$_groups[$group_name]["callable_url"]) , false);
                     */
                    self::$_groups_loaded[$load_state][$group_name] = true;
                }
                
                continue;
            }
            
            $sig = $obj["signature"];
            if (isset(self::$_cdn_segment[$sig]) && (bool) self::$_cdn_segment[$sig] == true)
            {
                $load_string .= MulticacheFrontendHelper::getloadableSourceScript(self::$_cdn_segment[$sig], $obj["async"]);
            } // if src else code
            elseif (! empty($obj["src"]))
            {
                
                // if obj int use only absolute
                if ($obj["internal"])
                {
                    
                    $load_string .= MulticacheFrontendHelper::getloadableSourceScript($obj["absolute_src"], $obj["async"]);
                }
                elseif (! $obj["internal"])
                {
                    // redundancy delared on purpose to maintain elseif formats
                    // external source
                    $load_string .= MulticacheFrontendHelper::getloadableSourceScript($obj["src"], $obj["async"]);
                }
                /*
                 * MORE ELSEIF CAN COME HERE TO ENTERTAIN ALIAS LOADING ETC.
                 */
            }
            elseif (! empty($obj["code"]))
            {
                //
                $load_string .= MulticacheFrontendHelper::getloadableCodeScript($obj["serialized_code"], $obj["async"]);
            }
            else
            {
                
                $emessage = "COM_MULTICACHE_SIMCONTROL_GETLOADSECTION_LOADSECTION_UNDEFINED_SCRIPT_TYPE_ERROR";
                JLog::add(JText::_($emessage), JLog::ERROR);
            }
        }
        
        if (empty($load_string))
        {
            
            Return false;
        }
        Return serialize($load_string);
    
    }

    protected function prepareLoadSections($jsarray, $load_state)
    {

        self::$_loadsections[1] = $this->getLoadSection(1, $jsarray, $load_state);
        self::$_loadsections[2] = $this->getLoadSection(2, $jsarray, $load_state);
        self::$_loadsections[3] = $this->getLoadSection(3, $jsarray, $load_state);
        self::$_loadsections[4] = $this->getLoadSection(4, $jsarray, $load_state);
    
    }

    protected function combineSectionFooter($object)
    {

        if (empty($object))
        {
            Return false;
        }
        $loadsections = self::$_loadsections;
        $footer_segment = $loadsections[4];
        /* NOTe This is arbitrary- for more sophistication we can point to end and then take the key.end($loadsections);$key = key($loadsections); */
        // get the load string
        if (! empty($footer_segment))
        {
            $load_string = unserialize($footer_segment);
        }
        
        foreach ($object as $obj)
        {
            /* NOTE: We have not provided for Advertisements/SOCIAL to be loaded for CDN. We need to set exceptioon handlers for ads & social that are marked to cdn */
            if (! empty($obj["src"]))
            {
                if ($obj["internal"])
                {
                    $load_string .= MulticacheFrontendHelper::getloadableSourceScript($obj["absolute_src"], $obj["async"]);
                }
                elseif (! $obj["internal"])
                {
                    $load_string .= MulticacheFrontendHelper::getloadableSourceScript($obj["src"], $obj["async"]);
                }
            }
            else
            {
                
                $load_string .= MulticacheFrontendHelper::getloadableCodeScript($obj["serialized_code"], $obj["async"]);
            }
        }
        if (empty($load_string))
        {
            Return false;
        }
        self::$_loadsections[4] = serialize($load_string);
        Return true;
    
    }

    protected function combineDelay()
    {

        if (empty(self::$_delayable_segment))
        {
            Return false;
        }
        
        $delay = self::$_principle_jquery_scope . "( document ).ready(function() {";
        foreach (self::$_delayable_segment as $delay_type_key => $delay_obj)
        {
            if (! empty($delay_obj["delay_executable_code"]))
            {
                $delay .= unserialize($delay_obj["delay_executable_code"]);
            }
        }
        $delay .= "});";
        $delay = serialize($delay); // just to make it compatible with earlier processes
                                    // $ds = MulticacheHelper::getloadableCodeScript( $delay ,false );
        $loadsections = self::$_loadsections;
        $footer_segment = $loadsections[4];
        if (! empty($footer_segment))
        {
            $load_string = unserialize($footer_segment);
        }
        
        $load_string .= MulticacheFrontendHelper::getloadableCodeScript($delay, false);
        if (empty($load_string))
        {
            Return false;
        }
        self::$_loadsections[4] = serialize($load_string);
        Return true;
    
    }

    protected function makeSignatureHash($obj)
    {

        if (empty($obj))
        {
            Return false;
        }
        
        foreach ($obj as $key => $js)
        {
            $sig = $js["signature"];
            $alt_sig = $js["alt_signature"];
            if (! isset(self::$_signature_hash[$sig]))
            {
                self::$_signature_hash[$sig] = true;
            }
            if (isset($alt_sig) && ! isset(self::$_signature_hash[$alt_sig]))
            {
                self::$_signature_hash[$alt_sig] = true;
            }
        }
        Return true;
    
    }

    protected function makeDelaySignatureHash($delay_obj)
    {

        if (empty($delay_obj))
        {
            Return false;
        }
        
        foreach ($delay_obj as $object)
        {
            
            $this->makeSignatureHash($object["items"]);
        }
    
    }

    protected function prepareDontMove($dontmovesegment, $params)
    {

        if (! empty($dontmovesegment))
        {
            foreach ($dontmovesegment as $key => $dontmove)
            {
                if (isset($dontmove['signature']))
                {
                    $hash = $dontmove['signature'];
                    self::$dontmove_js[$hash] = true;
                }
                if (isset($dontmove['src']))
                {
                    $src = $dontmove['src'];
                    $cleaned_src = str_replace(array(
                        'https',
                        'http',
                        '://',
                        '//',
                        'www.'
                    ), '', $src);
                    self::$dontmove_urls_js[$cleaned_src] = 1;
                }
            }
        }
        
        if (! empty($params))
        {
            $params = unserialize($params);
            $params = $params['positional_dontmovesrc'];
            if (empty($params))
            {
                Return false;
            }
            foreach ($params as $key => $param)
            {
                self::$dontmove_urls_js[$param] = 1;
            }
        }
    
    }

    protected function prepareCacheStrategy($last_test, $load_state = null, $type = null)
    {

        $app = JFactory::getApplication();
        $lnparams = $this->getlnparams();
        if (empty($last_test) && ! isset($type))
        {
            if (defined("MULTICACHESIMULATIONDEBUG"))
            {
                echo JText::_("COM_MULTICACHE_SIMCONTROL_DEBUG_PREPARE_CACHE_STRATEGY_LASTTESTNOTSET_NOTLOADMAIN_ERROR");
                echo "<br>";
            }
            Return false;
        }
        if (! isset(self::$_jscomments))
        {
            self::$_jscomments = $lnparams->js_comments;
        }
        
        if (isset($type) && $type == "load_main")
        {
            if (empty(self::$_linstruction))
            {
                $emessage = "COM_MULTICACHE_SIMCONTROL_CLASS_LOADINSTRUCTION_ERROR_LABEL_EMPTY";
                if (defined("MULTICACHESIMULATIONDEBUG"))
                {
                    echo JText::_($emessage);
                    echo "<br>";
                }
                
                JLog::add(JText::_($emessage), JLog::WARNING);
                Return false;
            }
            $load_key = array_search($load_state, self::$_linstruction);
            if (empty($load_key))
            {
                $emessage = "COM_MULTICACHE_SIMCONTROL_METHOD_PREPARECACHESTRATEGY_REQUESTED_LOAD_TACTIC_NOT_STRATEGIZED";
                if (defined("MULTICACHESIMULATIONDEBUG"))
                {
                    echo JText::_($emessage);
                    echo "<br>";
                }
                JLog::add(JText::_($emessage), JLog::WARNING);
                Return false;
            }
        }
        else
        {
            $load_key = $last_test->loadinstruc_key;
            $load_state = $last_test->loadinstruc_state;
        }
        
        if (property_exists('Loadinstruction', 'working_script_array'))
        {
            $page_script_object = Loadinstruction::$working_script_array; // MulticachePageScripts::$working_script_array;
        }
        else
        {
            $emessage = "COM_MULTICACHE_SIMCONTROL_PREPARETESTPAGE_WOKING_SCRIPT_ABSENT";
            if (defined("MULTICACHESIMULATIONDEBUG"))
            {
                echo JText::_($emessage);
                echo "<br>";
            }
            JLog::add(JText::_($emessage), JLog::WARNING);
            Return false;
        }
        if (empty($page_script_object))
        {
            if (defined("MULTICACHESIMULATIONDEBUG"))
            {
                $emessage = "COM_MULTICACHE_SIMCONTROL_PREPARETESTPAGE_PAGE_SCRIPT_EMPTY";
                echo JText::_($emessage);
                echo "<br>";
            }
            JLog::add(JText::_($emessage), JLog::WARNING);
            Return false;
        }
        $empty_page_script_check = array_filter($page_script_object);
        if (empty($empty_page_script_check))
        {
            $emessage = "COM_MULTICACHE_SIMCONTROL_PREPARETESTPAGE_PAGE_SCRIPT_EMPTY";
            if (defined("MULTICACHESIMULATIONDEBUG"))
            {
                echo JText::_($emessage);
                echo "<br>";
            }
            JLog::add(JText::_($emessage), JLog::WARNING);
            Return false;
        }
        $page_script_object = $this->setCDNtosignature($page_script_object);
        
        $page_script_object = $this->alignCombinePageScripts($page_script_object, $load_key, $load_state);
        $page_script_object = $this->assignGroups($page_script_object);
        $this->initialiseGroupHash($page_script_object, $load_state);
        $this->combineGroupCode($load_state);
        $this->prepareGrouploadableUrl($load_state);
        $this->writeGroupCode($load_state); // writes Group js scripts that will be loaded from JSCacheStrategy in operation mode flag $success if failed
        /* load the various segments segment - delayed, social , advertisement , async - NOTLOADING duplicates */
        self::$_delayable_segment = $this->loadProperty('delayed', 'Loadinstruction');
        self::$_social_segment = $this->loadProperty('social', 'Loadinstruction');
        self::$_advertisement_segment = $this->loadProperty('advertisements', 'Loadinstruction');
        self::$_async_segment = $this->loadProperty('async', 'Loadinstruction');
        $dontmove_segment = $this->loadProperty('dontmove', 'Loadinstruction');
        $this->prepareDontMove($dontmove_segment, $lnparams->params);
        $this->makeSignatureHash($page_script_object);
        $this->makeSignatureHash(self::$_social_segment);
        $this->makeSignatureHash(self::$_async_segment);
        $this->makeSignatureHash(self::$_advertisement_segment);
        $this->makeDelaySignatureHash(self::$_delayable_segment);
        $this->makeDelaycode();
        $this->segregatePlaceDelay();
        $this->prepareLoadsections($page_script_object, $load_state);
        $this->combineSectionFooter(self::$_advertisement_segment);
        $this->combineSectionFooter(self::$_social_segment);
        $this->combineSectionFooter(self::$_async_segment);
        $stubs = MulticacheFrontendHelper::prepareStubs($lnparams);
        if ($lnparams->conduit_switch)
        {
            // $this->combineConduitFooter();
        }
        $this->combineDelay();
        if (! empty($lnparams->jst_urlinclude) || ! empty($lnparams->jst_query_param) || ! empty($lnparams->excluded_components) || ! empty($lnparams->jst_url_string))
        {
            // js_tweaker_url_include_exclude
            // jst_query_include_exclude
            if (! empty($lnparams->jst_urlinclude))
            {
                $lnparams->jst_urlinclude = json_decode($lnparams->jst_urlinclude);
            }
            if (! empty($lnparams->jst_query_param))
            {
                $lnparams->jst_query_param = json_decode($lnparams->jst_query_param);
            }
            if (! empty($lnparams->excluded_components))
            {
                $excluded_components = unserialize($lnparams->excluded_components);
            }
            if (! empty($lnparams->jst_url_string))
            {
                $jst_url_string = json_decode($lnparams->jst_url_string);
            }
            
            $jst_object = MulticacheFrontendHelper::PrepareJSTexcludes($lnparams->js_tweaker_url_include_exclude, $lnparams->jst_query_include_exclude, $lnparams->jst_urlinclude, $lnparams->jst_query_param, $excluded_components, $jst_url_string);
        }
        
        if (isset($type) && $type == 'load_main')
        {
            $return = MulticacheFrontendHelper::writeJsCacheStrategyMain(self::$_signature_hash, self::$_loadsections, $this->_lnparams->js_switch, $load_state, $stubs, $jst_object, self::$dontmove_js, self::$dontmove_urls_js);
        }
        else
        {
            $return = MulticacheFrontendHelper::writeJsCacheStrategy(self::$_signature_hash, self::$_loadsections, $this->_lnparams->js_switch, $load_state, $stubs, $jst_object, self::$dontmove_js, self::$dontmove_urls_js);
        }
        
        Return $return;
    
    }

    protected function combineConduitFooter()
    {

        $loadsections = self::$_loadsections;
        $footer_segment = $loadsections[4];
        if (! empty($footer_segment))
        {
            $load_string = unserialize($footer_segment);
        }
        
        $conduit_src = JURI::root() . 'media/com_multicache/assets/js/conduit_footer.js';
        $load_string .= MulticacheFrontendHelper::getloadableSourceScript($conduit_src, false);
        if (empty($load_string))
        {
            Return false;
        }
        self::$_loadsections[4] = serialize($load_string);
        Return true;
    
    }

    protected function updateLoadvariables($precache_factor, $ccomp_factor, $loadinstruc_state = null)
    {

        $db = JFactory::getDbo();
        $updateObj = new stdClass();
        $updateObj->id = $this->_test_group->id;
        $updateObj->loaded_precache_factor = $precache_factor;
        $updateObj->loaded_cache_compression_factor = $ccomp_factor;
        $updateObj->loaded_loadinstruc_state = $loadinstruc_state;
        $updateObj->status = 'factors_devolved';
        $result = $db->updateObject('#__multicache_advanced_testgroups', $updateObj, 'id');
    
    }

    protected function deploy_algorithm()
    {

        $test_group = $this->_test_group;
        $precache_factor = $test_group->algorithm_precache_factor;
        $loadinstruc_state = ($test_group->advanced == 'advanced') ? $test_group->algorithm_loadinstruc_state : null;
        $ccomp_factor = $test_group->algorithm_cache_compression_factor;
        if ($test_group->advanced == 'advanced')
        {
            if (empty($loadinstruc_state))
            {
                $this->devolveNone();
                $emessage = "COM_MULTICACHE_ADVANCEDSIMULATION_DEPLOYALGORITH_FAILED_LOADINSTRUC_EMPTY";
                if (defined("MULTICACHESIMULATIONDEBUG"))
                {
                    echo JText::_($emessage);
                    echo "<br>";
                }
                JLog::add(JText::_($emessage), JLog::ERROR);
                Return false;
            }
            $prepared = $this->prepareCacheStrategy(null, $loadinstruc_state, 'load_main');
            if (! $prepared)
            {
                $emessage = "COM_MULTICACHE_ADVANCEDSIMULATION_DEPLOYALGORITH_FAILED_MESSAGE";
                JLog::add(JText::_($emessage), JLog::ERROR);
                Return false;
            }
        }
        MulticacheFrontendHelper::setJsSimulation(0, 'normal', null);
        MulticacheFrontendHelper::establish_factors($precache_factor, $ccomp_factor);
        MulticacheFrontendHelper::clean_cache('com_plugins', $test_group->test_page);
        $this->updateLoadvariables($precache_factor, $ccomp_factor, $loadinstruc_state);
        $emessage = "COM_MULTICACHE_ADVANCEDSIMULATION_DEPLOYALGORITH_SUCCEEDED_MESSAGE";
        JLog::add(JText::_($emessage), JLog::INFO);
    
    }

    protected function deploy_bestloadtime()
    {

        $test_group = $this->_test_group;
        $precache_factor = $test_group->blt_precache_factor;
        $ccomp_factor = $test_group->blt_cache_compression_factor;
        $load_state = ($test_group->advanced == 'advanced') ? $test_group->blt_loadinstruc_state : null;
        if ($this->_test_group->advanced == 'advanced')
        {
            if (empty($load_state))
            {
                $this->devolveNone();
                $emessage = "COM_MULTICACHE_ADVANCEDSIMULATION_DEPLOYBLT_FAILED_LOADINSTRUC_EMPTY";
                if (defined("MULTICACHESIMULATIONDEBUG"))
                {
                    echo JText::_($emessage);
                    echo "<br>";
                }
                JLog::add(JText::_($emessage), JLog::ERROR);
                Return false;
            }
            $prepared = $this->prepareCacheStrategy(null, $load_state, 'load_main');
            if (! $prepared)
            {
                $emessage = "COM_MULTICACHE_ADVANCEDSIMULATION_DEPLOYALBLT_FAILED_MESSAGE";
                JLog::add(JText::_($emessage), JLog::ERROR);
                Return false;
            }
        }
        MulticacheFrontendHelper::setJsSimulation(0, 'normal', null);
        MulticacheFrontendHelper::establish_factors($precache_factor, $ccomp_factor);
        MulticacheFrontendHelper::clean_cache('com_plugins', $test_group->test_page);
        $this->updateLoadvariables($precache_factor, $ccomp_factor, $load_state);
        $emessage = "COM_MULTICACHE_ADVANCEDSIMULATION_DEPLOYALBLT_SUCCEEDED_MESSAGE";
        JLog::add(JText::_($emessage), JLog::INFO);
    
    }

    protected function deploy_defaultsettings()
    {

        $test_group = $this->_test_group;
        $precache_factor = $this->_lnparams->precache_factor_default;
        $ccomp_factor = $this->_lnparams->gzip_factor_default;
        MulticacheFrontendHelper::setJsSimulation(0, 'normal', null);
        MulticacheFrontendHelper::establish_factors($precache_factor, $ccomp_factor);
        MulticacheFrontendHelper::clean_cache('com_plugins', $test_group->test_page);
        $this->updateLoadvariables($precache_factor, $ccomp_factor, null);
    
    }

    protected function abandonLastTest($last_test)
    {

        if (empty($last_test))
        {
            Return false;
        }
        $db = JFactory::getDbo();
        $updateObj = new stdClass();
        $updateObj->id = $last_test->id;
        
        $updateObj->status = 'test_abandoned';
        $result = $db->updateObject('#__multicache_advanced_test_results', $updateObj, 'id');
    
    }

    protected function onholdLastTest($last_test)
    {

        if (empty($last_test))
        {
            Return false;
        }
        $db = JFactory::getDbo();
        $updateObj = new stdClass();
        $updateObj->id = $last_test->id;
        
        $updateObj->status = 'test_on_hold';
        $result = $db->updateObject('#__multicache_advanced_test_results', $updateObj, 'id');
    
    }

    protected function reinitiateLastTest($last_test)
    {

        if (empty($last_test))
        {
            Return false;
        }
        
        $db = JFactory::getDbo();
        $updateObj = new stdClass();
        $updateObj->id = $last_test->id;
        
        $updateObj->status = 'initiated';
        $result = $db->updateObject('#__multicache_advanced_test_results', $updateObj, 'id');
    
    }
    // this status is only set for simulation tests as the factors are reverted. Non simulation tests do not require a revert of factors
    protected function setDailyBudgetComplete($last_test)
    {

        if (empty($last_test))
        {
            Return false;
        }
        $db = JFactory::getDbo();
        $updateObj = new stdClass();
        $updateObj->id = $last_test->id;
        
        $updateObj->status = 'daily_budget_complete';
        $result = $db->updateObject('#__multicache_advanced_test_results', $updateObj, 'id');
    
    }

    protected function prepare_next_test($ltest)
    {

        if (empty($ltest))
        {
            Return false;
        }
        $db = JFactory::getDBO();
        $db->getQuery(true);
        $params = $this->_lnparams;
        $parent_test = $this->_test_group;
        // close last test
        $updateobj = new stdClass();
        $updateobj->id = $ltest->id;
        $updateobj->status = 'complete';
        
        // prepare next test
        
        $precache_factor = $ltest->precache_factor;
        $cache_compression_factor = $ltest->cache_compression_factor;
        if ($ltest->advanced == 'normal')
        {
            self::$_linstruction = null;
            $load_key = NULL;
        }
        else
        {
            $load_key = $ltest->loadinstruc_key;
        }
        if (++ $precache_factor > $params->precache_factor_max)
        {
            $precache_factor = $params->precache_factor_min;
            if (($cache_compression_factor + $params->gzip_factor_step) > $params->gzip_factor_max)
            {
                $cache_compression_factor = $params->gzip_factor_min;
                
                if ($ltest->advanced == 'normal' || ! isset(self::$_linstruction[++ $load_key]))
                {
                    
                    $this->updateParentTestGroup($parent_test);
                }
            }
            else
            {
                $cache_compression_factor = $cache_compression_factor + $params->gzip_factor_step;
            }
        }
        
        $result = $db->updateObject('#__multicache_advanced_test_results', $updateobj, 'id');
        $this->loadNextTest($ltest, $precache_factor, $cache_compression_factor, $load_key, $parent_test->advanced);
    
    }

    protected function checkTestsTime()
    {

        $checktimestart = microtime(true) - (86400);
        $db = JFactory::getDBO();
        
        $check_query = $db->getQuery(true);
        
        $check_query->select('*');
        $check_query->from($db->quoteName('#__multicache_advanced_test_results'));
        $check_query->where($db->quoteName('mtime') . "  >= " . $db->quote($checktimestart));
        $check_query->order($db->quoteName('id') . ' DESC');
        $db->setQuery($check_query);
        $all_day_load = $db->loadObjectlist();
        
        $db->execute();
        $num_rows = $db->getNumRows();
        return $num_rows;
    
    }

    protected function updateParentTestGroup($parent_test)
    {

        if (empty($parent_test))
        {
            Return false;
        }
        
        $db = JFactory::getDbo();
        $app = JFactory::getApplication();
        
        if ($parent_test->status == 'complete' || $parent_test->status == 'factors_devolved')
        {
            Return;
        }
        if ($parent_test->cycles_complete < $parent_test->cycles)
        {
            ++ $parent_test->cycles_complete;
            $updateObj = new stdClass();
            $updateObj->id = $parent_test->id;
            $updateObj->cycles_complete = $parent_test->cycles_complete;
            $result = $db->updateObject('#__multicache_advanced_testgroups', $updateObj, 'id');
        }
        else
        {
            $this->updatePrecachefactorbase($parent_test);
            $this->updateCcompfactorbase($parent_test);
            $this->updateLoadInstructionbase($parent_test);
            $b_lt_query = $db->getQuery(true);
            $b_lt_query->select('MIN( `page_load_time`)  As min_plt');
            $b_lt_query->from($db->quoteName('#__multicache_advanced_test_results'));
            $b_lt_query->where(' ( ' . $db->quoteName('status') . "  LIKE " . $db->quote('complete') . ' OR ' . $db->quoteName('status') . "  LIKE " . $db->quote('test_recorded') . ' ) ');
            $b_lt_query->where($db->quoteName('group_id') . "  = " . $db->quote($parent_test->id));
            $b_lt_query->where($db->quoteName('simulation') . "  LIKE " . $db->quote('simulation'));
            $b_lt_query->where($db->quoteName('page_load_time') . "  != " . $db->quote(0));
            $b_lt_query->where($db->quoteName('page_load_time') . "  != " . $db->quote(""));
            $b_lt_query->where($db->quoteName('page_load_time') . "  IS NOT NULL");
            
            $db->setQuery($b_lt_query);
            $min_plt = $db->loadObject()->min_plt;
            
            $best_loadtime_query = $db->getQuery(true);
            $best_loadtime_query->select($db->quoteName('page_load_time'));
            $best_loadtime_query->select($db->quoteName('precache_factor'));
            $best_loadtime_query->select($db->quoteName('cache_compression_factor'));
            $best_loadtime_query->select($db->quoteName('loadinstruc_state'));
            $best_loadtime_query->from($db->quoteName('#__multicache_advanced_test_results'));
            $best_loadtime_query->where($db->quoteName('page_load_time') . "  = " . $db->quote($min_plt)); // Normally this can be performed under one query. However the Joomla Algorithm support for embedded selects is not documented.
            $best_loadtime_query->where($db->quoteName('simulation') . "  LIKE " . $db->quote('simulation'));
            $best_loadtime_query->where($db->quoteName('status') . "  LIKE " . $db->quote('complete') . " OR " . $db->quoteName('status') . "  LIKE " . $db->quote('test_recorded'));
            $best_loadtime_query->where($db->quoteName('group_id') . "  = " . $db->quote($parent_test->id));
            $db->setQuery($best_loadtime_query);
            $best_load_time = $db->loadObject();
            
            $stat_query = $db->getQuery(true);
            $stat_query->select('AVG( `page_load_time`)  As avg_plt');
            $stat_query->select('VARIANCE( `page_load_time`)  As variance_plt');
            $stat_query->from($db->quoteName('#__multicache_advanced_test_results'));
            $stat_query->where($db->quoteName('status') . "  LIKE " . $db->quote('complete') . " OR " . $db->quoteName('status') . "  LIKE " . $db->quote('test_recorded'));
            $stat_query->where($db->quoteName('simulation') . "  LIKE " . $db->quote('simulation'));
            $stat_query->where($db->quoteName('group_id') . "  = " . $db->quote($parent_test->id));
            $stat_query->where($db->quoteName('page_load_time') . "  != " . $db->quote(0));
            $stat_query->where($db->quoteName('page_load_time') . "  != " . $db->quote(""));
            $stat_query->where($db->quoteName('page_load_time') . "  IS NOT NULL");
            $db->setQuery($stat_query);
            $stat_result = $db->loadObject();
            
            $algo_precache_query = $db->getQuery(true);
            $algo_precache_query->select($db->quoteName('precache_factor'));
            $algo_precache_query->select('avg_load_time  As Pf_alt');
            $algo_precache_query->select('var_load_time  As Pf_vlt');
            $algo_precache_query->from($db->quoteName('#__multicache_advanced_precache_factor'));
            $algo_precache_query->where($db->quoteName('group_id') . " = " . $db->quote($parent_test->id));
            $algo_precache_query->order($db->quoteName('total_score') . ' DESC');
            $db->setQuery($algo_precache_query);
            $algo_precache_result = $db->loadObject();
            
            $algo_ccomp_query = $db->getQuery(true);
            $algo_ccomp_query->select($db->quoteName('ccomp_factor'));
            $algo_ccomp_query->select('avg_load_time  As ccf_alt');
            $algo_ccomp_query->select('var_load_time  As ccf_vlt');
            $algo_ccomp_query->from($db->quoteName('#__multicache_advanced_ccomp_factor_base'));
            $algo_ccomp_query->where($db->quoteName('group_id') . " = " . $db->quote($parent_test->id));
            $algo_ccomp_query->order($db->quoteName('total_score') . ' DESC');
            $db->setQuery($algo_ccomp_query);
            $algo_ccomp_result = $db->loadObject();
            
            $algo_loadinstruc_query = $db->getQuery(true);
            $algo_loadinstruc_query->select($db->quoteName('loadinstruc_state'));
            $algo_loadinstruc_query->select('avg_load_time  As loadinstruc_assoc_alt');
            $algo_loadinstruc_query->select('var_load_time  As loadinstruc_assoc_var');
            $algo_loadinstruc_query->from($db->quoteName('#__multicache_advanced_loadinstruction_base'));
            $algo_loadinstruc_query->where($db->quoteName('group_id') . " = " . $db->quote($parent_test->id));
            $algo_loadinstruc_query->order($db->quoteName('total_score') . ' DESC');
            $db->setQuery($algo_loadinstruc_query);
            $algo_loadinstruc_result = $db->loadObject();
            
            $updateObj = new stdClass();
            $updateObj->id = $parent_test->id;
            $updateObj->best_load_time = $best_load_time->page_load_time;
            $updateObj->blt_precache_factor = $best_load_time->precache_factor;
            $updateObj->blt_cache_compression_factor = $best_load_time->cache_compression_factor;
            $updateObj->blt_loadinstruc_state = $best_load_time->loadinstruc_state;
            $updateObj->avg_load_time = $stat_result->avg_plt;
            $updateObj->variance_on_load_time = $stat_result->variance_plt;
            $updateObj->algorithm_precache_factor = $algo_precache_result->precache_factor;
            $updateObj->algorithm_cache_compression_factor = $algo_ccomp_result->ccomp_factor;
            $updateObj->algorithm_loadinstruc_state = $algo_loadinstruc_result->loadinstruc_state;
            $updateObj->pf_assoc_alt = $algo_precache_result->Pf_alt;
            $updateObj->pf_assoc_var = $algo_precache_result->Pf_vlt;
            $updateObj->ccf_assoc_alt = $algo_ccomp_result->ccf_alt;
            $updateObj->ccf_assoc_var = $algo_ccomp_result->ccf_vlt;
            $updateObj->loadinstruc_assoc_alt = $algo_loadinstruc_result->loadinstruc_assoc_alt;
            $updateObj->loadinstruc_assoc_var = $algo_loadinstruc_result->loadinstruc_assoc_var;
            $updateObj->end_date = date('d-m-Y');
            $updateObj->end_time = microtime(true);
            $updateObj->status = 'factors_ready_to_devolve';
            $result = $db->updateObject('#__multicache_advanced_testgroups', $updateObj, 'id');
        }
        $app->close();
    
    }

    protected function updatePrecachefactorbase($parent_test)
    {

        if (empty($parent_test))
        {
            Return false;
        }
        $params = $this->_lnparams;
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('*');
        $query->from($db->quoteName('#__multicache_advanced_precache_factor'));
        $query->where($db->quoteName('group_id') . " = " . $db->quote($parent_test->id));
        $db->setQuery($query);
        $result = $db->loadObject();
        if (! empty($result))
        {
            Return false;
        }
        
        // get the precache object
        $precache_query = $db->getQuery(true);
        $precache_query->select('AVG(page_load_time) As avg_lt');
        $precache_query->select('VARIANCE(page_load_time) As variance_lt');
        $precache_query->select($db->quoteName('precache_factor'));
        $precache_query->from($db->quoteName('#__multicache_advanced_test_results'));
        $precache_query->where(' ( ' . $db->quoteName('status') . "  LIKE " . $db->quote('complete') . ' OR ' . $db->quoteName('status') . "  LIKE " . $db->quote('test_recorded') . ' ) ');
        $precache_query->where($db->quoteName('simulation') . ' LIKE ' . $db->quote('simulation'));
        $precache_query->where($db->quoteName('group_id') . "  = " . $db->quote($parent_test->id));
        $precache_query->group($db->quoteName('precache_factor'));
        $precache_query->order('avg_lt ASC');
        
        $db->setQuery($precache_query);
        
        $precache_result = $db->loadObjectlist();
        if (empty($precache_result))
        {
            Return false;
        }
        
        $variance_array = array(); // initialize variance array
        $max_variance = 0; // initialize max variance
        
        foreach ($precache_result as $obj)
        {
            
            $variance_array[] = $obj->variance_lt;
        }
        
        if (! empty($variance_array))
        {
            $max_variance = max($variance_array);
        }
        
        $target_load_time = ((int) $params->targetpageloadtime + 1) * 1000;
        
        $mode_val_query = $db->getQuery(true);
        $mode_val_query->select('COUNT( * ) AS Occurences');
        $mode_val_query->select($db->quoteName('precache_factor'));
        $mode_val_query->from($db->quoteName('#__multicache_advanced_test_results'));
        $mode_val_query->where(' ( ' . $db->quoteName('status') . "  LIKE " . $db->quote('complete') . ' OR ' . $db->quoteName('status') . "  LIKE " . $db->quote('test_recorded') . ' ) ');
        $mode_val_query->where($db->quoteName('group_id') . "  = " . $db->quote($parent_test->id));
        $mode_val_query->where($db->quoteName('page_load_time') . "  < " . $db->quote($target_load_time));
        $mode_val_query->group($db->quoteName('precache_factor'));
        $mode_val_query->order('Occurences DESC');
        
        $db->setQuery($mode_val_query);
        $mode_result_object = $db->loadObjectlist();
        $mode_array = array();
        if (! empty($mode_result_object))
        {
            foreach ($mode_result_object as $mode)
            {
                $mode_array[$mode->precache_factor] = $mode->Occurences;
            }
        }
        
        $max_mode = ! empty($mode_array) ? max($mode_array) : null;
        
        $point_system = count($precache_result);
        foreach ($precache_result as $obj)
        {
            $insertobj = new stdClass();
            $insertobj->group_id = $parent_test->id;
            $insertobj->avg_load_time = $obj->avg_lt;
            $insertobj->var_load_time = $obj->variance_lt;
            $insertobj->precache_factor = $obj->precache_factor;
            $insertobj->loadtime_score = $point_system --;
            if (! empty($max_variance) && $max_variance != 0)
            {
                $insertobj->loadvar_score = (($max_variance - $obj->variance_lt) * 10 / $max_variance); // cross check whether casting as int looses value
            }
            $insertobj->statmode = $mode_array[$obj->precache_factor];
            $insertobj->statmode_score = isset($max_mode) ? $mode_array[$obj->precache_factor] * 10 / $max_mode : null;
            $insertobj->total_score = $insertobj->loadtime_score * $params->algorithmavgloadtimeweight + $insertobj->loadvar_score * $params->algorithmvarianceweight + $insertobj->statmode_score * algorithmmodemaxbelowtimeweight;
            $result = $db->insertObject('#__multicache_advanced_precache_factor', $insertobj);
        }
    
    }

    protected function updateCcompfactorbase($parent_test)
    {

        $params = $this->_lnparams;
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('*');
        $query->from($db->quoteName('#__multicache_advanced_ccomp_factor_base'));
        $query->where($db->quoteName('group_id') . " = " . $db->quote($parent_test->id));
        $db->setQuery($query);
        $result = $db->loadObject();
        if (! empty($result))
        {
            Return false;
        }
        
        $ccomp_query = $db->getQuery(true);
        $ccomp_query->select('AVG(page_load_time) As avg_lt');
        $ccomp_query->select('VARIANCE(page_load_time) As variance_lt');
        $ccomp_query->select($db->quoteName('cache_compression_factor'));
        $ccomp_query->from($db->quoteName('#__multicache_advanced_test_results'));
        $ccomp_query->where(' ( ' . $db->quoteName('status') . "  LIKE " . $db->quote('complete') . ' OR ' . $db->quoteName('status') . "  LIKE " . $db->quote('test_recorded') . ' ) ');
        $ccomp_query->where($db->quoteName('simulation') . ' LIKE ' . $db->quote('simulation'));
        $ccomp_query->where($db->quoteName('group_id') . "  = " . $db->quote($parent_test->id));
        $ccomp_query->group($db->quoteName('cache_compression_factor'));
        $ccomp_query->order('avg_lt ASC');
        
        $db->setQuery($ccomp_query);
        
        $ccomp_result = $db->loadObjectlist();
        if (empty($ccomp_result))
        {
            Return false;
        }
        
        $variance_array = array();
        $max_variance = 0;
        foreach ($ccomp_result as $obj)
        {
            
            $variance_array[] = $obj->variance_lt;
        }
        if (! empty($variance_array))
        {
            $max_variance = max($variance_array);
        }
        
        $target_load_time = ((int) $params->targetpageloadtime + 1) * 1000;
        
        $mode_val_query = $db->getQuery(true);
        $mode_val_query->select('COUNT( * ) AS Occurences');
        $mode_val_query->select($db->quoteName('cache_compression_factor'));
        $mode_val_query->from($db->quoteName('#__multicache_advanced_test_results'));
        $mode_val_query->where(' ( ' . $db->quoteName('status') . "  LIKE " . $db->quote('complete') . ' OR ' . $db->quoteName('status') . "  LIKE " . $db->quote('test_recorded') . ' ) ');
        $mode_val_query->where($db->quoteName('group_id') . "  = " . $db->quote($parent_test->id));
        $mode_val_query->where($db->quoteName('page_load_time') . "  < " . $db->quote($target_load_time));
        $mode_val_query->group($db->quoteName('cache_compression_factor'));
        $mode_val_query->order('Occurences DESC');
        
        $db->setQuery($mode_val_query);
        $mode_result_object = $db->loadObjectlist();
        $mode_array = array();
        if (! empty($mode_result_object))
        {
            foreach ($mode_result_object as $mode)
            {
                $mode_array[$mode->cache_compression_factor] = $mode->Occurences;
            }
        }
        
        $max_mode = ! empty($mode_array) ? max($mode_array) : null;
        // end of mode value algorithm
        $point_system = count($ccomp_result);
        foreach ($ccomp_result as $obj)
        {
            $insertobj = new stdClass();
            $insertobj->group_id = $parent_test->id;
            $insertobj->avg_load_time = $obj->avg_lt;
            $insertobj->var_load_time = $obj->variance_lt;
            $insertobj->ccomp_factor = $obj->cache_compression_factor;
            $insertobj->loadtime_score = $point_system --;
            
            if (! empty($max_variance) && $max_variance != 0)
            {
                $insertobj->loadvar_score = (($max_variance - $obj->variance_lt) * 10 / $max_variance); // cross check whether casting as int looses value
            }
            $insertobj->statmode = $mode_array[$obj->cache_compression_factor];
            if ($max_mode)
            {
                $insertobj->statmode_score = $mode_array[$obj->cache_compression_factor] * 10 / $max_mode;
            }
            $insertobj->total_score = $insertobj->loadtime_score * $params->algorithmavgloadtimeweight + $insertobj->loadvar_score * $params->algorithmvarianceweight + $insertobj->statmode_score * algorithmmodemaxbelowtimeweight;
            $result = $db->insertObject('#__multicache_advanced_ccomp_factor_base', $insertobj);
        }
    
    }

    protected function updateLoadInstructionbase($parent_test)
    {

        if (empty($parent_test) || $this->_test_group->advanced != 'advanced')
        {
            Return false;
        }
        $params = $this->_lnparams;
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('*');
        $query->from($db->quoteName('#__multicache_advanced_loadinstruction_base'));
        $query->where($db->quoteName('group_id') . " = " . $db->quote($parent_test->id));
        $db->setQuery($query);
        $result = $db->loadObject();
        if (! empty($result))
        {
            Return false;
        }
        
        $linstruc_query = $db->getQuery(true);
        $linstruc_query->select('AVG(page_load_time) As avg_lt');
        $linstruc_query->select('VARIANCE(page_load_time) As variance_lt');
        $linstruc_query->select($db->quoteName('loadinstruc_state'));
        
        $linstruc_query->from($db->quoteName('#__multicache_advanced_test_results'));
        $linstruc_query->where(' ( ' . $db->quoteName('status') . "  LIKE " . $db->quote('complete') . ' OR ' . $db->quoteName('status') . "  LIKE " . $db->quote('test_recorded') . ' ) ');
        $linstruc_query->where($db->quoteName('simulation') . ' LIKE ' . $db->quote('simulation'));
        $linstruc_query->where($db->quoteName('group_id') . "  = " . $db->quote($parent_test->id));
        $linstruc_query->group($db->quoteName('loadinstruc_state'));
        $linstruc_query->order('avg_lt ASC');
        
        $db->setQuery($linstruc_query);
        
        $linstruc_result = $db->loadObjectlist();
        if (empty($linstruc_result))
        {
            Return false;
        }
        
        $variance_array = array();
        $max_variance = 0;
        foreach ($linstruc_result as $obj)
        {
            
            $variance_array[] = $obj->variance_lt;
        }
        
        if (! empty($variance_array))
        {
            $max_variance = max($variance_array);
        }
        
        $target_load_time = ((int) $params->targetpageloadtime + 1) * 1000;
        
        $mode_val_query = $db->getQuery(true);
        $mode_val_query->select('COUNT( * ) AS Occurences');
        $mode_val_query->select($db->quoteName('loadinstruc_state'));
        $mode_val_query->from($db->quoteName('#__multicache_advanced_test_results'));
        $mode_val_query->where(' ( ' . $db->quoteName('status') . "  LIKE " . $db->quote('complete') . ' OR ' . $db->quoteName('status') . "  LIKE " . $db->quote('test_recorded') . ' ) ');
        $mode_val_query->where($db->quoteName('group_id') . "  = " . $db->quote($parent_test->id));
        $mode_val_query->where($db->quoteName('page_load_time') . "  < " . $db->quote($target_load_time));
        $mode_val_query->group($db->quoteName('loadinstruc_state'));
        $mode_val_query->order('Occurences DESC');
        $db->setQuery($mode_val_query);
        $mode_result_object = $db->loadObjectlist();
        $mode_array = array();
        if (! empty($mode_result_object))
        {
            foreach ($mode_result_object as $mode)
            {
                $mode_array[$mode->loadinstruc_state] = $mode->Occurences;
            }
        }
        
        $max_mode = ! empty($mode_array) ? max($mode_array) : null;
        
        $point_system = count($linstruc_result);
        foreach ($linstruc_result as $obj)
        {
            $insertobj = new stdClass();
            $insertobj->group_id = $parent_test->id;
            $insertobj->avg_load_time = $obj->avg_lt;
            $insertobj->var_load_time = $obj->variance_lt;
            $insertobj->loadinstruc_state = $obj->loadinstruc_state;
            $insertobj->loadtime_score = $point_system --;
            if (! empty($max_variance) && $max_variance != 0)
            {
                $insertobj->loadvar_score = (($max_variance - $obj->variance_lt) * 10 / $max_variance); // cross check whether casting as int looses value
            }
            $insertobj->statmode = $mode_array[$obj->loadinstruc_state];
            // transported correction from WP [$obj->loadinstruc_state] was pointing to precache
            $insertobj->statmode_score = isset($max_mode) ? $mode_array[$obj->loadinstruc_state] * 10 / $max_mode : null;
            $insertobj->total_score = $insertobj->loadtime_score * $params->algorithmavgloadtimeweight + $insertobj->loadvar_score * $params->algorithmvarianceweight + $insertobj->statmode_score * algorithmmodemaxbelowtimeweight;
            $result = $db->insertObject('#__multicache_advanced_loadinstruction_base', $insertobj);
        }
    
    }

    protected function devolveRepeat()
    {

        $db = JFactory::getDbo();
        $updateObj = new stdClass();
        $updateObj->id = $this->_test_group->id;
        $updateObj->status = 'factors_ready_to_devolve';
        $result = $db->updateObject('#__multicache_advanced_testgroups', $updateObj, 'id');
    
    }

    protected function devolveNone()
    {

        $db = JFactory::getDbo();
        $updateObj = new stdClass();
        $updateObj->id = $this->_test_group->id;
        $updateObj->status = 'factors_devolved_none';
        $result = $db->updateObject('#__multicache_advanced_testgroups', $updateObj, 'id');
    
    }

}

?>