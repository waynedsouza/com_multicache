<?php
/**
 *
 * @version 1.0.1.5
 * @package com_multicache
 * @copyright Copyright (C) Multicache.org 2015. All rights reserved.
 * @licenseEULA see LICENSE.txt - http://multicache.org/terms-conditions/end-user-license-agreement.html
 * @author Wayne DSouza <consulting@OnlineMarketingConsultants.in> - http://OnlineMarketingConsultants.in
 */

// No direct access.
defined('_JEXEC') or die();

jimport('joomla.application.component.modeladmin');
JLoader::register('JCacheStoragetemp', JPATH_ROOT . '/administrator/components/com_multicache/lib/storagetemp.php');
use Joomla\Registry\Registry;
require_once JPATH_COMPONENT . '/helpers/multicache.php';
JLoader::register('MulticachePageScripts', JPATH_ROOT . '/administrator/components/com_multicache/lib/pagescripts.php');
JLoader::register('MulticachePageCss', JPATH_ROOT . '/administrator/components/com_multicache/lib/pagecss.php');
JLoader::register('Loadinstruction', JPATH_ROOT . '/components/com_multicache/lib/loadinstruction.php');
JLoader::register('MulticacheCSSOptimize', JPATH_ROOT . '/administrator/components/com_multicache/lib/compression_libs/multicachecssoptimize.php');
JLoader::register('MulticacheJSOptimize', JPATH_ROOT . '/administrator/components/com_multicache/lib/compression_libs/multicachejsoptimize.php');
JLoader::register('JSMin', JPATH_ROOT . '/administrator/components/com_multicache/lib/compression_libs/JSmin.php');
JLoader::import('simcontrol', JPATH_ROOT . '/components/com_multicache/models');

/**
 * Multicache model.
 */
class MulticacheModelConfig extends JModelAdmin
{

    /**
     *
     * @var string prefix to use with controller messages.
     * @since 1.6
     */
    protected $text_prefix = 'COM_MULTICACHE_CONFIG';

    protected $scrape_url = '';

    protected static $_scraped_page_content = '';

    protected static $_css_scraped_page_content = '';

    protected static $_duplicates = null;

    protected static $_duplicates_css = null;

    protected static $_signature_hash = null;

    protected static $_dontmovesignature_hash = null;

    protected static $_dontmoveurls = null;

    protected static $_dontmove_items = null;
    // prime comparison and removal array in JSStrategy. Contains all signatures of scripts that need to be removed and reloaded. If Ignore is introduced it will have to unset this array as well as the jsarray
    protected static $_allow_multiple_orphaned = null;

    protected static $_signature_hash_css = null;

    protected static $_social_segment = null;

    protected static $_advertisement_segment = null;

    protected static $_async_segment = null;

    protected static $_delayable_segment = null;

    protected static $_delayable_segment_css = null;

    protected static $_cdn_segment = null;

    protected static $_cdn_segment_css = null;

    protected static $_groups = null;

    protected static $_groups_css = null;
    // stores group name as key and url as value. The url points to the loaction the group script is located in
    protected static $_groups_loaded = null;
    
    //ver1.0.1.2 
    protected static $_promises = null;

    protected static $_groups_loaded_css = null;
    // a group needs to be loaded only once. This array stores as Keys the groups that are loaded in the loadsection iteration
    protected static $_principle_jquery_scope = null;

    protected static $_mediaVersion = null;

    protected static $_loadsections = null;

    protected static $_unset_hash = null;

    protected static $_loadsections_css = null;

    protected static $_jscomments = null;

    protected static $_css_comments = null;

    protected static $_excluded_components = null;

    protected static $_temp_group = null;

    protected static $_excluded_components_css = null;

    protected static $_excluded_components_img = null;

    protected static $_delayed_noscript = '';

    protected static $_atimports_prop = null;

    const DOUBLE_QUOTE_STRING = '"(?>(?:\\\\.)?[^\\\\"]*+)+?(?:"|(?=$))';
    // regex for single quoted string
    const SINGLE_QUOTE_STRING = "'(?>(?:\\\\.)?[^\\\\']*+)+?(?:'|(?=$))";
    // regex for block comments
    const BLOCK_COMMENTS = '/\*(?>[^/\*]++|//|\*(?!/)|(?<!\*)/)*+\*/';
    // regex for line comments
    const LINE_COMMENTS = '//[^\r\n]*+';

    const URI = '(?<=url)\([^)]*+\)';

    /**
     * Returns a reference to the a Table object, always creating it.
     *
     * @param
     *        type The table type to instantiate
     * @param
     *        string A prefix for the table class name. Optional.
     * @param
     *        array Configuration array for model. Optional.
     * @return JTable database object
     * @since 1.6
     */
    public function getTable($type = 'Config', $prefix = 'MulticacheTable', $config = array())
    {

        $config_table = JTable::getInstance($type, $prefix, $config);
        
        return $config_table;
    
    }

    public function getParam($id = null)
    {

        static $param = array();
        if (! isset($id) && isset($param['result']))
        {
            Return $param['result'];
        }
        if (isset($id) && isset($param[$id]))
        {
            Return $param[$id];
        }
        $db = JFactory::getDbo();
        $query = $db->getQuery('true');
        $query->select('*');
        $query->from($db->quoteName('#__multicache_config'));
        $db->setQuery($query);
        $result = $db->loadObjectlist();
        if (empty($result))
        {
            Return false;
        }
        $param['result'] = $result;
        if (isset($id))
        {
            $param[$id] = $result[$id];
            return $result[$id];
        }
        return $result;
    
    }

    public function getConfigSetUp()
    {
        // php-issue if (!empty($this->getTable()->load(1)))
        $mul_conf = $this->getTable()->load(1);
        if (! empty($mul_conf))
        {
            Return true;
        }
        $db = JFactory::getDBO();
        $insertObject = new stdClass();
        $insertObject->id = 1;
        
        $db->insertObject('#__multicache_config', $insertObject);
    
    }

    /**
     * Method to get the record form.
     *
     * @param array $data
     *        array of data for the form to interogate.
     * @param boolean $loadData
     *        the form is to load its own data (default case), false if not.
     * @return JForm JForm object on success, false on failure
     * @since 1.6
     */
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
        $form = $this->assigndefaultvalues($form);
        /*
         * deprecated
         * if ($form->getValue(js_switch))
         * {
         * $form = $this->initialiseJsdefaults($form);
         * }
         */
        
        $this->scrape_url = $form->getValue('default_scrape_url'); // lets [preload this value to reduce load
        return $form;
    
    }

    /**
     * Method to get a single record.
     *
     * @param
     *        integer The id of the primary key.
     *        
     * @return mixed on success, false on failure.
     * @since 1.6
     */
    public function getItem($pk = null)
    {

        if ($item = parent::getItem($pk))
        {
            
            // Do any procesing on fields here if needed
        }
        
        return $item;
    
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
        $advanced_cache_enabled = (false !== $this->getParam(0)) ? $this->getParam(0)->indexhack : false;
        if ($advanced_cache_enabled)
        {
            $isenabled = JPluginHelper::isEnabled("user", "multicache");
            if (! $isenabled)
            {
                $app->enqueueMessage(JText::_('COM_MULTICACHE_CONFIG_SYSTEM_USER_MULTICACHE_PLUGIN_NOT_ENABLED_MESSAGE'), 'warning');
            }
        }
        MulticacheHelper::checkComponentParams();
    
    }

    public function getfastCacheFailStatus()
    {

        $config = JFactory::getConfig();
        $storage = $config->get('cache_handler');
        
        if ($storage == 'fastcache' && class_exists('JCacheStorageFastcache'))
        {
            
            $status = JCacheStorageFastcache::isSupported();
            
            if ($status === false)
            {
                $app = JFactory::getApplication();
                $app->enqueueMessage(JText::_('COM_MULTICACHE_FASTCACHE_NOT_SUPPORTED'), 'warning');
                Return true;
            }
        }
        
        Return false;
    
    }

    public function getTotalScripts()
    {

        if (! $this->getItem()->js_switch)
        {
            Return false;
        }
        
        if (class_exists('MulticachePageScripts') && property_exists('MulticachePageScripts', 'original_script_array'))
        {
            
            Return count(MulticachePageScripts::$original_script_array);
        }
        Return false;
    
    }

    public function getCssTotalScripts()
    {

        if (! $this->getItem()->css_switch)
        {
            Return false;
        }
        
        if (class_exists('MulticachePageCss') && property_exists('MulticachePageCss', 'original_css_array'))
        {
            
            Return count(MulticachePageCss::$original_css_array);
        }
        Return false;
    
    }

    public function getUniqueScriptAsArray()
    {

        if (! $this->getItem()->js_switch)
        {
            Return false;
        }
        
        if (class_exists('MulticachePageScripts'))
        {
            Return MulticacheHelper::getUniqueScriptAsArray();
        }
        Return false;
    
    }

    public function getUniqueScripts()
    {

        if (! $this->getItem()->js_switch)
        {
            Return false;
        }
        
        if (class_exists('MulticachePageScripts') && property_exists('MulticachePageScripts', 'original_script_array'))
        {
            Return MulticacheHelper::getUniqueScripts(MulticachePageScripts::$original_script_array);
        }
        Return false;
    
    }

    public function getCssUniqueScripts()
    {

        if (! $this->getItem()->css_switch)
        {
            Return false;
        }
        
        if (class_exists('MulticachePageCss') && property_exists('MulticachePageCss', 'original_css_array'))
        {
            Return MulticacheHelper::getUniqueScripts(MulticachePageCss::$original_css_array);
        }
        Return false;
    
    }

    /* get the template page */
    public function getTemplatePage()
    {

        if (! $this->getItem()->js_switch)
        {
            Return false;
        }
        
        $pagescripts = $this->getRelevantPageScript();
        $templatepage = MulticacheHelper::getPageScriptObject($pagescripts);
        return $templatepage;
    
    }

    public function getCssPage()
    {

        if (! $this->getItem()->css_switch)
        {
            Return false;
        }
        
        $pagecss = $this->getRelevantPageCss();
        
        $csspage = MulticacheHelper::getPageCssObject($pagecss);
        return $csspage;
    
    }

    protected function getRecentValue($key)
    {

        $app = JFactory::getApplication();
        $jinput = $app->input->post->__call('getfilter', array(
            0 => 'jform',
            1 => null
        ));
        if (! empty($jinput[$key]))
        {
            $value = $jinput[$key];
        }
        else
        {
            $jinput = $app->input->__call('getfilter', array(
                0 => 'jform',
                1 => null
            ));
            $value = ! empty($jinput[$key]) ? $jinput[$key] : null;
        }
        Return $value;
    
    }

    public function makeTemplatePage()
    {

        $app = JFactory::getApplication();
        $jinput = $app->input->post->__call('getfilter', array(
            0 => 'jform',
            1 => null
        ));
        if (! empty($jinput["default_scrape_url"]))
        {
            $this->scrape_url = $jinput["default_scrape_url"];
        }
        else
        {
            $jinput = $app->input->__call('getfilter', array(
                0 => 'jform',
                1 => null
            ));
            $this->scrape_url = ! empty($jinput["default_scrape_url"]) ? $jinput["default_scrape_url"] : null;
        }
        if ($this->getParam(0)->default_scrape_url != $this->scrape_url)
        {
            // lets set a session var
            $session = JFactory::getSession();
            $sess_dsu = serialize($this->scrape_url);
            $session->set('multicache_scrape_url_default', $sess_dsu);
        }
        
        if (empty($this->scrape_url))
        {
            $this->scrape_url = $this->getParam(0)->default_scrape_url;
        }
        
        if (empty($this->scrape_url))
        {
            
            $app->enqueueMessage(JText::_('COM_MULTICACHE_ERROR_SCRAPE_URL_NOTSET'), 'warning');
            Return false;
        }
        $uri = JURI::getInstance($this->scrape_url);
        $uri->setVar('multicachetask', MulticacheHelper::getMediaFormat());
        
        $this->scraped_page = MulticacheHelper::get_web_page($uri->toString());
        if ($this->scraped_page["http_code"] == 200)
        {
        }
        else
        {
            $app->enqueueMessage(JText::_('COM_MULTICACHE_ERROR_SCRAPE_URL_NOTRETREIVED') . $this->scraped_page["http_code"], 'notice');
            Return false;
        }
        if (strstr($this->scraped_page["content"], 'Loaded by MulticachePlugin') || strstr($this->scraped_page["content"], '
/administrator/components/com_multicache/'))
        {
            $app->enqueueMessage(JText::_('COM_MULTICACHE_ERROR_SCRAPE_PAGE_LOOP'), 'error');
            Return false;
        }
        self::$_scraped_page_content = $this->scraped_page["content"];
        $this->jsArray = $this->getAllScripts();
        $this->jsArray = $this->setSocialIndicators($this->jsArray);
        $this->jsArray = $this->setAdvertisementIndicators($this->jsArray);
        
        $success = MulticacheHelper::storePageScripts($this->jsArray);
        
        Return $success;
    
    }
    // start
    public function makeCssPage()
    {

        $app = JFactory::getApplication();
        $jinput = $app->input->post->__call('getfilter', array(
            0 => 'jform',
            1 => null
        ));
        if (! empty($jinput["css_scrape_url"]))
        {
            $this->css_scrape_url = $jinput["css_scrape_url"];
        }
        else
        {
            $jinput = $app->input->__call('getfilter', array(
                0 => 'jform',
                1 => null
            ));
            $this->css_scrape_url = ! empty($jinput["css_scrape_url"]) ? $jinput["css_scrape_url"] : null;
        }
        if ($this->getParam(0)->css_scrape_url != $this->css_scrape_url)
        {
            // lets set a session var
            $session = JFactory::getSession();
            $sess_dcsu = serialize($this->css_scrape_url);
            $session->set('multicache_css_url_default', $sess_dcsu);
        }
        
        if (empty($this->css_scrape_url))
        {
            $this->css_scrape_url = $this->getParam(0)->css_scrape_url;
        }
        
        if (empty($this->css_scrape_url))
        {
            
            $app->enqueueMessage(JText::_('COM_MULTICACHE_ERROR_CSS_URL_NOTSET'), 'warning');
            Return false;
        }
        $uri = JURI::getInstance($this->css_scrape_url);
        $uri->setVar('multicachecsstask', MulticacheHelper::getMediaFormat());
        
        $this->css_scraped_page = MulticacheHelper::get_web_page($uri->toString());
        if ($this->css_scraped_page["http_code"] == 200)
        {
        }
        else
        {
            $app->enqueueMessage(JText::_('COM_MULTICACHE_ERROR_CSS_SCRAPE_URL_NOTRETREIVED'), 'notice');
            Return false;
        }
        
        if (strstr($this->css_scraped_page["content"], 'Loaded by MulticachePlugin') || strstr($this->css_scraped_page["content"], '
/administrator/components/com_multicache/'))
        {
            $app->enqueueMessage(JText::_('COM_MULTICACHE_ERROR_CSS_SCRAPE_PAGE_LOOP'), 'error');
            Return false;
        }
        self::$_css_scraped_page_content = $this->css_scraped_page["content"];
        $this->cssArray = $this->getAllCss();
        
        // $this->cssArray = $this->setSocialIndicators($this->jsArray);
        // $this->jsArray = $this->setAdvertisementIndicators($this->jsArray);
        
        $success = MulticacheHelper::storePageCss($this->cssArray);
        
        Return $success;
    
    }
    // end
    public function checkConfigParams()
    {

        $app = JFactory::getApplication();
        $jfinput = $app->input->post->__call('getfilter', array(
            0 => 'jform',
            1 => null
        ));
        
        if (! isset($jfinput))
        {
            $jfinput = $app->input->__call('getfilter', array(
                0 => 'jform',
                1 => null
            ));
        }
        if (empty($jfinput))
        {
            Return false;
        }
        if ($jfinput["gzip_factor_min"] > 1 || $jfinput["gzip_factor_max"] > 1 || $jfinput["gzip_factor_step"] > 1 || $jfinput["gzip_factor_default"] > 1)
        {
            $app->enqueueMessage(JText::_('COM_MULTICACHE_CONFIG_CACHE_COMPRESSION_OUTOFRANGE'), 'error');
            $app->redirect('index.php?option=com_multicache&view=config&layout=edit&id=1#page-optimisation');
        }
        if ($jfinput["gzip_factor_min"] > $jfinput["gzip_factor_max"])
        {
            $app->enqueueMessage(JText::_('COM_MULTICACHE_CONFIG_CACHE_COMPRESSION_MAXLESSTHANMIN'), 'error');
            $app->redirect('index.php?option=com_multicache&view=config&layout=edit&id=1#page-optimisation');
        }
        if ($jfinput["gzip_factor_step"] == 0)
        {
            $app->enqueueMessage(JText::_('COM_MULTICACHE_CONFIG_CACHE_COMPRESSION_STEPZERO'), 'error');
            $app->redirect('index.php?option=com_multicache&view=config&layout=edit&id=1#page-optimisation');
        }
        else
        {
            $seq = ($jfinput["gzip_factor_max"] - $jfinput["gzip_factor_min"]) / $jfinput["gzip_factor_step"];
            if ($seq < 1 && $seq > 0)
            {
                $app->enqueueMessage(JText::_('COM_MULTICACHE_CONFIG_CACHE_COMPRESSION_SETTING_ERROR'), 'error');
                $app->redirect('index.php?option=com_multicache&view=config&layout=edit&id=1#page-optimisation');
            }
            if ((1 + $seq) > 100)
            {
                $app->enqueueMessage(JText::_('COM_MULTICACHE_CONFIG_CACHE_COMPRESSION_SEQSETTING_OUTOFRANGE'), 'error');
                $app->redirect('index.php?option=com_multicache&view=config&layout=edit&id=1#page-optimisation');
            }
        }
        if ($jfinput["precache_factor_max"] < $jfinput["precache_factor_min"])
        {
            $app->enqueueMessage(JText::_('COM_MULTICACHE_CONFIG_PRECACHE_MAXLESSTHANMIN'), 'error');
            $app->redirect('index.php?option=com_multicache&view=config&layout=edit&id=1#page-optimisation');
        }
        $weight = $jfinput["algorithmavgloadtimeweight"] + $jfinput["algorithmmodemaxbelowtimeweight"] + $jfinput["algorithmvarianceweight"];
        if ($weight > 1)
        {
            $app->enqueueMessage(JText::_('COM_MULTICACHE_CONFIG_PRECACHE_ALGORTIHM_SETTINGS_WEIGHTSGTONE'), 'error');
            $app->redirect('index.php?option=com_multicache&view=config&layout=edit&id=1#page-partb');
        }
        
        if (! empty($jfinput["googleviewid"]) && substr($jfinput["googleviewid"], 0, 2) == "ua")
        {
            
            $message = JText::_('COM_MULTICACHE_GOOGLE_CREDENTIAL_ERROR_ACCOUNT_VIEW_ID');
            JError::raiseWarning(500, $message);
            $app->redirect('index.php?option=com_multicache&view=config&layout=edit&id=1');
        }
        
        if (! empty($jfinput["gtmetrix_testing"]) && ! empty($jfinput["gtmetrix_allow_simulation"]) && $jfinput["simulation_advanced"])
        {
            if (empty($jfinput["gtmetrix_test_url"]))
            {
                $app->enqueueMessage(JText::_('COM_MULTICACHE_CONFIG_GTMETRIX_TESTURL_IS_EMPTY'), 'error');
                $app->redirect('index.php?option=com_multicache&view=config&layout=edit&id=1#page-optimisation');
            }
            // check 1
            $excludeurls = $jfinput["jst_urlinclude"];
            if (! empty($jfinput["js_tweaker_url_include_exclude"]) && ! empty($excludeurls))
            {
                
                $excludeurls = preg_split('/[\s\n,]+/', $excludeurls);
                if (($jfinput["js_tweaker_url_include_exclude"] == 1 && ! in_array($jfinput["gtmetrix_test_url"], $excludeurls)) || ($jfinput["js_tweaker_url_include_exclude"] == 2 && in_array($jfinput["gtmetrix_test_url"], $excludeurls)))
                {
                    $app->enqueueMessage(JText::_('COM_MULTICACHE_CONFIG_GTMETRIX_TESTURL_IS_EXCLUDED'), 'error');
                    $app->redirect('index.php?option=com_multicache&view=config&layout=edit&id=1#page-js-tweaks');
                }
            }
            
            $queryparams = $jfinput["jst_query_param"];
            if (! empty($jfinput["jst_query_include_exclude"]) && ! empty($queryparams))
            {
                $gtm_testurl_qparams = JURI::getInstance($jfinput["gtmetrix_test_url"])->getQuery(true);
                $queryparams = preg_split('/[\s\n,]+/', $queryparams);
                $query_set = false;
                if (! empty($queryparams))
                {
                    $q_val = array();
                    foreach ($queryparams as $query)
                    {
                        $split = explode("=", $query);
                        $key = $split[0];
                        $value = isset($split[1]) ? $split[1] : 1;
                        $q_val[$key][$value] = 1;
                    }
                }
                if (! empty($gtm_testurl_qparams))
                {
                    foreach ($gtm_testurl_qparams as $key => $value)
                    {
                        if (isset($q_val[$key][$value]) || (isset($q_val[$key]) && $q_val[$key][true] == 1))
                        {
                            $query_set = true;
                            break;
                        }
                    }
                }
                
                if (($jfinput["jst_query_include_exclude"] == 1 && ! $query_set) || ($jfinput["jst_query_include_exclude"] == 2 && $query_set))
                {
                    $app->enqueueMessage(JText::_('COM_MULTICACHE_CONFIG_GTMETRIX_TESTURL_IS_EXCLUDED_QUERYPARAM'), 'error');
                    $app->redirect('index.php?option=com_multicache&view=config&layout=edit&id=1#page-js-tweaks');
                }
            }
        }
    
    }

    public function performFactoryReset()
    {

        $db = JFactory::getDBO();
        $updateobj = new stdClass();
        $updateobj->id = 1;
        $updateobj->cache_handler = 'fastcache';
        $updateobj->cachetime = 15;
        $updateobj->multicache_persist = true;
        $updateobj->multicache_compress = true;
        $updateobj->multicache_server_host = 'localhost';
        $updateobj->multicache_server_port = '11211';
        $updateobj->gtmetrix_testing = 0;
        $updateobj->gtmetrix_api_budget = 20;
        $updateobj->gtmetrix_email = '';
        $updateobj->gtmetrix_token = '';
        $updateobj->gtmetrix_adblock = true;
        $updateobj->gtmetrix_test_url = JURI::root();
        $updateobj->gtmetrix_allow_simulation = 0;
        $updateobj->simulation_advanced = 0;
        $updateobj->jssimulation_parse = 1;
        $updateobj->gtmetrix_cycles = 1;
        $updateobj->precache_factor_min = 0;
        $updateobj->precache_factor_max = 9;
        $updateobj->precache_factor_default = 2;
        $updateobj->gzip_factor_min = 0;
        $updateobj->gzip_factor_max = 1;
        $updateobj->gzip_factor_step = 0.1;
        $updateobj->gzip_factor_default = 0.22;
        $updateobj->googleclientid = '';
        $updateobj->googleclientsecret = '';
        $updateobj->googleviewid = '';
        $updateobj->googlestartdate = '';
        $updateobj->googleenddate = '';
        $updateobj->googlenumberurlscache = 200;
        $updateobj->multicachedistribution = 3;
        $updateobj->additionalpagecacheurls = '';
        $updateobj->force_locking_off = 0;
        $updateobj->indexhack = 0;
        $updateobj->conduit_switch = 1;
        $updateobj->targetpageloadtime = 3;
        $updateobj->algorithmavgloadtimeweight = 0.4;
        $updateobj->algorithmmodemaxbelowtimeweight = 0.4;
        $updateobj->algorithmvarianceweight = 0.2;
        $updateobj->urlfilters = 1;
        $updateobj->frequency_distribution = 1;
        $updateobj->natlogdist = 1;
        $updateobj->deployment_method = 3;
        $updateobj->cartsessionvariables = '["vmcart,vm"]';
        $updateobj->cartdifferentiators = '["virtuemart_currency_id","com_virtuemart"]';
        $updateobj->cartmode = 0;
        $updateobj->cartmodeurlinclude = '';
        $updateobj->js_switch = 0;
        $updateobj->default_scrape_url = JURI::root();
        $updateobj->social_script_identifiers = '["FB.init","assets.pinterest.com","platform.twitter.com","plusone.js"]';
        $updateobj->advertisement_script_identifiers = '';
        $updateobj->pre_head_stub_identifiers = '["<head>"]';
        $updateobj->head_stub_identifiers = '["<\\/head>"]';
        $updateobj->body_stub_identifiers = '["<body>"]';
        $updateobj->footer_stub_identifiers = '["<\\/body>"]';
        $updateobj->principle_jquery_scope = 0;
        $updateobj->principle_jquery_scope_other = '';
        $updateobj->dedupe_scripts = 1;
        $updateobj->defer_social = 1;
        $updateobj->defer_advertisement = 1;
        $updateobj->defer_async = 1;
        $updateobj->maintain_preceedence = 1;
        $updateobj->minimize_roundtrips = 1;
        $updateobj->js_comments = 1;
        $updateobj->debug_mode = 0;
        $updateobj->advanced_simulation_lock = 1;
        $updateobj->js_tweaker_url_include_exclude = 0;
        $updateobj->jst_urlinclude = '';
        $updateobj->jst_query_include_exclude = 0;
        $updateobj->jst_query_param = '';
        $updateobj->orphaned_scripts = 4;
        $updateobj->orphaned_scripts = 4;
        $updateobj->imgexcluded_components = '';
        $updateobj->params = 'a:1:{s:22:"positional_dontmovesrc";a:1:{i:0;s:48:"pagead2.googlesyndication.com/pagead/show_ads.js";}}';
        
        $result = $db->updateObject('#__multicache_config', $updateobj, 'id');
    
    }

    /*
     * protected function initialiseJsdefaults($fobj)
     * {
     *
     * if (empty($fobj->getValue('default_scrape_url')))
     * {
     *
     * $base_url = strtolower(JURI::root());
     * $fobj->setValue('default_scrape_url', '', $base_url);
     * $updateobj = new stdClass();
     * $updateobj->default_scrape_url = $base_url;
     * }
     *
     * //always last to update db
     * $temparr = (array) $updateobj;
     * if (!empty($temparr))
     * {
     *
     * $db = JFactory::getDBO();
     * $db->getQuery(true);
     * $updateobj->id = 1;
     * $updateobj->targetpageloadtime = 3;
     * $updateobj->algorithmmodemaxbelowtimeweight = 0.4;
     * $updateobj->algorithmvarianceweight = 0.2;
     * $updateobj->algorithmavgloadtimeweight = 0.4;
     * $result = $db->updateObject('#__multicache_config', $updateobj, 'id');
     * }
     * Return $fobj;
     * }
     */
    /*
     * Method to align com_multicache from config. Method config -> com_multicache
     * differential arguement used for multicache distribution.
     */
    protected function assigndefaultvalues($fobj)
    {

        $app = JFactory::getApplication();
        $session = JFactory::getSession();
        $config = JFactory::getConfig();
        $updateobj = new stdClass();
        $params = ! empty($this->getParam(0)->params) ? unserialize($this->getParam(0)->params) : null;
        if (! empty($params['positional_dontmovesrc']))
        {
            $positional_urls = implode("\n", $params['positional_dontmovesrc']);
            $fobj->setValue('positional_dontmovesrc', '', $positional_urls);
        }
        if (! empty($params['allow_multiple_orphaned']))
        {
            $allow_multiple_orphaned = implode("\n", $params['allow_multiple_orphaned']);
            $fobj->setValue('allow_multiple_orphaned', '', $allow_multiple_orphaned);
        }
        if (! empty($params['resultant_async']))
        {
            $resultant_async = $params['resultant_async'];
            $fobj->setValue('resultant_async', '', $resultant_async);
        }
        else {
        	$fobj->setValue('resultant_async', '', 0);
        }
        if (! empty($params['resultant_defer']))
        {
            $resultant_defer = $params['resultant_defer'];
            $fobj->setValue('resultant_defer', '', $resultant_defer);
        }
        else {
        	$fobj->setValue('resultant_defer', '', 0);
        }
        //ver1.0.1.1 $css_groupsasync
        if (! empty($params['css_groupsasync']))
        {
        	$css_groupsasync = $params['css_groupsasync'];
        	$fobj->setValue('css_groupsasync', '', $css_groupsasync);
        }
        else {
        	$fobj->setValue('css_groupsasync', '', 0);
        }
        //ver1.0.1.2 onload and exclude groups async
        if (! empty($params['groups_async_exclude']))
        {
        	$css_groupsasync_exclude = $params['groups_async_exclude'];
        }
        
         if (! empty($css_groupsasync_exclude))
        {
        	$css_groupsasync_exclude = implode("\n", $css_groupsasync_exclude);
        	$fobj->setValue('groups_async_exclude', '', $css_groupsasync_exclude);
        }
        else
        {
        	$fobj->setValue('groups_async_exclude', '', '');
        }
        if (! empty($params['css_groupsasync_delay']))
        {
        	$css_groupsasync_delay = $params['css_groupsasync_delay'];
        }
        if (! empty($css_groupsasync_delay))
        {
        	foreach($css_groupsasync_delay As $key => $del)
        	{
        	$c_ga_delay[] = $key .':'.$del;
        	}
        	$css_groupsasync_delay = implode("\n", $c_ga_delay);
        	$fobj->setValue('groups_async_delay', '', $css_groupsasync_delay);
        }
        else
        {
        	$fobj->setValue('groups_async_delay', '', '');
        }
        //
        
        //end
        if ($fobj->getValue('jssimulation_parse') == 0)
        {
            $fobj->setValue('jssimulation_parse', '', 1);
        }
        
        $cron_url = $fobj->getValue('cron_url');
        if (empty($cron_url))
        {
            $cron_url = JRoute::_(strtolower(JURI::root()) . 'index.php?option=com_multicache&view=simcontrol');
            $fobj->setValue('cron_url', '', $cron_url);
        }
        $redirect_uri = $fobj->getValue('redirect_uri');
        if (empty($redirect_uri))
        {
            $redirect_uri = JRoute::_(strtolower(JURI::root()) . 'administrator/index.php?option=com_multicache&view=lnobject&authg=2');
            $fobj->setValue('redirect_uri', '', $redirect_uri);
        }
        $cart_url_mode = $fobj->getValue('cartmodeurlinclude');
        if (! empty($cart_url_mode))
        {
            $cart_url_array = json_decode($cart_url_mode, true);
            $cart_url_native = implode("\n", $cart_url_array);
            $fobj->setValue('cartmodeurlinclude', '', $cart_url_native);
        }
        
        $cart_session_vars = $fobj->getValue('cartsessionvariables');
        if (! empty($cart_session_vars))
        {
            $cart_session_array = json_decode($cart_session_vars, true);
            $cart_session_native = implode("\n", $cart_session_array);
            $fobj->setValue('cartsessionvariables', '', $cart_session_native);
        }
        
        $cart_diff_vars = $fobj->getValue('cartdifferentiators');
        if (! empty($cart_diff_vars))
        {
            $cart_diff_array = json_decode($cart_diff_vars, true);
            $cart_diff_native = implode("\n", $cart_diff_array);
            $fobj->setValue('cartdifferentiators', '', $cart_diff_native);
        }
        
        $jst_urlinclude = $fobj->getValue('jst_urlinclude');
        if (! empty($jst_urlinclude))
        {
            $jst_urlinclude_array = json_decode($jst_urlinclude, true);
            $jst_urlinclude_native = implode("\n", $jst_urlinclude_array);
            $fobj->setValue('jst_urlinclude', '', $jst_urlinclude_native);
        }
        
        $jst_query_param = $fobj->getValue('jst_query_param');
        if (! empty($jst_query_param))
        {
            $jst_query_param_array = json_decode($jst_query_param, true);
            $jst_query_param_native = implode("\n", $jst_query_param_array);
            $fobj->setValue('jst_query_param', '', $jst_query_param_native);
        }
        
        $jst_url_string = $fobj->getValue('jst_url_string');
        if (! empty($jst_url_string))
        {
            $jst_url_string_array = json_decode($jst_url_string, true);
            $jst_url_string_native = implode("\n", $jst_url_string_array);
            $fobj->setValue('jst_url_string', '', $jst_url_string_native);
        }
        // css start
        
        $css_urlinclude = $fobj->getValue('css_urlinclude');
        if (! empty($css_urlinclude))
        {
            $css_urlinclude_array = json_decode($css_urlinclude, true);
            $css_urlinclude_native = implode("\n", $css_urlinclude_array);
            $fobj->setValue('css_urlinclude', '', $css_urlinclude_native);
        }
        
        $css_query_param = $fobj->getValue('css_query_param');
        if (! empty($css_query_param))
        {
            $css_query_param_array = json_decode($css_query_param, true);
            $css_query_param_native = implode("\n", $css_query_param_array);
            $fobj->setValue('css_query_param', '', $css_query_param_native);
        }
        
        $css_url_string = $fobj->getValue('css_url_string');
        if (! empty($css_url_string))
        {
            $css_url_string_array = json_decode($css_url_string, true);
            $css_url_string_native = implode("\n", $css_url_string_array);
            $fobj->setValue('css_url_string', '', $css_url_string_native);
        }
        // css end
        // img start
        
        $img_urlinclude = $fobj->getValue('images_urlinclude');
        if (! empty($img_urlinclude))
        {
            $img_urlinclude_array = json_decode($img_urlinclude, true);
            $img_urlinclude_native = implode("\n", $img_urlinclude_array);
            $fobj->setValue('images_urlinclude', '', $img_urlinclude_native);
        }
        
        $img_query_param = $fobj->getValue('images_query_param');
        if (! empty($img_query_param))
        {
            $img_query_param_array = json_decode($img_query_param, true);
            $img_query_param_native = implode("\n", $img_query_param_array);
            $fobj->setValue('images_query_param', '', $img_query_param_native);
        }
        
        $img_url_string = $fobj->getValue('images_url_string');
        if (! empty($img_url_string))
        {
            $img_url_string_array = json_decode($img_url_string, true);
            $img_url_string_native = implode("\n", $img_url_string_array);
            $fobj->setValue('images_url_string', '', $img_url_string_native);
        }
        // param
        $image_lazy_container_strings = $fobj->getValue('image_lazy_container_strings');
        if (! empty($image_lazy_container_strings))
        {
            $image_lazy_container_strings_array = json_decode($image_lazy_container_strings, true);
            $image_lazy_container_strings_native = implode("\n", $image_lazy_container_strings_array);
            $fobj->setValue('image_lazy_container_strings', '', $image_lazy_container_strings_native);
        }
        
        $image_lazy_image_selector_include_strings = $fobj->getValue('image_lazy_image_selector_include_strings');
        if (! empty($image_lazy_image_selector_include_strings))
        {
            $image_lazy_image_selector_include_strings_array = json_decode($image_lazy_image_selector_include_strings, true);
            $image_lazy_image_selector_include_strings_native = implode("\n", $image_lazy_image_selector_include_strings_array);
            $fobj->setValue('image_lazy_image_selector_include_strings', '', $image_lazy_image_selector_include_strings_native);
        }
        $image_lazy_image_selector_exclude_strings = $fobj->getValue('image_lazy_image_selector_exclude_strings');
        if (! empty($image_lazy_image_selector_exclude_strings))
        {
            $image_lazy_image_selector_exclude_strings_array = json_decode($image_lazy_image_selector_exclude_strings, true);
            $image_lazy_image_selector_exclude_strings_native = implode("\n", $image_lazy_image_selector_exclude_strings_array);
            $fobj->setValue('image_lazy_image_selector_exclude_strings', '', $image_lazy_image_selector_exclude_strings_native);
        }
        // img end
        /*
         * php-issue
         * PHP 5.5 code
         * if (empty($fobj->getValue('default_scrape_url'))){}
         * in 5.3 produces error cannot write to return value
         */
        
        $default_scrape_url_p = $fobj->getValue('default_scrape_url');
        if (empty($default_scrape_url_p))
        {
            
            $base_url = strtolower(JURI::root());
            $fobj->setValue('default_scrape_url', '', $base_url);
            $updateobj->default_scrape_url = $base_url;
        }
        // lets align default scrape urls to latest
        $dsu_session = $session->get('multicache_scrape_url_default');
        $dsu_session = isset($dsu_session) ? unserialize($dsu_session) : null;
        if (isset($dsu_session) && $dsu_session != $default_scrape_url_p)
        {
            $fobj->setValue('default_scrape_url', '', $dsu_session);
            $updateobj->default_scrape_url = $dsu_session;
            $session->clear('multicache_scrape_url_default');
        }
        // start css scrape css_scrape_url
        $css_default_scrape_url = $fobj->getValue('css_scrape_url');
        if (empty($css_default_scrape_url))
        {
            
            $base_url = strtolower(JURI::root());
            $fobj->setValue('css_scrape_url', '', $base_url);
            $updateobj->css_scrape_url = $base_url;
        }
        $dcsu_session = $session->get('multicache_css_url_default');
        $dcsu_session = isset($dcsu_session) ? unserialize($dcsu_session) : null;
        if (isset($dcsu_session) && $dcsu_session != $css_default_scrape_url)
        {
            $fobj->setValue('css_scrape_url', '', $dcsu_session);
            $updateobj->css_scrape_url = $dcsu_session;
            $session->clear('multicache_css_url_default');
        }
        
        $css_special_identifiers = $fobj->getValue('css_special_identifiers');
        if (! empty($css_special_identifiers))
        {
            $css_special_identifiers_array = json_decode($css_special_identifiers, true);
            $css_special_identifiers_native = implode("\n", $css_special_identifiers_array);
            $fobj->setValue('css_special_identifiers', '', $css_special_identifiers_native);
        }
        // lets align default css scrape url to latest
        // end css scrape
        
        $urltotest = $fobj->getValue('gtmetrix_test_url');
        if (empty($urltotest))
        {
            // $homepage = "http://" . JURI::getInstance()->getHost() . "/";
            $homepage = JURI::root();
            $fobj->setValue('gtmetrix_test_url', '', $homepage);
            $updateobj->gtmetrix_test_url = $homepage;
        }
        $startdate = $fobj->getValue('googlestartdate');
        $enddate = $fobj->getValue('googleenddate');
        if (empty($startdate))
        {
            $startdatevalue = date('Y-m-d', strtotime('-1 year'));
            $fobj->setValue('googlestartdate', '', $startdatevalue);
            $updateobj->googlestartdate = $startdatevalue;
        }
        if (empty($enddate))
        {
            $enddatevalue = date('Y-m-d');
            $fobj->setValue('googleenddate', '', $enddatevalue);
            $updateobj->googleenddate = $enddatevalue;
        }
        $social_script_bits = $fobj->getValue('social_script_identifiers');
        if (! empty($social_script_bits))
        {
            $social_script_bits = json_decode($social_script_bits, true);
            $social_script_bits = array_filter($social_script_bits);
            $social_script_obj = implode("\n", $social_script_bits);
            $fobj->setValue('social_script_identifiers', '', $social_script_obj);
        }
        $advertisement_script_bits = $fobj->getValue('advertisement_script_identifiers');
        if (! empty($advertisement_script_bits))
        {
            $advertisement_script_bits = json_decode($advertisement_script_bits, true);
            $advertisement_script_bits = array_filter($advertisement_script_bits);
            $advertisement_script_obj = implode("\n", $advertisement_script_bits);
            $fobj->setValue('advertisement_script_identifiers', '', $advertisement_script_obj);
        }
        // head & body tags
        $pre_head_stub_identifiers = $fobj->getValue('pre_head_stub_identifiers');
        if (! empty($pre_head_stub_identifiers))
        {
            $pre_head_stub_identifiers = json_decode($pre_head_stub_identifiers, true);
            $pre_head_stub_identifiers = array_filter($pre_head_stub_identifiers);
            $pre_head_stub_identifier_obj = implode("\n", $pre_head_stub_identifiers);
            $fobj->setValue('pre_head_stub_identifiers', '', $pre_head_stub_identifier_obj);
        }
        else
        {
            $fobj->setValue('pre_head_stub_identifiers', '', '<head>');
        }
        
        $head_stub_identifiers = $fobj->getValue('head_stub_identifiers');
        if (! empty($head_stub_identifiers))
        {
            $head_stub_identifiers = json_decode($head_stub_identifiers, true);
            $head_stub_identifiers = array_filter($head_stub_identifiers);
            $head_stub_identifier_obj = implode("\n", $head_stub_identifiers);
            $fobj->setValue('head_stub_identifiers', '', $head_stub_identifier_obj);
        }
        else
        {
            $fobj->setValue('head_stub_identifiers', '', '</head>');
        }
        
        $body_stub_identifiers = $fobj->getValue('body_stub_identifiers');
        if (! empty($body_stub_identifiers))
        {
            $body_stub_identifiers = json_decode($body_stub_identifiers, true);
            $body_stub_identifiers = array_filter($body_stub_identifiers);
            $body_stub_identifier_obj = implode("\n", $body_stub_identifiers);
            $fobj->setValue('body_stub_identifiers', '', $body_stub_identifier_obj);
        }
        else
        {
            $fobj->setValue('body_stub_identifiers', '', '<body>');
        }
        
        $footer_stub_identifiers = $fobj->getValue('footer_stub_identifiers');
        if (! empty($footer_stub_identifiers))
        {
            $footer_stub_identifiers = json_decode($footer_stub_identifiers, true);
            $footer_stub_identifiers = array_filter($footer_stub_identifiers);
            $footer_stub_identifier_obj = implode("\n", $footer_stub_identifiers);
            $fobj->setValue('footer_stub_identifiers', '', $footer_stub_identifier_obj);
        }
        else
        {
            $fobj->setValue('footer_stub_identifiers', '', '</body>');
        }
        // end head & body tags
        $t_page_obj = $fobj->getValue('additionalpagecacheurls');
        if (! empty($t_page_obj))
        {
            $temp_url_ar = json_decode($t_page_obj, true);
            $temp_url_ar = array_filter($temp_url_ar);
            $page_obj = implode("\n", $temp_url_ar);
            $fobj->setValue('additionalpagecacheurls', '', $page_obj);
        }
        // check if a precache factor has been set
        /*
         * $options = array(
         *
         * 'cachebase' => JPATH_SITE . '/cache'
         * );
         */
        
        MulticacheHelper::clean_cache('com_plugins', null);
        /*
         * deprecated moved precache completely to configuration.
         * $plugin = json_decode(JPluginHelper::getPlugin('system', 'cache')->params);
         * if (isset($plugin->precache_factor) && $plugin->precache_factor != $fobj->getValue('precache_factor_default'))
         * {
         * $fobj->setValue('precache_factor_default', '', $plugin->precache_factor);
         * $updateobj->precache_factor_default = $plugin->precache_factor;
         * }
         */
        // end precache checks
        $ch = $config->get('cache_handler');
        $ccmode = $config->get('caching');
        $ct = $config->get('cachetime');
        $cmpersist = $config->get('multicache_persist');
        $cmc = $config->get('multicache_compress');
        $cmh = $config->get('multicache_server_host');
        $cmport = $config->get('multicache_server_port');
        $cih = $config->get('indexhack');
        $cgz = $config->get('gzip_factor');
        $cprec = $config->get('precache_factor');
        $cmulticachedistribution = $config->get('multicachedistribution');
        $cprecache_switch = $config->get('multicacheprecacheswitch', null);
        
        if (isset($cprec) && $cprec != $fobj->getValue('precache_factor_default') && $fobj->getValue('deployment_method') != 1)
        {
            $fobj->setValue('precache_factor_default', '', $cprec);
            $updateobj->precache_factor_default = $cprec;
        }
        
        if (isset($ch) && $ch != $fobj->getValue('cache_handler'))
        {
            $fobj->setValue('cache_handler', '', $ch);
            $updateobj->cache_handler = $ch;
        }
        
        if (isset($ct) && $ct != $fobj->getValue('cachetime'))
        {
            $fobj->setValue('cachetime', '', $ct);
            $updateobj->cachetime = $ct;
        }
        if (isset($ccmode) && $ccmode != $fobj->getValue('caching'))
        {
            $fobj->setValue('caching', '', $ccmode);
            $updateobj->caching = $ccmode;
        }
        if (isset($cmpersist) && $cmpersist != $fobj->getValue('multicache_persist'))
        {
            $fobj->setValue('multicache_persist', '', $cmpersist);
            $updateobj->multicache_persist = $cmpersist;
        }
        if (isset($cmc) && $cmc != $fobj->getValue('multicache_compress'))
        {
            $fobj->setValue('multicache_compress', '', $cmc);
            $updateobj->multicache_compress = $cmc;
        }
        if (isset($cmh) && $cmh != $fobj->getValue('multicache_server_host'))
        {
            $fobj->setValue('multicache_server_host', '', $cmh);
            $updateobj->multicache_server_host = $cmh;
        }
        if (isset($cmport) && $cmport != $fobj->getValue('multicache_server_port'))
        {
            $fobj->setValue('multicache_server_port', '', $cmport);
            $updateobj->multicache_server_port = $cmport;
        }
        if (isset($cih) && $cih != $fobj->getValue('indexhack'))
        {
            $fobj->setValue('indexhack', '', $cih);
            $updateobj->indexhack = $cih;
        }
        if (isset($cgz) && $cgz != $fobj->getValue('gzip_factor_default') && $fobj->getValue('deployment_method') != 1)
        {
            $fobj->setValue('gzip_factor_default', '', $cgz);
            $updateobj->gzip_factor_default = $cgz;
        }
        
        if (isset($cmulticachedistribution) && $cmulticachedistribution != $fobj->getValue('multicachedistribution'))
        {
            $fobj->setValue('multicachedistribution', '', $cmulticachedistribution);
            $updateobj->multicachedistribution = $cmulticachedistribution;
        }
        if (! isset($cprecache_switch) && $fobj->getValue('force_precache_off') == 1)
        {
            $fobj->setValue('force_precache_off', 0);
            $updateobj->force_precache_off = 0;
        }
        elseif (isset($cprecache_switch) && $cprecache_switch === true && $fobj->getValue('force_precache_off') == 0)
        {
            $fobj->setValue('force_precache_off', 1); // force_precache_off IS NOT OF multicacheprecacheswitch
            $updateobj->force_precache_off = 1;
        }
        $temparr = (array) $updateobj;
        if (! empty($temparr))
        {
            
            $db = JFactory::getDBO();
            $db->getQuery(true);
            $updateobj->id = 1;
            $result = $db->updateObject('#__multicache_config', $updateobj, 'id');
        }
        Return $fobj;
    
    }

    protected function loadFormData()
    {
        // Check the session for previously entered form data.
        $data = JFactory::getApplication()->getUserState('com_multicache.edit.config.data', array());
        
        if (empty($data))
        {
            $data = $this->getItem();
        }
        
        return $data;
    
    }

    /*
     * In the prepare_Config method , caution is excercised and the config object is not sought from the Factory Class.
     * This is due to the fact that the Factory class might add template related parametrs as per its instantiation.
     * If these are fed back to the configuration file<<<<<CRASH>>>>>
     */
    protected function prepare_Config($obj)
    {

        $app = JFactory::getApplication();
        require_once (JPATH_CONFIGURATION . '/configuration.php');
        
        $config = new JConfig();
        $config->cache_handler = $obj->cache_handler;
        $config->caching = $obj->caching;
        $config->cachetime = $obj->cachetime;
        $config->multicache_persist = $obj->multicache_persist;
        $config->multicache_compress = $obj->multicache_compress;
        $config->multicache_server_host = $obj->multicache_server_host;
        $config->multicache_server_port = $obj->multicache_server_port;
        $config->indexhack = $obj->indexhack;
        $config->precache_factor = $obj->precache_factor_default;
        $config->gzip_factor = $obj->gzip_factor_default;
        $config->force_locking_off = $obj->force_locking_off;
        $config->multicachedistribution = $obj->multicachedistribution;
        $config->jmulticache_hash = md5(md5(md5(JURI::getInstance()->getHost() . '-' . $config->secret)));
        //broswer cache
        $plugin_params = JPluginHelper::getPlugin('system', 'cache')->params;
        $plugin_params = json_decode($plugin_params);
        $browser_cache = isset($plugin_params->browsercache) ? $plugin_params->browsercache: false;
        $config->browsercache = $browser_cache;
        
        if (isset($obj->force_precache_off) && $obj->force_precache_off == 1)
        {
            $config->multicacheprecacheswitch = true;
        }
        elseif (isset($obj->force_precache_off) && $obj->force_precache_off == 0)
        {
            $config->multicacheprecacheswitch = null;
        }
        
        $registry = new Registry();
        $registry->loadObject($config);
        // $this->writeConfigFile($registry);
        MulticacheHelper::writeToConfig($registry);
    
    }

    protected function getRelevantPageScript()
    {

        $app = JFactory::getApplication();
        if (! class_exists('MulticachePageScripts'))
        {
            $message = JText::_('COM_MULTICACHE_CLASS_MULTICACHE_PAGE_SCRIPTS_DOESNOTEXIST');
            $app->enqueueMessage($message, 'notice');
            
            Return false;
        }
        
        if (property_exists('MulticachePageScripts', 'working_script_array'))
        {
            
            $pagescripts = MulticachePageScripts::$working_script_array;
        }
        elseif (property_exists('MulticachePageScripts', 'original_script_array'))
        {
            $pagescripts = MulticachePageScripts::$original_script_array;
        }
        else
        {
            // register error Multicache Class exists with no proerties
            $message = JText::_('COM_MULTICACHE_CLASS_MULTICACHE_PAGE_SCRIPTS_HASNODEFINEDPROPERTIES');
            $app->enqueueMessage($message, 'error');
            Return false;
        }
        
        Return $pagescripts;
    
    }

    protected function getRelevantPageCss()
    {

        $app = JFactory::getApplication();
        if (! class_exists('MulticachePageCss'))
        {
            $message = JText::_('COM_MULTICACHE_CLASS_MULTICACHE_PAGE_CSS_DOESNOTEXIST');
            $app->enqueueMessage($message, 'notice');
            
            Return false;
        }
        
        if (property_exists('MulticachePageCss', 'working_css_array'))
        {
            
            $pagecss = MulticachePageCss::$working_css_array;
        }
        elseif (property_exists('MulticachePageCss', 'original_css_array'))
        {
            $pagecss = MulticachePageCss::$original_css_array;
        }
        else
        {
            // register error Multicache Class exists with no proerties
            $message = JText::_('COM_MULTICACHE_CLASS_MULTICACHE_PAGE_CSS_HASNODEFINEDPROPERTIES');
            $app->enqueueMessage($message, 'error');
            Return false;
        }
        
        Return $pagecss;
    
    }

    protected function getLastTest()
    {

        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('*');
        $query->from($db->quoteName('#__multicache_advanced_test_results'));
        $query->order($db->quoteName('id') . '	DESC');
        $db->setQuery($query);
        Return $db->loadObject();
    
    }

    protected function getlastTestGroup()
    {

        $db = JFactory::getDBo();
        $query = $db->getQuery(true);
        $query->select('*');
        $query->from($db->quoteName('#__multicache_advanced_testgroups'));
        $query->order($db->quoteName('id') . ' DESC');
        $db->setQuery($query);
        Return $db->loadObject();
    
    }

    protected function getLoadSection($section, $jsarray_obj, $tbl, $params = null)
    {

        $app = JFactory::getApplication();
        
        foreach ($jsarray_obj as $obj)
        {
            
            if ($obj["loadsection"] != $section)
            {
                continue;
            }
            
            if (isset($obj["group"]) && (bool) ($group_name = $obj["group"]) == true && isset(self::$_groups[$group_name]["success"]) && self::$_groups[$group_name]["success"] == true)
            {
                
                if (! isset(self::$_groups_loaded[$group_name]))
                {
                    
                    $load_string .= unserialize(self::$_groups[$group_name]["script_tag_url"]); //
                    
                    /*
                     * OTHER OPTIONS
                     *
                     * $load_string .= MulticacheHelper::getloadableSourceScript(self::$_groups[$group_name]["url"] , false);
                     *
                     * $load_string .= MulticacheHelper::getloadableSourceScript(unserialize( self::$_groups[$group_name]["callable_url"]) , false);
                     */
                    self::$_groups_loaded[$group_name] = true;
                }
                
                continue;
            }
            
            $sig = $obj["signature"];
            if (isset(self::$_cdn_segment[$sig]) && (bool) self::$_cdn_segment[$sig] == true)
            {
            	if(!empty($obj['promises']))
            	{
            		$alias_grp = $obj;
            		$alias_grp['src'] = self::$_cdn_segment[$sig];
            	
            		$c_string = $this->preparePromise($alias_grp , '', false ,true );
            		$load_string .= MulticacheHelper::getloadableCodeScript($c_string , $obj["async"],true,  $params);
            	
            	}
            	else
            	{
            		$load_string .= MulticacheHelper::getloadableSourceScript(self::$_cdn_segment[$sig], $obj["async"], $params);
            	}
                 // $load_string .= MulticacheHelper::getloadableSourceScript(self::$_cdn_segment[$sig], $obj["async"], $params);
            }
            // if src else code
            elseif (! empty($obj["src"]))
            {
                
                // if obj int use only absolute
                if ($obj["internal"])
                {
                	if(!empty($obj['promises']))
                	{
                		$alias_grp = $obj;
                		$alias_grp['src'] = $obj["absolute_src"];
                		$c_string = $this->preparePromise($alias_grp , '', false ,true );
                		$load_string .= MulticacheHelper::getloadableCodeScript($c_string , $obj["async"],true,  $params);
                			
                	}
                	else{
                	
                		$load_string .= MulticacheHelper::getloadableSourceScript($obj["absolute_src"], $obj["async"], $params);
                	}
                     // $load_string .= MulticacheHelper::getloadableSourceScript($obj["absolute_src"], $obj["async"], $params);
                }
                elseif (! $obj["internal"])
                {
                    // redundancy delared on purpose to maintain elseif formats
                    // external source
                	if(!empty($obj['promises']))
                	{
                		$alias_grp = $obj;
                		$c_string = $this->preparePromise($alias_grp , '', false ,true );
                		$load_string .= MulticacheHelper::getloadableCodeScript($c_string , $obj["async"],true,  $params);
                	}
                	else{
                	
                		$load_string .= MulticacheHelper::getloadableSourceScript($obj["src"], $obj["async"], $params);
                	}
                      //$load_string .= MulticacheHelper::getloadableSourceScript($obj["src"], $obj["async"], $params);
                }
                /*
                 * MORE ELSEIF CAN COME HERE TO ENTERTAIN ALIAS LOADING ETC.
                 */
            }
            elseif (! empty($obj["code"]))
            {
                $unserialized_code = unserialize($obj["serialized_code"]);
                $code = ! empty($unserialized_code) ? $unserialized_code : $obj["code"];
                if(!empty($obj['promises']))
                {
                	$alias_grp = $obj;
                	$c_string = $this->preparePromise($alias_grp , $code, false  );
                	$code = MulticacheHelper::getloadableCodeScript($c_string , $obj["async"],true,  $params);
                }
                if ($tbl->compress_js)
                {
                    
                    $load_string .= MulticacheHelper::getloadableCodeScript(MulticacheJSOptimize::process($code), $obj["async"], true, $params);
                }
                else
                {
                	//detected mistake in 1.0.0.8 getloadablecodescript requires a parameter to ascertain whether to unserialize code
                    $load_string .= MulticacheHelper::getloadableCodeScript($code, $obj["async"],true, $params);
                }
            }
            else
            {
                $app->enqueueMessage(JText::_('COM_MULTICACHE_CLASS_MULTICACHE_PAGE_SCRIPTS_LOADSECTION_UNDEFINED_SCRIPT_TYPE_ERROR'), 'error');
            }
        }
        if (empty($load_string))
        {
            
            Return false;
        }
        Return serialize($load_string);
    
    }

    protected function getCssLoadSection($section, $cssarray_obj, $tbl, $param)
    {

        $app = JFactory::getApplication();
        
        foreach ($cssarray_obj as $obj)
        {
            
            if ($obj["loadsection"] != $section)
            {
                continue;
            }
            $sig = $obj["signature"];
            if (isset($obj["group"]) && (bool) ($group_name = $obj["group"]) == true && isset(self::$_groups_css[$group_name]["success"]) && self::$_groups_css[$group_name]["success"] == true)
            {
                
            	if (! isset(self::$_groups_loaded_css[$group_name]) && empty($param['css_groupsasync']))
					{
                    
                    $load_string .= unserialize(self::$_groups_css[$group_name]["css_tag_url"]); //
                    
                    /*
                     * OTHER OPTIONS
                     *
                     * $load_string .= MulticacheHelper::getloadableSourceScript(self::$_groups[$group_name]["url"] , false);
                     *
                     * $load_string .= MulticacheHelper::getloadableSourceScript(unserialize( self::$_groups[$group_name]["callable_url"]) , false);
                     */
                    self::$_groups_loaded_css[$group_name] = true;
                }
                
                continue;
            }
            elseif ( isset(self::$_cdn_segment_css[$sig]) && (bool) self::$_cdn_segment_css[$sig] == true)
            {
            	
                $load_string .= $b = MulticacheHelper::getCsslinkUrl(self::$_cdn_segment_css[$sig], 'link_url', self::$_mediaVersion);
                
            }
            elseif(!empty($obj['cdnalias']) && !empty($obj['cdn_url_css']))
            {
            	
            	$load_string .= MulticacheHelper::getCsslinkUrl($obj['cdn_url_css'], 'link_url', self::$_mediaVersion);
            }
            // if href else code
            elseif (! empty($obj["href"]))
            {
            	
                // if obj int use only absolute
                if ($obj["internal"])
                {
                    
                    $load_string .= MulticacheHelper::getCsslinkUrl($obj["absolute_src"], 'link_url', self::$_mediaVersion);
                }
                elseif (! $obj["internal"])
                {
                    
                    // redundancy delared on purpose to maintain elseif formats
                    // external source
                    $load_string .= MulticacheHelper::getCsslinkUrl($obj["href"], 'link_url', self::$_mediaVersion);
                }
                /*
                 * MORE ELSEIF CAN COME HERE TO ENTERTAIN ALIAS LOADING ETC.
                 */
            }
            elseif (! empty($obj["code"]))
            {
            	
            	
                $unserialized_code = unserialize($obj["serialized_code"]);
                $code = ! empty($unserialized_code) ? $unserialized_code : $obj["code"];
                //
                if ($tbl->compress_css)
                {
                    $load_string .= MulticacheHelper::getloadableCodeCss(MulticacheCSSOptimize::optimize($code), null, null, true);
                }
                else
                {
                    $load_string .= MulticacheHelper::getloadableCodeCss($code);
                }
            }
            else
            {
                
                $app->enqueueMessage(JText::_('COM_MULTICACHE_CLASS_MULTICACHE_PAGE_CSS_LOADSECTION_UNDEFINED_CSS_TYPE_ERROR'), 'error');
            }
        }
        if (empty($load_string))
        {
            
            Return false;
        }
        
        Return serialize($load_string);
    
    }

    protected function getCache_id($url, $group = 'page')
    {

        $obj = new JCacheStoragetemp();
        $cache_id = $obj->getCacheidAlternate($url, $group);
        Return $cache_id;
    
    }

    protected function getTemplateKeys($page_script_object)
    {

        $template_object = MulticacheHelper::getPageScriptObject($page_script_object);
        $template_object = $template_object->pagetransposeobject;
        if (empty($template_object))
        {
            Return false;
        }
        $template_keys = array();
        foreach ($template_object as $key => $value)
        {
            $template_keys[] = $key;
        }
        Return $template_keys;
    
    }

    protected function getTemplateCssKeys($page_css_object)
    {

        $template_cssobject = MulticacheHelper::getPageCssObject($page_css_object);
        $template_cssobject = $template_cssobject->CssTransposeObject;
        if (empty($template_cssobject))
        {
            Return false;
        }
        $template_csskeys = array();
        foreach ($template_cssobject as $key => $value)
        {
            $template_csskeys[] = $key;
        }
        Return $template_csskeys;
    
    }

    protected function getCombinedCode($grp, $tbl)
    {

        if (empty($grp))
        {
            Return false;
        }
        $app = JFactory::getApplication();
        
        foreach ($grp as $key => $group)
        {
            
            $begin_comment = "/* Inserted by MulticacheReduceRoundtrips source code insert	key-" . $key . "	rank-" . $group["rank"] . "  src-" . substr($group["src"], 0, 10) . " */";
            $end_comment = "/* end MulticacheRoundtrip insert */";
            $begin_comment_code = "/* Inserted by MulticacheReduceRoundtrips  code insert	key-" . $key . "	rank-" . $group["rank"] . "   */";
            $end_comment_code = "/* end MulticacheRoundtrip code insert */";
            
            if ($group["internal"])
            {
                // actual question here is is this source or is this code
                // source the code and place it here
                // ensure it ends with ;
                $url = $group["absolute_src"];
                
                if (isset(self::$_mediaVersion))
                {
                    $url_temp = $url;
                    $j_uri = JURI::getInstance($url);
                    $j_uri->setVar('mediaFormat', self::$_mediaVersion);
                    $url = $j_uri->toString();
                }
                $url = MulticacheHelper::checkCurlable($url);
                $curl_obj = MulticacheHelper::get_web_page($url);
                if ($curl_obj["http_code"] == 200)
                {
                    // $code_string .= $begin_comment . MulticacheHelper::clean_code(trim($curl_obj["content"])) . $end_comment;
                    if ($tbl->compress_js)
                    {
                        $ret_content = trim(MulticacheJSOptimize::process($curl_obj["content"]));
                    }
                    else
                    {
                        $ret_content = $curl_obj["content"];
                    }
                    $c_string = ! empty(self::$_jscomments) ? $begin_comment . MulticacheHelper::clean_code(trim($ret_content)) . $end_comment : MulticacheHelper::clean_code(trim($ret_content));
                    if(!empty($group['promises']))
                    {
                    	$c_string = MulticacheHelper::clean_code(trim($ret_content));
                    	$c_string = $this->preparePromise($group , $c_string);
                    }
                   
                    $code_string .= $c_string;
                }
                else
                {
                    // register error
                    
                    $e_message = "	" . $curl_obj["errmsg"] . " uri- " . $url;
                    $app->enqueueMessage(JText::_('COM_MULTICACHE_ERROR_PAGESCRIPT_GETCOMBINECODE_CURL_ERROR') . $e_message . '   response-' . $curl_obj["http_code"], 'warning');
                    Return false;
                }
            }
            else
            {
                // unserialize and tie code here
                if (! empty($group["serialized_code"]))
                {
                    // issue with blank space before code
                    $unserialized_code = unserialize($group["serialized_code"]);
                    $code = ! empty($unserialized_code) ? $unserialized_code : $group["code"];
                    // end issue
                    if ($tbl->compress_js)
                    {
                        $unserialized_code = MulticacheJSOptimize::process($code);
                    }
                    else
                    {
                        $unserialized_code = $code; // maintain structure for two versions
                    }
                    // $code_string .= $begin_comment_code . MulticacheHelper::clean_code(trim(unserialize($group["serialized_code"]))) . $end_comment_code;
                    //$code_string .= ! empty(self::$_jscomments) ? $begin_comment_code . MulticacheHelper::clean_code(trim($unserialized_code)) . $end_comment_code : MulticacheHelper::clean_code(trim($unserialized_code));
                    $c_string = ! empty(self::$_jscomments) ? $begin_comment_code . MulticacheHelper::clean_code(trim($unserialized_code)) . $end_comment_code : MulticacheHelper::clean_code(trim($unserialized_code));
                    if(!empty($group['promises']))
                    {
                    	$c_string = MulticacheHelper::clean_code(trim($unserialized_code));
                    	$c_string = $this->preparePromise($group , $c_string);
                    }
                    $code_string .= $c_string;
                }
                else
                {
                    // register error
                    $app->enqueueMessage(JText::_('COM_MULTICACHE_ERROR_PAGESCRIPT_GROUP_NOT_INTERNAL_CODE_EMPTY_ROOT_SCRIPT_DETECT_ERROR'), 'warning');
                    Return false;
                }
            }
        }
        
        Return serialize($code_string);
    
    }

    protected static function resolveAbsolute($url, $image_url)
    {

        $base1_uri = $base2_uri = $base3_uri = $base4_uri = null;
        $base_uri = $url;
        
        $uri_instance = JURI::getInstance($base_uri);
        $base0_uri = $uri_instance->toString(array(
            "scheme",
            "host"
        ));
        $base_uri_array = explode('/', $base_uri);
        $nextpath = $uri_instance->getPath();
        
        if ($nextpath)
        {
            array_pop($base_uri_array);
            $base1_uri = implode('/', $base_uri_array); // one down
            $nextpath = JURI::getInstance($base1_uri)->getPath();
        }
        
        if (isset($nextpath))
        {
            array_pop($base_uri_array);
            $base2_uri = implode('/', $base_uri_array); // two down
            $nextpath = JURI::getInstance($base2_uri)->getPath();
        }
        if (isset($nextpath))
        {
            $exists = array_pop($base_uri_array);
            $base3_uri = implode('/', $base_uri_array); // three down
            $nextpath = JURI::getInstance($base3_uri)->getPath();
        }
        if (isset($nextpath))
        {
            $exists = array_pop($base_uri_array);
            $base4_uri = implode('/', $base_uri_array); // four down
                                                            // $nextpath = JURI::getInstance($base4_uri)->getPath();
        }
        
        // start rules
        // Reference : http://www.ietf.org/rfc/rfc3986
        // assumption http://a/b/c/d;p?q
        // lets handle Normal cases of image uri
        // case 1: /image
        // case2 : image tested :confirmed
        // case 3: ../image
        // case4 : ./image
        // case 5a: ..image
        // case5b : .image
        // case 6a : ../..image
        // case 6b : ../../image
        // case 7a : // absolute url
        // case 7b : http:// absolute url
        // case 7c :https://
        // case 8b ../../../image
        // case 8a ../../..image
        if (strpos($image_url, '//') === 0 || strpos($image_url, 'http://') === 0 || strpos($image_url, 'https://') === 0)
        {
            $complete_image = $image_url; // absolute urls
        }
        elseif (strpos($image_url, '/') === 0)
        {
            // case 1:
            // tested:simulation
            $complete_image = $base0_uri . $image_url;
        }
        elseif (strpos($image_url, '../../../') === 0)
        {
            
            // case: "../../" = "http://a/" case 8b
            // tested:simulation
            $complete_image = $base0_uri . substr($image_url, 8);
        }
        elseif (strpos($image_url, '../../..') === 0)
        {
            
            // case: "../../" = "http://a/" case 8a
            // tested:simulation
            $complete_image = $base0_uri . '/' . substr($image_url, 8);
        }
        elseif (strpos($image_url, '../../') === 0)
        {
            
            // case: "../../" = "http://a/" case 6b
            // tested:simulation
            $complete_image = isset($base3_uri) ? $base3_uri . substr($image_url, 5) : $base0_uri . substr($image_url, 5);
        }
        elseif (preg_match('/^\.\.\/\.\.[A-Za-z0-9]+/', $image_url))
        {
            
            // case: "../.." = "http://a/" case 6a
            // tested:simulation
            $complete_image = isset($base3_uri) ? $base3_uri . '/' . substr($image_url, 5) : $base0_uri . '/' . substr($image_url, 5);
        }
        elseif (strpos($image_url, './') === 0)
        {
            // case: "./" = "http://a/b/c/" case 4
            // tested:simulation
            $complete_image = isset($base1_uri) ? $base1_uri . substr($image_url, 1) : $base0_uri . substr($image_url, 1);
        }
        elseif (strpos($image_url, '../') === 0)
        {
            // case: "../" = "http://a/b/" case 3
            // tested:simulation
            $complete_image = isset($base2_uri) ? $base2_uri . substr($image_url, 2) : $base0_uri . substr($image_url, 2);
        }
        elseif (preg_match('/^\.[A-Za-z0-9]+/', $image_url))
        {
            // "." = "http://a/b/c/" case 5b
            // tested:simulation
            $complete_image = isset($base1_uri) ? $base1_uri . '/' . substr($image_url, 1) : $base0_uri . '/' . substr($image_url, 1);
        }
        elseif (preg_match('/^\.\.[A-Za-z0-9]+/', $image_url))
        {
            // ".." = "http://a/b/" case 5a
            // tested:simulation
            $complete_image = isset($base2_uri) ? $base2_uri . '/' . substr($image_url, 2) : $base0_uri . '/' . substr($image_url, 2);
        }
        elseif (preg_match('/^[A-Za-z0-9]+/', $image_url))
        {
            
            // case 2 : "g" = "http://a/b/c/g"
            // tested:confirmed
            $complete_image = isset($base1_uri) ? $base1_uri . '/' . $image_url : $base0_uri . '/' . $image_url;
        }
        Return $complete_image;
    
    }

    protected function replaceAtImports($content, $group)
    {

        $pattern = '~(?>[/@]?[^/@]*+(?:/\*(?>\*?[^\*]*+)*?\*/)?)*?\K(?:@import[^;}]++;?|\K$)~i';
        $replacedContent = preg_replace_callback($pattern, 'self::repAtImports', $content);
        Return $replacedContent;
    
    }

    protected static function repAtImports($matches)
    {

        if (empty($matches[0]))
        {
            Return $matches[0];
        }
        $url_pattern = '~(?:http:|https:|)\/\/[^\'"]+~';
        preg_match($url_pattern, $matches[0], $url_matches);
        self::$_atimports_prop[] = $url_matches[0];
        Return '';
    
    }

    protected function replaceImgUrls($content, $group)
    {

        self::$_temp_group = $group;
        $e = self::DOUBLE_QUOTE_STRING . '|' . self::SINGLE_QUOTE_STRING . '|' . self::BLOCK_COMMENTS . '|' . self::LINE_COMMENTS;
        $replacedContent = preg_replace_callback("#(?>[(]?[^('\"/]*+(?:{$e}|/)?)*?(?:(?<=url)\(\s*+\K['\"]?((?<!['\"])[^\s)]*+|(?<!')[^\"]*+|[^']*+)['\"]?|\K$)#i", 'self::replaceImages', $content);
        /*
         * $sCorrectedContent = preg_replace_callback(
         * "#(?>[(]?[^('\"/]*+(?:{$e}|/)?)*?(?:(?<=url)\(\s*+\K['\"]?((?<!['\"])[^\s)]*+|(?<!')[^\"]*+|[^']*+)['\"]?|\K$)#i",
         * function ($aMatches) use ($aUrl, $obj)
         * {
         * return $obj->_correctUrlCB($aMatches, $aUrl);
         * }, $sContent);
         */
        
        Return $replacedContent;
    
    }

    protected static function replaceImages($matches)
    {
        // need to test the preg_match of last arguement
        if (! isset($matches[1]) || $matches[1] == '' || preg_match('#^(?:\(|/(?:/|\*))#', $matches[0]))
        {
            return $matches[0];
        }
        $imageurl = $matches[1];
        if (preg_match('#^data:#', $imageurl))
        {
            Return $matches[0];
        }
        
        $grp = self::$_temp_group;
        // handling external scripts
        if (! empty($grp["href"]) && isset($grp["internal"]) && $grp["internal"] == false)
        {
            $base_uri = $grp["href_clean"];
            
            $complete_image = self::resolveAbsolute($base_uri, $imageurl);
        }
        elseif (! empty($grp["href"]) && isset($grp["internal"]) && $grp["internal"] == true)
        {
            $base_uri = $grp["absolute_src"];
            $complete_image = self::resolveAbsolute($base_uri, $imageurl);
        }
        elseif (empty($grp["href"]) && ! isset($grp["internal"]) && ! empty($grp["serialized_code"]))
        {
            // redundant as we do not need to do this for style tags
            $base_uri = JURI::base();
            $complete_image = self::resolveAbsolute($base_uri, $imageurl);
        }
        
        // var_dump(preg_match('#^(?:\(|/(?:/|\*))#', $imageurl));?
        
        // var_dump(preg_match('#^/|://#', $imageurl));//matches an relative/ absolute url
        
        // var_dump(preg_match('#(?<!\\\\)[\s\'"(),]#', $imageurl));
        /*
         * echo "<br> compelet image , image";
         * var_dump($complete_image, $imageurl);
         * echo "<br>dumping url<br>";
         * var_dump($grp["href"], $grp["absolute_src"],$base_uri);
         */
        
        Return $complete_image;
    
    }

    protected function getCombinedCssCode($grp, $tbl)
    {

        if (empty($grp))
        {
            Return false;
        }
        $app = JFactory::getApplication();
        
        foreach ($grp as $key => $group)
        {
            
            $begin_comment = "/* Inserted by MulticacheGroup Css insert	key-" . $key . "	rank-" . $group["rank"] . "  src-" . substr($group["src"], 0, 10) . " */";
            $end_comment = "/* end MulticacheGroup Css insert */";
            $begin_comment_code = "/* Inserted by MulticacheGroup Css  inline insert	key-" . $key . "	rank-" . $group["rank"] . "   */";
            $end_comment_code = "/* end MulticacheGroup Css inline insert */";
            
            if (! empty($group["href"]))
            {
                // actual question here is is this source or is this code
                // source the code and place it here
                // ensure it ends with ;
                $url = ! empty($group["absolute_src"]) ? $group["absolute_src"] : $group["href_clean"];
                // post version 1.0.1.1 ammend to handle relative urls
                if (strpos($url, '/') === 0 && strpos($url, '//') !== 0)
                {
                    $url = substr(JURI::root(), 0, - 1) . $url;
                }
                if (isset(self::$_mediaVersion))
                {
                    $c_uri = JURI::getInstance($url);
                    
                    $c_uri->setVar('mediaFormat', self::$_mediaVersion);
                    $url = $c_uri->toString();
                }
                
                $curl_obj = MulticacheHelper::get_web_page($url);
                // lets try again with href
                if ($curl_obj["http_code"] != 200)
                {
                    if (! empty($group["href"]))
                    {
                        $url_temp = $group["href"];
                        $url = MulticacheHelper::Checkurl($url_temp, self::$_mediaVersion);
                        $curl_obj = MulticacheHelper::get_web_page($url);
                    }
                }
                
                if ($curl_obj["http_code"] == 200)
                {
                    // start experiment to replace backgroundimages with absolute urls
                    // echo "experiment<br>";
                    $abs_content = $this->replaceAtImports($curl_obj["content"], $group);
                    $abs_content = $this->replaceImgUrls($abs_content, $group);
                    
                    // end experiment
                    // $code_string .= $begin_comment . MulticacheHelper::clean_code(trim($curl_obj["content"])) . $end_comment;
                    if ($tbl->compress_css)
                    {
                        $ret_content = trim(MulticacheCSSOptimize::optimize($abs_content));
                    }
                    else
                    {
                        $ret_content = $abs_content;
                    }
                    
                    $code_string .= ! empty(self::$_css_comments) ? $begin_comment . $ret_content . $end_comment : trim($ret_content);
                }
                else
                {
                    // register error
                    
                    $e_message = "	" . $curl_obj["errmsg"] . " uri- " . $url;
                    $app->enqueueMessage(JText::_('COM_MULTICACHE_ERROR_PAGECSS_GETCOMBINECODE_CURL_ERROR') . $e_message . '   response-' . $curl_obj["http_code"], 'warning');
                    Return false;
                }
            }
            else
            {
                // unserialize and tie code here
                if (! empty($group["serialized_code"]))
                {
                    // Not required for style tags
                    // $this->replaceImgUrls(unserialize($group["serialized_code"]) , $group);
                    // $code_string .= $begin_comment_code . MulticacheHelper::clean_code(trim(unserialize($group["serialized_code"]))) . $end_comment_code;
                    // issue with blank space before code
                    $unserialized_code = unserialize($group["serialized_code"]);
                    $code = ! empty($unserialized_code) ? $unserialized_code : $group["code"];
                    // end issue
                    if ($tbl->compress_css)
                    {
                        $unserialized_code = MulticacheCSSOptimize::optimize($code);
                    }
                    else
                    {
                        $unserialized_code = $code; // maintain structure for two versions
                    }
                    
                    $code_string .= ! empty(self::$_css_comments) ? $begin_comment_code . trim($unserialized_code) . $end_comment_code : trim($unserialized_code);
                }
                else
                {
                    // register error
                    $app->enqueueMessage(JText::_('COM_MULTICACHE_ERROR_PAGECSS_GROUP_NOT_HREF_CODE_EMPTY_ROOT_SCRIPT_DETECT_ERROR'), 'warning');
                    Return false;
                }
            }
        }
        
        Return serialize($code_string);
    
    }

    protected function setSocialIndicators($js_array)
    {
        // get all social indicators
        $social_indicators = json_decode($this->getParam(0)->social_script_identifiers);
        foreach ($js_array as $key => $js)
        {
            foreach ($social_indicators as $social_indicator)
            {
                $social_indicator = trim($social_indicator);
                if (strpos($js['src'], $social_indicator) || strpos($js['code'], $social_indicator))
                {
                    $js_array[$key]['social'] = 1;
                }
            }
        }
        
        Return $js_array;
    
    }

    protected function setAdvertisementIndicators($js_array)
    {
        // get all social indicators
        $advertisement_indicators = json_decode($this->getParam(0)->advertisement_script_identifiers);
        foreach ($js_array as $key => $js)
        {
            foreach ($advertisement_indicators as $advertisement_indicator)
            {
                $advertisement_indicator = trim($advertisement_indicator);
                if (strpos($js['src'], $advertisement_indicator) || strpos($js['code'], $advertisement_indicator))
                {
                    $js_array[$key]['advertisement'] = 1;
                }
            }
        }
        
        Return $js_array;
    
    }

    protected function findSource($m, $k = null)
    {

        $search = "#src=['\"]([^\"']*)[\"'][>\s]#";
        preg_match($search, $m, $source_match);
        
        Return trim($source_match[1]);
    
    }

    /*
     * PHP 5.5
     * protected function findCode($m, $k = null)
     * {
     * $search = "#>(.*)$#";
     * preg_match($search . s, $m, $source_match);
     * $code = empty(trim($source_match[1])) ? false : trim($source_match[1]);
     * Return $code;
     * }
     * in 5.3 produces error cannot write to return value
     */
    protected function findCode($m, $k = null)
    {

        $search = "#>(.*)$#";
        preg_match($search . s, $m, $source_match);
        
        // php-issue
        $source_match_p = trim($source_match[1]);
        $code = empty($source_match_p) ? false : $source_match_p;
        
        Return $code;
    
    }

    protected function findAsync($m, $k = null)
    {

        $search = "#\sasync[^>]*>#";
        $mflag = preg_match($search, $m, $source_match);
        
        Return $mflag;
    
    }

    protected function getAllScripts()
    {

        $page_body = self::$_scraped_page_content;
        // $search = "~<script((?s:(?!</script).)*)<\/script>~"; This is a good basic regex
        
        $search = '~(?><?[^<]*+(?:<!--(?>-?[^-]*+)*?-->)?)*?\\K(?:(?:<script(?= (?> [^\\s>]*+[\\s] (?(?=type= )type=["\']?(?:text|application)/javascript ) )*+ [^\\s>]*+> )(?:(?> [^\\s>]*+\\s )+? (?>src)=["\']?( (?<!["\']) [^\\s>]*+| (?<!\') [^"]*+ | [^\']*+ ))?[^>]*+>( (?> <?[^<]*+ )*? )</script>)|\\K$)~six';
        // search2 has a look ahead that hopefully with a script in code tag will dtect and complete the entire script: courtesy stephen clay
        // preg_match_all($search, $page_body, $matches);
        preg_match_all($search, $page_body, $matches);
        
        $jsarray = array();
        
        foreach ($matches[0] as $key => $match)
        {
            if (empty($match))
            {
                continue;
            }
            $jscode = new stdClass();
            // $source_link = $this->findSource($match, $key);
            $source_link = ! empty($matches[1][$key]) ? $matches[1][$key] : '';
            $source_link_clean = null; // initiating source link
            $source_link_cmp = strtolower($source_link);
            // removing query and fragments from the source url
            
            // we cant do a string to lower here. If the source has a link in uppercase a 404 will be issued
            // lets make a comparitive
            // $code = $this->findCode($match, $key);
            $code = ! empty($matches[2][$key]) ? $matches[2][$key] : '';
            $async = $this->findAsync($match, $key);
            $absolute_link = null;
            /*
             * store contents of the script to implement remove, replace or alias methods
             *
             */
            $serialized = serialize($match);
            $serialized_code = (bool) $code ? serialize($code) : NULL;
            
            if (strstr($source_link_cmp, 'com_multicache/assets/js/conduit'))
            {
                // continue;//only required for hard coded conduit
            }
            if (! empty($source_link_cmp) && strpos($source_link_cmp, '//') === 0)
            {
                $host = JURI::getInstance()->getHost();
                // check whether the uri contains the host name
                if (! strstr(strtolower($source_link_cmp), strtolower($host)))
                {
                    $internal = false;
                }
                elseif (strpos(strtolower($source_link_cmp), strtolower(host)) === 2)
                {
                    $internal = true;
                }
                else
                {
                    // we treat all remainder urls as external. This will avoid grouping in doubtful cases
                    $internal = false;
                }
            }
            elseif (! empty($source_link_cmp))
            {
                
                $internal = JURI::isInternal($source_link);
            }
            else
            {
                $internal = null;
            }
            // moderator for certain cases
            if ($internal === false && ! empty($source_link_cmp) && strpos(strtolower($source_link_cmp), strtolower(substr(JURI::root(), 0, - 1))) === 0)
            {
                $internal = true;
            }
            // additional checks for internal
            // type1:https://domain.com
            // type2://domain.com
           
            if (! $internal)
            {
            	
                $type1 = 'https://' . JURI::getInstance()->getHost();
                $type2 = '//' . JURI::getInstance()->getHost();
                $type3 = 'http://' . JURI::getInstance()->getHost();
                if (strpos($source_link_cmp, $type1) === 0 || strpos($source_link_cmp, $type2) === 0 || strpos($source_link_cmp, $type3) === 0)
                {
                    $internal = true;
                }
                //suddenly Joomla stopped classifications for internal
                elseif((strpos($source_link_cmp , '/') === 0
                		||preg_match('~^[a-zA-Z]~six', $source_link_cmp))
                		&& strpos($source_link_cmp ,'//') !==0
                		&& strpos($source_link_cmp ,'https://') !==0
                		&& strpos($source_link_cmp ,'http://') !==0
                		&& strpos($source_link_cmp ,'data') !==0/*treating data as external as we dont know whats hexed*/
                		
                		)
                		
                {
                	$internal = true;
                }
            }
            // end additional checks for internal
            
            if ($internal)
            {
                $host = JURI::getInstance()->getHost();
                $scheme = JURI::getInstance()->getScheme();
                $uri_object = JURI::getInstance($source_link);
                $source_uri = $uri_object->toString(array(
                    'scheme',
                    'host',
                    'path'
                ));
                
                /*
                 * $unfeathered_root = strtolower(str_replace(array(
                 * "https://",
                 * "http://",
                 * "//",
                 * "/"
                 * ), "", JURI::root()));
                 */
                
                if (! stristr($source_link_cmp, $host))
                {
                    if (strpos($source_link_cmp, '/') === 0 /*substr($source_link, 0, 1) == '/'*/)
                    {
                        $absolute_link = $scheme . '://' . $host . $source_uri;
                        
                        // $absolute_link = strtolower(substr(JURI::root(), 0, - 1)) . $source_link;//issues in folder loaded installations
                    }
                    else
                    {
                        $absolute_link = $scheme . '://' . $host . '/' . $source_uri;
                        
                        // $absolute_link = strtolower(JURI::root()) . $source_link;
                    }
                }
                else // added feb 20th making absolute links for absolute internal links just to standardize.look out for issues
                {
                    $absolute_link = $source_uri;
                }
            }
            else
            {
                $absolute_link = null;
            }
            // lets make an alt_signature for internal scripts as Joomla has a habit of either adding or removing a /
            if ($internal && (bool) $source_link && stripos($source_link_cmp, 'http') !== 0 && strpos($source_link_cmp, '//') !== 0)
            {
                
                if (strpos($source_link_cmp, '/') === 0)
                {
                    $search = array(
                        'src="/',
                        "src='/"
                    );
                    $replace = array(
                        'src="',
                        "src='"
                    );
                    $alt_match = str_replace($search, $replace, $match);
                }
                else
                {
                    
                    $search = array(
                        'src="',
                        "src='"
                    );
                    $replace = array(
                        'src="/',
                        "src='/"
                    );
                    $alt_match = str_replace($search, $replace, $match);
                    $alt_serialized = serialize($alt_match);
                    $alt_signature = md5($alt_serialized);
                }
                $alt_serialized = serialize($alt_match);
                $alt_signature = md5($alt_serialized);
            }
            else
            {
                $alt_signature = null;
            }
            // start
            // making this code compatible with the css counterpart
            // original code
            /*
             * if (! empty($source_link))
             * {
             * $uri_object = JURI::getInstance($source_link);
             * $source_link_clean = $uri_object->toString(array(
             * 'scheme',
             * 'host',
             * 'path'
             * ));
             * }
             */
            // substitute code
            if (! empty($source_link))
            {
                
                // workaround for //
                $t_flag = false;
                $js_source_link_t = $source_link;
                if (strpos($js_source_link_t, '//') === 0)
                {
                    $js_source_link_t = "http:" . $js_source_link_t;
                    $t_flag = true;
                }
                $uri_object = JURI::getInstance($js_source_link_t);
                $source_link_clean = $uri_object->toString(array(
                    'scheme',
                    'host',
                    'path'
                ));
                if (! empty($t_flag))
                {
                    $source_link_clean = substr($source_link_clean, 5);
                }
            }
            
            // end of sub
            // its not right to judge an external link by our scheme hence we leave them as it is for //
            /*
             * if (! $internal && strpos($source_link_clean, '//') === 0)
             * {
             * $source_link_clean = JURI::getInstance()->getScheme() . '://' . substr($source_link_clean, 2);
             * }
             */
            
            // stop
            $jsarray[$key] = array(
                "src" => $source_link,
                "src_clean" => $source_link_clean,
                "code" => $code,
                "async" => $async,
                "serialized" => $serialized,
                "signature" => md5($serialized),
                "alt_signature" => $alt_signature,
                "rank" => $key,
                "quoted" => preg_quote($match),
                "library" => null,
                "social" => null,
                "advertisement" => null,
                "loadsection" => 0,
                "preceedence" => true,
                "serialized_code" => $serialized_code,
                "internal" => $internal,
                "absolute_src" => $absolute_link,
                "delay" => null,
                "delay_type" => null
            );
        }
        
        Return $jsarray;
    
    }
    // start
    protected function findSpecialCssIdentifiers($sub_match)
    {

        if (empty($this->css_special_identifiers))
        {
            $css_special_identifiers = $this->getRecentValue("css_special_identifiers");
            $this->css_special_identifiers = preg_split('/[\s,\n]+/', $css_special_identifiers);
            if (empty($css_special_identifiers))
            {
                $this->css_special_identifiers = json_decode($this->getParam(0)->css_special_identifiers);
            }
        }
        if (empty($this->css_special_identifiers))
        {
            Return null;
        }
        $this->css_special_identifiers = array_filter($this->css_special_identifiers);
        $identifiers = array();
        foreach ($this->css_special_identifiers as $css_identifier)
        {
            $ss = "#" . $css_identifier . "=(?(?=[\"\'])(?:[\"\']([^\"\']+))|(\w+))#i";
            
            preg_match($ss, $sub_match, $attributes);
            $identifiers[] = $attributes;
            
            // preg_match('#media=(?(?=["\'])(?:["\']([^"\']+))|(\w+))#i', $sub_match, $identifiers2);
        }
        if (empty($identifiers))
        {
            Return null;
        }
        $identifiers = array_filter($identifiers);
        Return $identifiers;
    
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

    protected function getPreviousCssKey($key, $cssarray)
    {

        $key --;
        while ($key >= 0)
        {
            if (isset($cssarray[$key]) && ! empty($cssarray[$key]["grouping"]))
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

    protected function getNextCssKey($key, $cssarray)
    {

        if (empty($cssarray))
        {
            Return false;
        }
        $max_key = max(array_keys($cssarray));
        $key ++;
        while ($key <= $max_key)
        {
            if (isset($cssarray[$key]) && ! empty($cssarray[$key]["grouping"]))
            {
                Return $key;
            }
            $key ++;
        }
        Return false;
    
    }

    protected function getAllCss()
    {

        $page_body = self::$_css_scraped_page_content;
        
        $search = '~(?><?[^<]*+(?:<!--(?>-?[^-]*+)*?-->)?)*?\\K(?:(?:<link(?= (?>[^\\s>]*+[\\s] (?!(?:itemprop|disabled|type=(?!  ["\']?text/css)|rel=(?!["\']?stylesheet))))*+[^\\s>]*+>)(?>[^\\s>]*+\\s)+?(?>href)=["\']?((?<!["\'])[^\\s>]*+|(?<!\')[^"]*+| [^\']*+)[^>]*+>)|(?:<style(?:(?!(?:type=(?!["\']?text/css))|(?:scoped))[^>])*>((?><?[^<]+)*?)</style>)|\\K$)~six';
        
        preg_match_all($search, $page_body, $matches);
        $cssarray = array();
        
        foreach ($matches[0] as $key => $match)
        {
            if (empty($match))
            {
                continue;
            }
            $csscode = new stdClass();
            $css_source_link = ! empty($matches[1][$key]) ? $matches[1][$key] : null;
            $css_source_link_clean = null;
            $css_source_link_cmp = strtolower($css_source_link);
            
            // we cant do a string to lower here. If the source has a link in uppercase a 404 will be issued
            // lets make a comparitive
            $css_code = ! empty($matches[2][$key]) ? $matches[2][$key] : null;
            
            $spl_identifiers = $this->findSpecialCssIdentifiers($match);
            
            $absolute_link = null;
            
            /*
             * store contents of the script to implement remove, replace or alias methods
             *
             */
            
            $serialized = serialize($match);
            $serialized_code = isset($css_code) ? serialize($css_code) : NULL;
            
            if (! empty($css_source_link_cmp) && strpos($css_source_link_cmp, '//') === 0)
            {
                $host = JURI::getInstance()->getHost();
                // check whether the uri contains the host name
                if (! strstr($css_source_link_cmp, strtolower($host)))
                {
                    $internal = false;
                }
                elseif (strpos($css_source_link_cmp, strtolower(host)) === 2)
                {
                    $internal = true;
                }
                else
                {
                    // we treat all remainder urls as external. This will avoid grouping in doubtful cases
                    $internal = false;
                }
            }
            elseif (! empty($css_source_link_cmp))
            {
                
                $internal = JURI::isInternal($css_source_link);
            }
            else
            {
                $internal = null;
            }
            // moderator for certain cases
            if ($internal === false && ! empty($css_source_link_cmp) && strpos($css_source_link_cmp, strtolower(substr(JURI::root(), 0, - 1))) === 0)
            {
                $internal = true;
            }
            // additional checks for internal
            // type1:https://domain.com
            // type2://domain.com
            if (! $internal)
            {
                $type1 = 'https://' . JURI::getInstance()->getHost();
                $type2 = '//' . JURI::getInstance()->getHost();
                $type3 = 'http://' . JURI::getInstance()->getHost();
                if (strpos($css_source_link_cmp, $type1) === 0 || strpos($css_source_link_cmp, $type2) === 0 || strpos($css_source_link_cmp, $type3) === 0)
                {
                    $internal = true;
                }
                elseif(strpos($css_source_link_cmp, '/') === 0 && strpos($css_source_link_cmp, '//') !== 0)
                {
                	$internal = true;
                }
            }
            // end additional checks for internal
            
            if ($internal)
            {
                $host = JURI::getInstance()->getHost();
                $scheme = JURI::getInstance()->getScheme();
                // create a uri object to remove queries & fragments
                $uri_object = JURI::getInstance($css_source_link);
                $css_source_uri = $uri_object->toString(array(
                    'scheme',
                    'host',
                    'path'
                ));
                
                /*
                 * $unfeathered_root = strtolower(str_replace(array(
                 * "https://",
                 * "http://",
                 * "//",
                 * "/"
                 * ), "", JURI::root()));
                 */
                
                if (! stristr($css_source_link_cmp, $host))
                {
                    if (strpos($css_source_link_cmp, '/') === 0 /*substr($source_link, 0, 1) == '/'*/)
                    {
                        $absolute_link = $scheme . '://' . $host . $css_source_uri; // $css_source_link;
                                                                                        // $absolute_link = strtolower(substr(JURI::root(), 0, - 1)) . $source_link;//issues in folder loaded installations
                    }
                    else
                    {
                        $absolute_link = $scheme . '://' . $host . '/' . $css_source_uri; // $css_source_link;
                                                                                              // $absolute_link = strtolower(JURI::root()) . $source_link;
                    }
                }
                else // added feb 20th making absolute links for absolute internal links just to standardize.look out for issues
                {
                    $absolute_link = $css_source_uri; // $css_source_link;
                }
            }
            else
            {
                $absolute_link = null;
            }
            // lets make an alt_signature for internal scripts as Joomla has a habit of either adding or removing a /
            if ($internal && (bool) $css_source_link && stripos($css_source_link_cmp, 'http') !== 0 && strpos($css_source_link_cmp, '//') !== 0)
            {
                
                if (strpos($css_source_link_cmp, '/') === 0)
                {
                    $search = array(
                        'href="/',
                        "href='/"
                    );
                    $replace = array(
                        'href="',
                        "href='"
                    );
                    $alt_match = str_replace($search, $replace, $match);
                }
                else
                {
                    
                    $search = array(
                        'href="',
                        "href='"
                    );
                    $replace = array(
                        'href="/',
                        "href='/"
                    );
                    $alt_match = str_replace($search, $replace, $match);
                    $alt_serialized = serialize($alt_match);
                    $alt_signature = md5($alt_serialized);
                }
                $alt_serialized = serialize($alt_match);
                $alt_signature = md5($alt_serialized);
            }
            else
            {
                $alt_signature = null;
            }
            
            // removing query and fragments from the source url
            if (! empty($css_source_link))
            {
                
                // workaround for //
                $t_flag = false;
                $css_source_link_t = $css_source_link;
                if (strpos($css_source_link_t, '//') === 0)
                {
                    $css_source_link_t = "http:" . $css_source_link_t;
                    $t_flag = true;
                }
                $uri_object = JURI::getInstance($css_source_link_t);
                $css_source_link_clean = $uri_object->toString(array(
                    'scheme',
                    'host',
                    'path'
                ));
                if (! empty($t_flag))
                {
                    $css_source_link_clean = substr($css_source_link_clean, 5);
                }
            }
            /*
             * Technically we cant judge the external uri scheme by our internal settings so lets maintain this as //
             * if (! $internal && strpos($css_source_link_clean, '//') === 0)
             * {
             * $css_source_link_clean = JURI::getInstance()->getScheme() . '://' . substr($css_source_link_clean, 2);
             * }
             */
            $cssarray[$key] = array(
                "href" => $css_source_link,
                "href_clean" => $css_source_link_clean,
                "code" => $css_code,
                "serialized" => $serialized,
                "signature" => md5($serialized),
                "alt_signature" => $alt_signature,
                "rank" => $key,
                "quoted" => preg_quote($match),
                "attributes" => $spl_identifiers,
                "loadsection" => 0,
                "preceedence" => true,
                "serialized_code" => $serialized_code,
                "internal" => $internal,
                "absolute_src" => $absolute_link,
                "delay" => null,
                "delay_type" => null
            );
        }
        
        Return $cssarray;
    
    }
    // start
    protected function performCssOptimization($table, $css_exclude_object = null, $img_exclude_object = null, $params_lazyload = null , $params = null)
    {

        $app = JFactory::getApplication();
        $css_array = $this->prepareNonTableCssElements();
        $spl_condition = null;
        if (property_exists('MulticachePageCss', 'delayed'))
        {
            $del_flg = MulticachePageCss::$delayed;
            if (! empty($del_flg) && property_exists('MulticachePageCss', 'working_css_array'))
            {
                $wsa = MulticachePageCss::$working_css_array;
                if (empty($wsa))
                {
                    $spl_condition = true;
                }
            }
        }
        
        if (empty($css_array) && ! isset($spl_condition))
        {
            $app->enqueueMessage(JText::_('COM_MULTICACHE_ERROR_PAGESCSS_PREPARENONTABLEELEMENTS_EMPTY'), 'warning');
            Return false;
        }
        // ignore's ignore behaviour will treat css as orphaned and load
        $css_array = $this->setCssIgnore($css_array);
        // dont loads - dont loads behaviour will remove the css if present
        $css_array = $this->setDontLoadCss($css_array);
        // store cdns and their sigs
        $css_array = $this->setCDNtosignatureCss($css_array);
        // mark duplicates
        $css_array = $this->setSignatureHashCss($css_array);
        if ($table->css_maintain_preceedence)
        {
            // iterating through a jsarray here to avoid code duplication
            // ensures preceedence does not moderate the default loadsections
            $css_array = $this->performPreceedenceModeration($css_array);
        }
        /* DUPLICATE HANDLERS */
        // to be tested
        if (! $table->dedupe_css_styles)
        {
            $css_array = $this->placeDuplicatesBack($css_array, 'css', 'MulticachePageCss');
        }
        if ($table->dedupe_css_styles)
        {
            $css_array = $this->removeDuplicateCss($css_array);
        }
        // mark for delay css. Prepare the delayable object.
        // institutes the $_delayable_segment_css object
        $css_array = $this->prepareDelayableCss($css_array, $table);
        
        MulticacheHelper::storePageCss(null, $css_array, self::$_duplicates_css, self::$_delayable_segment_css);
        // correction for 2nd time flow
        $this->correctSignatureHashCss(self::$_duplicates_css);
        $this->correctDelaySignatureHashCss(self::$_delayable_segment_css);
        $css_array = $this->moderateDefaultLoadsections($css_array);
        
        if ($table->group_css_styles)
        {
            /*
             * in css we will attempt to group everything.
             * If somethings is not to be grouped it should be explicitely indicated.
             * css groups are distinguioshed by loadsection primarily
             * css groups are distinguished by group number secondarily.
             */
            
            $css_array = $this->assignCssGroups($css_array, $table);
            $this->initialiseCssGroupHash($css_array);
            $this->combineCssGroupCode($table);
            $this->prepareCssGrouploadableUrl();
            $this->writeGroupCssCode($table);
        }
        $this->makeCssDelaycode($params);
        $this->segregatePlaceCssDelay($table);
        
        $this->prepareCssLoadsections($css_array, $table , $params);
        $this->correctCssLoadsectionAturl();
        // if js tweaks is off then we will combine delay to css elements
        // delay is performed though scripts hence if js tweaks is on it is better to perform
        // the delay at the lag end before prepare cache strategy. This enables delay scripts to align to normal scripts and
        // prevents blocking css elements
        if (! $table->js_switch)
        {
            $this->combineCssDelay($params);
            $stubs = MulticacheHelper::prepareStubs($table);
            MulticacheHelper::writeJsCacheStrategy(null, null, null, $stubs, null, self::$_signature_hash_css, self::$_loadsections_css, $table->css_switch, $css_exclude_object, $table->image_lazy_switch, $img_exclude_object, $params_lazyload, null);
        }
    
    }
    // stop
    protected function correctCssLoadsectionAturl()
    {

        if (empty(self::$_atimports_prop))
        {
            Return;
        }
        foreach (self::$_atimports_prop as $impurls)
        {
            $links .= MulticacheHelper::getCsslinkUrl($impurls, 'plain_url');
        }
        // if loadsection 1 is empty put in 2
        $load_content = '';
        if (empty(self::$_loadsections_css[1]))
        {
            $load_content = ! empty(self::$_loadsections_css[2]) ? $links . unserialize(self::$_loadsections_css[2]) : $links;
            self::$_loadsections_css[2] = serialize($load_content);
        }
        else
        {
            $load_content = $links . unserialize(self::$_loadsections_css[1]);
            self::$_loadsections_css[1] = serialize($load_content);
        }
    
    }

    protected function clean_stub($stub_array)
    {

        $stub_array = array_filter($stub_array);
        $ret_array = array();
        foreach ($stub_array as $key => $stub)
        {
            $ret_array[] = trim($stub) . ">";
        }
        Return $ret_array;
    
    }

    protected function setprincipleJqueryscopeoperator($table)
    {

        $app = JFactory::getApplication();
        if (isset($table->principle_jquery_scope) && $table->principle_jquery_scope == 0)
        {
            self::$_principle_jquery_scope = "jQuery";
        }
        elseif (isset($table->principle_jquery_scope) && $table->principle_jquery_scope == 1)
        {
            self::$_principle_jquery_scope = "$";
        }
        elseif (isset($table->principle_jquery_scope) && $table->principle_jquery_scope == 2)
        {
            if (! empty($table->principle_jquery_scope_other))
            {
                self::$_principle_jquery_scope = trim($table->principle_jquery_scope_other);
            }
            else
            {
                $app->enqueueMessage(JText::_('COM_MULTICACHE_CLASS_MULTICACHE_PAGE_SCRIPTS_JQUERY_SCOPE_NOT_DEFINED'), 'warning');
                $app->redirect('index.php?option=com_multicache&view=config&layout=edit&id=1#page-js-tweaks');
            }
        }
        else
        {
            $app->enqueueMessage(JText::_('COM_MULTICACHE_CLASS_MULTICACHE_PAGE_SCRIPTS_JQUERY_SCOPE_ERROR'), 'error');
            $app->redirect('index.php?option=com_multicache&view=config&layout=edit&id=1#page-js-tweaks');
        }
    
    }

    protected function makeExcludedComponentslist()
    {

        $app = JFactory::getApplication();
        $serialized = unserialize($app->input->post->serialize());
        array_walk_recursive($serialized, 'self::filterExcludedComponents');
        self::$_excluded_components = array_filter(self::$_excluded_components);
        self::$_excluded_components_css = array_filter(self::$_excluded_components_css);
        self::$_excluded_components_img = array_filter(self::$_excluded_components_img);
    
    }

    protected static function filterExcludedComponents($item, $key)
    {

        if (strstr($key, 'com_multicache_component_exclusions_'))
        {
            $key = str_replace('com_multicache_component_exclusions_', '', $key);
            self::$_excluded_components[$key] = $item;
        }
        
        if (strstr($key, 'com_multicache_css_component_exclusions'))
        {
            
            $key = str_replace('com_multicache_css_component_exclusions_', '', $key);
            
            self::$_excluded_components_css[$key] = $item;
        }
        
        if (strstr($key, 'com_multicache_img_component_exclusions'))
        {
            
            $key = str_replace('com_multicache_img_component_exclusions_', '', $key);
            
            self::$_excluded_components_img[$key] = $item;
        }
    
    }

    /**
     * Prepare and sanitise the table prior to saving.
     *
     * @since 1.6
     */
    protected function prepareTable($table)
    {

        $app = JFactory::getApplication();
        $params = array();
        
        $last_test = $this->getLastTest();
        
        if (! empty($last_test) && $table->gtmetrix_api_budget != $last_test->max_tests)
        {
            $this->resetMaxTests($table->gtmetrix_api_budget, $last_test->id);
        }
        $last_testgroup = $this->getlastTestGroup();
        if (! empty($last_testgroup) && $table->gtmetrix_cycles != $last_testgroup->cycles)
        {
            if ($last_testgroup->cycles_complete < $table->gtmetrix_cycles)
            {
                $this->resetGroupCycles($table, $last_testgroup->id);
            }
            else
            {
                $app->enqueueMessage(JText::_('COM_MULTICACHE_CONFIG_RESETCYCLES_CANNOT_COMPLETE_NOW_ERROR'), 'error');
            }
        }
        
        $table->simulation_advanced = ! isset($table->simulation_advanced) ? 0 : 1;
        
        $bypass = 1;
        $sim_lock = json_decode(JPluginHelper::getPlugin('system', 'multicache')->params)->lock_sim_control;
        if (! empty($sim_lock))
        {
            $bypass = 0;
            if (empty($table->advanced_simulation_lock) && ! empty($table->simulation_advanced))
            {
                $bypass = 1;
                $app->enqueueMessage(JText::_('COM_MULTICACHE_CONFIG_DEVOLVING_CHANGES_TORUNNINGADVTEST'));
            }
        }
        $debug_mode = ! empty($table->debug_mode) ? serialize($table->default_scrape_url) : null;
        //ver1.0.1.1 moved from below
        $css_groupsasync = $this->getRecentValue('css_groupsasync');
        MulticacheHelper::setJsSwitch($table->js_switch, $table->conduit_switch, $table->gtmetrix_testing, $table->simulation_advanced, $table->js_comments, $debug_mode, $table->orphaned_scripts, $table->css_switch, $table->css_comments, $table->compress_css, $table->minify_html, $table->compress_js, $table->orphaned_styles_loading, $table->image_lazy_switch , $css_groupsasync);
        
        if (! isset(self::$_principle_jquery_scope))
        {
            $this->setprincipleJqueryscopeoperator($table);
        }
        
        if (! isset(self::$_jscomments))
        {
            self::$_jscomments = $table->js_comments;
        }
        if (! isset(self::$_css_comments))
        {
            self::$_css_comments = $table->css_comments;
        }
        self::$_mediaVersion = MulticacheHelper::getMediaFormat();
        if (empty($table->id))
        {
            $jinput = JFactory::getApplication()->input;
            if (empty($jinput))
            {
                $table->id = $jinput->getInt('id');
            }
            else
            {
                $table->id = 1;
            }
        }
        // basic requirements for testing
        if ($table->gtmetrix_testing)
        {
            if (empty($table->gtmetrix_email))
            {
                $app->enqueueMessage(JText::_('COM_MULTICACHE_CONFIG_GTMETRIX_EMAIL_ABSENT'), 'notice');
            }
            if (empty($table->gtmetrix_token))
            {
                $app->enqueueMessage(JText::_('COM_MULTICACHE_CONFIG_GTMETRIX_TOKEN_ABSENT'), 'notice');
            }
        }
        /*
         * place for template vary
         */
        
        if ($table->gtmetrix_allow_simulation)
        {
            
            $base_url = strtolower(substr(JURI::root(), 0, - 1));
            $test_url = strtolower($table->gtmetrix_test_url);
            $same_domain = stristr($test_url, $base_url);
            if ($test_url == $base_url)
            {
                $table->gtmetrix_test_url = strtolower(JURI::root());
            }
            if (! $same_domain)
            {
                $table->gtmetrix_allow_simulation = 0;
                $app->enqueueMessage(JText::_('COM_MULTICACHE_URL_DIFFERS_FROM_DOMAIN_SIMULATION_TURNED_OFF'), 'error');
            }
        }
        
        if (substr($table->googleviewid, 0, 3) != "ga:" && preg_match('/^[0-9]*$/', $table->googleviewid, $match))
        {
            
            $table->googleviewid = "ga:" . $table->googleviewid;
        }
        
        if (! empty($table->social_script_identifiers))
        {
            $social_script_raw = $table->social_script_identifiers;
            $social_script_array = preg_split('/[\s,\n]+/', $social_script_raw);
            $table->social_script_identifiers = json_encode($social_script_array);
        }
        if (! empty($table->advertisement_script_identifiers))
        {
            $advertisement_script_raw = $table->advertisement_script_identifiers;
            $advertisement_script_array = preg_split('/[\s,\n]+/', $advertisement_script_raw);
            $table->advertisement_script_identifiers = json_encode($advertisement_script_array);
        }
        
        if (! empty($table->pre_head_stub_identifiers))
        {
            $pre_head_stub_identifier_raw = $table->pre_head_stub_identifiers;
            // $pre_head_stub_identifier_array = preg_split('/>/'.iUs , $pre_head_stub_identifier_raw ,PREG_SPLIT_DELIM_CAPTURE);
            $pre_head_stub_identifier_array = $this->clean_stub(explode('>', $pre_head_stub_identifier_raw));
            // $pre_head_stub_identifier_array = $this->clean_stub($pre_head_stub_identifier_array);
            
            $table->pre_head_stub_identifiers = json_encode($pre_head_stub_identifier_array);
        }
        if (! empty($table->head_stub_identifiers))
        {
            $head_stub_identifier_raw = $table->head_stub_identifiers;
            // $head_stub_identifier_array = preg_split('/[\s,\n]+/' , $head_stub_identifier_raw);
            $head_stub_identifier_array = $this->clean_stub(explode('>', $head_stub_identifier_raw));
            $table->head_stub_identifiers = json_encode($head_stub_identifier_array);
        }
        if (! empty($table->body_stub_identifiers))
        {
            $body_stub_identifier_raw = $table->body_stub_identifiers;
            // $body_stub_identifier_array = preg_split('/[\s,\n]+/' , $body_stub_identifier_raw);
            $body_stub_identifier_array = $this->clean_stub(explode('>', $body_stub_identifier_raw));
            $table->body_stub_identifiers = json_encode($body_stub_identifier_array);
        }
        if (! empty($table->footer_stub_identifiers))
        {
            $footer_stub_identifiers_raw = $table->footer_stub_identifiers;
            // $footer_stub_identifier_array = preg_split('/[\s,\n]+/' , $footer_stub_identifiers_raw);
            $footer_stub_identifier_array = $this->clean_stub(explode('>', $footer_stub_identifiers_raw));
            $table->footer_stub_identifiers = json_encode($footer_stub_identifier_array);
        }
        
        if (! empty($table->additionalpagecacheurls))
        {
            $base_url = strtolower(str_ireplace(array(
                'http://',
                'www.'
            ), '', substr(JURI::root(), 0, - 1)));
            $urls_raw = $table->additionalpagecacheurls;
            $url_array = preg_split('/[\s,\n]+/', $urls_raw);
            foreach ($url_array as $key => $url)
            {
                $exists = $this->checkUrldburlArray($url, 'google');
                if ($exists || ! stristr($url, $base_url))
                {
                    unset($url_array[$key]);
                }
            }
            $this->clearTable();
            $url_string = json_encode($url_array); // this is stored to config
            
            foreach ($url_array as $key => $url)
            {
                $exists = $this->checkUrldburlArray($url, 'manual');
                
                if (! $exists && stristr($url, $base_url))
                {
                    
                    $this->storeUrlArray($url);
                }
            }
            
            $table->additionalpagecacheurls = $url_string;
        }
        // start
        if (! empty($table->css_special_identifiers))
        {
            $css_special_identifiers_raw = $table->css_special_identifiers;
            $css_special_identifiers_array = preg_split('/[\s,\n]+/', $css_special_identifiers_raw);
            $css_special_identifiers_array = MulticacheHelper::makeWinSafeArray($css_special_identifiers_array);
            $table->css_special_identifiers = json_encode($css_special_identifiers_array);
        }
        // stop
        
        if (! empty($table->cartmodeurlinclude))
        {
            $carturls_array = preg_split('/[\s,\n]+/', $table->cartmodeurlinclude);
            $carturls_array = MulticacheHelper::makeWinSafeArray($carturls_array);
            $table->cartmodeurlinclude = json_encode($carturls_array);
        }
        if (! empty($table->cartsessionvariables))
        {
            $cart_session_var_array = preg_split('/[\s\n]+/', $table->cartsessionvariables);
            $cart_session_var_array = MulticacheHelper::makeWinSafeArray($cart_session_var_array);
            $table->cartsessionvariables = json_encode($cart_session_var_array);
        }
        
        if (! empty($table->cartdifferentiators))
        {
            $cart_diff_var_array = preg_split('/[\s\n,]+/', $table->cartdifferentiators);
            $cart_diff_var_array = MulticacheHelper::makeWinSafeArray($cart_diff_var_array);
            $table->cartdifferentiators = json_encode($cart_diff_var_array);
        }
        
        MulticacheHelper::prepareCartObject($carturls_array, $cart_session_var_array, $cart_diff_var_array, $table->cartmode, $table->multicachedistribution);
        if (! empty($table->jst_urlinclude))
        {
            $jst_urlinclude = preg_split('/[\s\n,]+/', $table->jst_urlinclude);
            $jst_urlinclude = ! empty($jst_urlinclude) ? array_filter($jst_urlinclude) : $jst_urlinclude;
            $jst_urlinclude = MulticacheHelper::makeWinSafeArray($jst_urlinclude);
            $table->jst_urlinclude = json_encode($jst_urlinclude);
        }
        if (! empty($table->jst_query_param))
        {
            $jst_query_param = preg_split('/[\s\n,]+/', $table->jst_query_param);
            $jst_query_param = ! empty($jst_query_param) ? array_filter($jst_query_param) : $jst_query_param;
            $jst_query_param = MulticacheHelper::makeWinSafeArray($jst_query_param);
            $table->jst_query_param = json_encode($jst_query_param);
        }
        if (! empty($table->jst_url_string))
        {
            $jst_url_string = preg_split('/[\s\n,]+/', $table->jst_url_string);
            $jst_url_string = ! empty($jst_url_string) ? array_filter($jst_url_string) : $jst_url_string;
            $jst_url_string = MulticacheHelper::makeWinSafeArray($jst_url_string);
            $table->jst_url_string = json_encode($jst_url_string);
        }
        // css start
        if (! empty($table->css_urlinclude))
        {
            $css_urlinclude = preg_split('/[\s\n,]+/', $table->css_urlinclude);
            $css_urlinclude = ! empty($css_urlinclude) ? array_filter($css_urlinclude) : $css_urlinclude;
            $css_urlinclude = MulticacheHelper::makeWinSafeArray($css_urlinclude);
            $table->css_urlinclude = json_encode($css_urlinclude);
        }
        if (! empty($table->css_query_param))
        {
            $css_query_param = preg_split('/[\s\n,]+/', $table->css_query_param);
            $css_query_param = ! empty($css_query_param) ? array_filter($css_query_param) : $css_query_param;
            $css_query_param = MulticacheHelper::makeWinSafeArray($css_query_param);
            $table->css_query_param = json_encode($css_query_param);
        }
        if (! empty($table->css_url_string))
        {
            $css_url_string = preg_split('/[\s\n,]+/', $table->css_url_string);
            $css_url_string = ! empty($css_url_string) ? array_filter($css_url_string) : $css_url_string;
            $css_url_string = MulticacheHelper::makeWinSafeArray($css_url_string);
            $table->css_url_string = json_encode($css_url_string);
        }
        // css end
        // img start
        if (! empty($table->images_urlinclude))
        {
            $img_urlinclude = preg_split('/[\s\n,]+/', $table->images_urlinclude);
            $img_urlinclude = ! empty($img_urlinclude) ? array_filter($img_urlinclude) : $img_urlinclude;
            $img_urlinclude = MulticacheHelper::makeWinSafeArray($img_urlinclude);
            $table->images_urlinclude = json_encode($img_urlinclude);
        }
        if (! empty($table->images_query_param))
        {
            $img_query_param = preg_split('/[\s\n,]+/', $table->images_query_param);
            $img_query_param = ! empty($img_query_param) ? array_filter($img_query_param) : $img_query_param;
            $img_query_param = MulticacheHelper::makeWinSafeArray($img_query_param);
            $table->images_query_param = json_encode($img_query_param);
        }
        if (! empty($table->images_url_string))
        {
            $img_url_string = preg_split('/[\s\n,]+/', $table->images_url_string);
            $img_url_string = ! empty($img_url_string) ? array_filter($img_url_string) : $img_url_string;
            $img_url_string = MulticacheHelper::makeWinSafeArray($img_url_string);
            $table->images_url_string = json_encode($img_url_string);
        }
        // img end
        // img params begin
        if (! empty($table->image_lazy_container_strings))
        {
            // this will only accept newline to differentiate
            $image_lazy_container_strings = preg_split('/[\n]+/', $table->image_lazy_container_strings);
            $image_lazy_container_strings = ! empty($image_lazy_container_strings) ? array_filter($image_lazy_container_strings) : $image_lazy_container_strings;
            $image_lazy_container_strings = MulticacheHelper::makeWinSafeArray($image_lazy_container_strings);
            $table->image_lazy_container_strings = json_encode($image_lazy_container_strings);
        }
        if (! empty($table->image_lazy_image_selector_include_strings))
        {
            // this will only accept newline to differentiate
            $image_lazy_image_selector_include_strings = preg_split('/[\n]+/', $table->image_lazy_image_selector_include_strings);
            $image_lazy_image_selector_include_strings = ! empty($image_lazy_image_selector_include_strings) ? array_filter($image_lazy_image_selector_include_strings) : $image_lazy_image_selector_include_strings;
            $image_lazy_image_selector_include_strings = MulticacheHelper::makeWinSafeArray($image_lazy_image_selector_include_strings);
            $table->image_lazy_image_selector_include_strings = json_encode($image_lazy_image_selector_include_strings);
        }
        if (! empty($table->image_lazy_image_selector_exclude_strings))
        {
            // this will only accept newline to differentiate
            $image_lazy_image_selector_exclude_strings = preg_split('/[\n]+/', $table->image_lazy_image_selector_exclude_strings);
            $image_lazy_image_selector_exclude_strings = ! empty($image_lazy_image_selector_exclude_strings) ? array_filter($image_lazy_image_selector_exclude_strings) : $image_lazy_image_selector_exclude_strings;
            $image_lazy_image_selector_exclude_strings = MulticacheHelper::makeWinSafeArray($image_lazy_image_selector_exclude_strings);
            $table->image_lazy_image_selector_exclude_strings = json_encode($image_lazy_image_selector_exclude_strings);
        }
        $img_script_lazy = MulticacheHelper::prepareScriptLazy(self::$_principle_jquery_scope);
        $img_style_lazy = MulticacheHelper::prepareStylelazy();
        $params_lazyload = MulticacheHelper::prepareLazyloadParams($table, $img_script_lazy, $img_style_lazy);
        // img params end
        // positional urls dont move
        $p_dontmove_form = $this->getRecentValue('positional_dontmovesrc'); // we dint bind this to table so we need to retreive the value from form
        if (! empty($p_dontmove_form))
        {
            // this will only accept newline to differentiate
            $positional_urls = preg_split('/[\n]+/', $p_dontmove_form);
            $positional_urls = ! empty($positional_urls) ? array_filter($positional_urls) : $positional_urls;
            if (! empty($positional_urls))
            {
                $positional_urls = MulticacheHelper::checkPositionalUrls($positional_urls);
            }
            // $table->image_lazy_image_selector_exclude_strings = json_encode($image_lazy_image_selector_exclude_strings);
        }
        $resultant_async = $this->getRecentValue('resultant_async');
        $resultant_defer = $this->getRecentValue('resultant_defer');
        //ver1.0.1.1
        //moved above js switch
       // $css_groupsasync = $this->getRecentValue('css_groupsasync');
        $css_groupsasync_exclude = $this->getRecentValue('groups_async_exclude');
     if (! empty($css_groupsasync_exclude))
        {
            // this will only accept newline to differentiate
            $css_groupsasync_exclude = preg_split('/[\n]+/', $css_groupsasync_exclude);
            $css_groupsasync_exclude = ! empty($css_groupsasync_exclude) ? array_filter(array_map('trim' ,$css_groupsasync_exclude)) : $css_groupsasync_exclude;
            if (! empty($css_groupsasync_exclude))
            {
                $css_groupsasync_exclude = MulticacheHelper::checkPositionalUrls($css_groupsasync_exclude);
            }
            // $table->image_lazy_image_selector_exclude_strings = json_encode($image_lazy_image_selector_exclude_strings);
        }
        //async groups delay
        $css_groupsasync_delay = $this->getRecentValue('groups_async_delay');
        if (! empty($css_groupsasync_delay))
        {
        	// this will only accept newline to differentiate
        	$css_groupsasync_delay = preg_split('/[\n]+/', $css_groupsasync_delay);
        	if(!empty($css_groupsasync_delay))
        	{
        		$as_delay = array();
        		foreach($css_groupsasync_delay As $c_ga_delay)
        		{
        			$parts = explode(':',$c_ga_delay);
        			$parts[1] = isset($parts[1])? $parts[1]: 30;
        			$as_delay[trim($parts[0])] = trim($parts[1]); 
        		}
        		
        	}
        	$css_groupsasync_delay = ! empty($as_delay) ? array_filter(array_map('trim' ,$as_delay)) : $css_groupsasync_exclude;
        	
        	// $table->image_lazy_image_selector_exclude_strings = json_encode($image_lazy_image_selector_exclude_strings);
        }
        //end async groups delay
        // set the params vector
        $params['positional_dontmovesrc'] = $positional_urls;
        // $table->params = serialize($params);
        $params['resultant_async'] = $resultant_async;
        $params['resultant_defer'] = $resultant_defer;
        //ver1.0.1.1
        $params['css_groupsasync'] = $css_groupsasync;
        $params['groups_async_exclude'] = $css_groupsasync_exclude;
        $params['css_groupsasync_delay'] = $css_groupsasync_delay;
        // end of setting params vector
        // set the don move bits
        if (! empty($positional_urls))
        {
            self::$_dontmoveurls = array_flip($positional_urls);
        }
        
        // set the dont move src
        // start allow multiple orphaned
        $allow_multiple_orphaned = $this->getRecentValue('allow_multiple_orphaned'); // we dint bind this to table so we need to retreive the value from form
        if (! empty($allow_multiple_orphaned))
        {
            // this will only accept newline to differentiate
            $allow_multiple_orphaned_url_bits = preg_split('/[\n]+/', $allow_multiple_orphaned);
            $allow_multiple_orphaned_url_bits = ! empty($allow_multiple_orphaned_url_bits) ? array_filter($allow_multiple_orphaned_url_bits) : $allow_multiple_orphaned_url_bits;
            if (! empty($allow_multiple_orphaned_url_bits))
            {
                $allow_multiple_orphaned_url_bits = MulticacheHelper::checkPositionalUrls($allow_multiple_orphaned_url_bits);
            }
            // $table->image_lazy_image_selector_exclude_strings = json_encode($image_lazy_image_selector_exclude_strings);
        }
        // set the params vector
        $params['allow_multiple_orphaned'] = $allow_multiple_orphaned_url_bits;
        $table->params = serialize($params);
        // end of setting params vector
        // set the don move bits
        if (! empty($allow_multiple_orphaned_url_bits))
        {
            self::$_allow_multiple_orphaned = (isset($allow_multiple_orphaned_url_bits[0]) && $allow_multiple_orphaned_url_bits[0] == - 1) ? - 1 : array_flip($allow_multiple_orphaned_url_bits);
        }
        // end allow multiple orphaned
        
        /*
         * Te JSTexclude object allows for Javascript tweaker to exclude ceratin templates eg mobile templates
         * or format vary
         * urlswitch - 0 - all pages
         * urlswitch - 1 - these pages
         * urlswitch - 2 - not these pages
         * queryswitch 0 - off
         * query switch 1 - inclusion
         * query switch 2 - exclusion
         */
        $this->makeExcludedComponentslist(); // initialize the self::$_excluded_components
        $jst_exclude_object = MulticacheHelper::PrepareJSTexcludes($table->js_tweaker_url_include_exclude, $table->jst_query_include_exclude, $jst_urlinclude, $jst_query_param, self::$_excluded_components, $jst_url_string);
        $css_exclude_object = MulticacheHelper::PrepareJSTexcludes($table->css_tweaker_url_include_exclude, $table->css_query_include_exclude, $css_urlinclude, $css_query_param, self::$_excluded_components_css, $css_url_string);
        $img_exclude_object = MulticacheHelper::PrepareJSTexcludes($table->imagestweaker_url_include_exclude, $table->images_query_include_exclude, $img_urlinclude, $img_query_param, self::$_excluded_components_img, $img_url_string);
        $table->excluded_components = ! empty(self::$_excluded_components) ? serialize(self::$_excluded_components) : null;
        $table->cssexcluded_components = ! empty(self::$_excluded_components_css) ? serialize(self::$_excluded_components_css) : null;
        $table->imgexcluded_components = ! empty(self::$_excluded_components_img) ? serialize(self::$_excluded_components_img) : null;
        // a check to see that the test url in advance mode is included inspite of exclude object
        /*
         * Moved to checkConfigParams
         * if (! empty($table->gtmetrix_allow_simulation) && $table->simulation_advanced)
         * {
         * // check 1
         * if (! empty($jst_exclude_object) && isset($jst_exclude_object->urlswitch))
         * {
         * if (($jst_exclude_object->urlswitch == 1 && ! isset($jst_exclude_object->url[$table->gtmetrix_test_url])) || ($jst_exclude_object->urlswitch == 2 && isset($jst_exclude_object->url[$table->gtmetrix_test_url])))
         * {
         * $app->enqueueMessage(JText::_('COM_MULTICACHE_CONFIG_GTMETRIX_TESTURL_IS_EXCLUDED'), 'error');
         * $app->redirect('index.php?option=com_multicache&view=config&layout=edit&id=1#page-js-tweaks');
         * }
         * }
         * if (! empty($jst_exclude_object) && isset($jst_exclude_object->queryswitch))
         * {
         * $gtm_testurl_qparams = JURI::getInstance($table->gtmetrix_test_url)->getQuery(true);
         * $query_params = $jst_exclude_object->query;
         * $query_set = false;
         * if (! empty($gtm_testurl_qparams))
         * {
         * foreach ($gtm_testurl_qparams as $key => $value)
         * {
         * if (isset($jst_exclude_object->query[$key][$value]) || (isset($jst_exclude_object->query[$key]) && $jst_exclude_object->query[$key][true] == 1))
         * {
         * $query_set = true;
         * break;
         * }
         * }
         * }
         * }
         * if (($jst_exclude_object->queryswitch == 1 && ! $query_set) || ($jst_exclude_object->queryswitch == 2 && $query_set))
         * {
         * $app->enqueueMessage(JText::_('COM_MULTICACHE_CONFIG_GTMETRIX_TESTURL_IS_EXCLUDED_QUERYPARAM'), 'error');
         * $app->redirect('index.php?option=com_multicache&view=config&layout=edit&id=1#page-js-tweaks');
         * }
         * }
         */
        $this->prepare_Config($table);
        // $this->setExtensionParam($table);//suspending as we do not want to touch the sys cache plugin-> instead loading from config
        $this->prepare_JSvars($table);
        // issue $table->js_switch may contain the unsaved state of js switch. May be required to check the old state of js_switch to proceed
        // $this->getParam(0)->js_switch contains the old state
        if ($table->simulation_advanced && ! class_exists('Loadinstruction'))
        {
            
            $app->enqueueMessage(JText::_('COM_MULTICACHE_ADVANCED_SIMULATION_REQUIRES_JAVASCRIPT_TWEAKER_TO_BE_INITIALISED'), 'notice');
        }
        $stubs = MulticacheHelper::prepareStubs($table);
        if ($table->css_switch && $this->getParam(0)->css_switch)
        {
            $this->performCssOptimization($table, $css_exclude_object, $img_exclude_object, $params_lazyload , $params);
        }
        if (empty($table->css_switch) && empty($table->js_switch) && ! empty($table->image_lazy_switch))
        {
            $return = MulticacheHelper::writeJsCacheStrategy(null, null, null, $stubs, null, null, null, null, null, $table->image_lazy_switch, $img_exclude_object, $params_lazyload, null);
        }
        if ($table->js_switch && $this->getParam(0)->js_switch)
        {
            $page_new_script = $this->prepareNonTableElements();
            if (empty($page_new_script))
            {
                $app->enqueueMessage(JText::_('COM_MULTICACHE_ERROR_PAGESCRIPT_PREPARENONTABLEELEMENTS_EMPTY'), 'warning');
                Return false;
            }
            $success = $this->validatePageScript($page_new_script, $table);
            if (! $success)
            {
                Return false;
            }
            $page_new_script = $this->setIgnore($page_new_script);
            $page_new_script = $this->setDontLoad($page_new_script); // sets dontmove as well
            $page_new_script = $this->ConfirmIgnoreDontLoad($page_new_script);
            $page_new_script = $this->setCDNtosignature($page_new_script);
            $page_new_script = $this->setSignatureHash($page_new_script); // also sets duplicate flag
            
            /* NOTE IMPORTANT WE CALL TABLE->value instead of saved valiues as we want the immediate action selected versus the previous state */
            // theoretically this is where the ignore segs come in
            if ($table->maintain_preceedence)
            {
                $page_new_script = $this->performPreceedenceModeration($page_new_script);
            }
            /* DUPLICATE HANDLERS */
            if (! $table->dedupe_scripts)
            {
                $page_new_script = $this->placeDuplicatesBack($page_new_script);
            }
            if ($table->dedupe_scripts)
            {
                $page_new_script = $this->removeDuplicateScripts($page_new_script);
            }
            /* PREPARE DELAYABLE WITHOUT DELAY LAG FOR NON PRECEEDENCE */
            if (! $table->maintain_preceedence)
            {
                $page_new_script = $this->prepareDelayable($page_new_script, $table);
            }
            
            /* DEFER ADVERTISEMENTS - ads cannot be delayed unless specifically warranted */
            if ($table->defer_advertisement)
            {
                $page_new_script = $this->deferAdvertisement($page_new_script);
            }
            /* DEFER ASYNC - async cannot be delayed unless specifically waranted */
            if ($table->defer_async)
            {
                $page_new_script = $this->deferAsync($page_new_script);
            }
            /* PREPARE DELAYABLE WITH DELAY LAG FOR PRECEEDENCE */
            if ($table->maintain_preceedence)
            {
                $page_new_script = $this->prepareDelayable($page_new_script, $table);
            }
            
            /* DEFER SOCIAL */
            // A Social can be delayed heance it takes a lesser priority to delayable
            
            if ($table->defer_social)
            {
                $page_new_script = $this->deferSocial($page_new_script);
            }
            
            // INTERCEPTION POINT
            // PoINT TO NOTE always pass storePageScripts with null as first value to maintain the original array. Conversely pass original array with all other values set to null to reset.
            MulticacheHelper::storePageScripts(null, $page_new_script, self::$_duplicates, self::$_social_segment, self::$_advertisement_segment, self::$_async_segment, self::$_delayable_segment, self::$_dontmove_items); //
            $simcontrol_object = array();
            $simcontrol_object['working_script_array'] = $page_new_script;
            $simcontrol_object['social'] = self::$_social_segment;
            $simcontrol_object['advertisements'] = self::$_advertisement_segment;
            $simcontrol_object['async'] = self::$_async_segment;
            $simcontrol_object['delayable'] = self::$_delayable_segment;
            $this->correctSignatureHash(self::$_advertisement_segment); // correction for 2nd time flow
            $this->correctSignatureHash(self::$_duplicates);
            $this->correctSignatureHash(self::$_social_segment);
            $this->correctSignatureHash(self::$_async_segment);
            $this->correctDelaySignatureHash(self::$_delayable_segment);
            
            // trial:
            $page_new_script = $this->moderateDefaultLoadsections($page_new_script); // optimizing for userability
              /*as of version1.0.1.0 we need to accomadate 
               * resultant async and resultant defers to this extent
               * when these are set we wish to combine the delay callable to the group
               * code in order that we need not use async timers.
               * To this end the delay peice is performed before the minimize roundtrips
               */ 
            $this->makeDelaycode();
            $this->segregatePlaceDelay($table);
            
            // end trial
            if ($table->minimize_roundtrips)
            {
                // GROUPS CONSIST OF ONLY INTERNAL SOURCE & PEICES OF CODE HENCE WE DO NOT alias the CDN's as it makes no sense to pull the entire source from a cdn //and paste it in an inline script defeating the very purpose of a CDN
                $page_new_script = $this->assignGroups($page_new_script);
                $this->initialiseGroupHash($page_new_script);
                $this->combineGroupCode($table);
                //ver1.0.1.0 ammend
                if(!empty($params['resultant_async']) || !empty($params['resultant_defer']))
                {
                	$this->combineDelayloadUrlToGroup($table);
                	if ($table->css_switch)
                	{
                		//only scroll and mousemove
                		$this->combineCssDelayloadUrlToGroup($table);
                	}
                }
                $this->prepareGrouploadableUrl($params);
                $this->writeGroupCode($table); // writes Group js scripts that will be loaded from JSCacheStrategy in operation mode flag $success if failed
            }
            //old coment
            // Although I dont see a benefit at this stage Delay will alias to CDN's
            /*
             *$this->makeDelaycode();
             *$this->segregatePlaceDelay($table);
            */
            // $page_new_script = $this->moderateDefaultLoadsections($page_new_script);//all remaining defaults are moved to closing header tag int(2)
            $this->prepareLoadsections($page_new_script, $table, $params);
            //
            $this->combineSectionFooter(self::$_advertisement_segment);
            $this->combineSectionFooter(self::$_social_segment);
            $this->combineSectionFooter(self::$_async_segment);
            //$this->combineMAU();//moved below to accomadate mau for async
            if ($table->conduit_switch)
            {
                // $this->combineConduitFooter();
            }
            
            $this->combineDelay( $params);
            
            if ($table->css_switch)
            {
                $this->combineCssDelayToScript($params);
            }
            $this->combineMAU();//test for async mau
            $return = MulticacheHelper::writeJsCacheStrategy(self::$_signature_hash, self::$_loadsections, $table->js_switch, $stubs, $jst_exclude_object, self::$_signature_hash_css, self::$_loadsections_css, $table->css_switch, $css_exclude_object, $table->image_lazy_switch, $img_exclude_object, $params_lazyload, self::$_dontmovesignature_hash, self::$_dontmoveurls, self::$_allow_multiple_orphaned , $params);
            
            if (! empty($table->simulation_advanced))
            {
                $this->prepareSimulationControl($simcontrol_object, $bypass);
            }
            Return true;
        }
        
        else
        {
            // $return = MulticacheHelper::writeJsCacheStrategy(null, null, $table->js_switch, $stubs, $jst_exclude_object, self::$_signature_hash_css, self::$_loadsections_css, $table->css_switch, $css_exclude_object, $table->image_lazy_switch, $img_exclude_object, $params_lazyload);
            /* becomes redudant when we inject the js_switch into plugin params lets maintain this for structure */
            
            if (! empty($table->simulation_advanced))
            {
                // $this->prepareSimulationControl($simcontrol_object, $bypass);
            }
        }
    
    }

    protected function prepareSimulationControl($SIMOBJ, $LOCK_FLAG)
    {

        if (empty($LOCK_FLAG))
        {
            Return; // not empty is a lock
        }
        $app = JFactory::getApplication();
        
        $comp = JModelLegacy::getInstance('Simcontrol', 'MulticacheModel');
        
        $prepared = $comp->getSimcontrol($SIMOBJ);
        if (! $prepared)
        {
            $app = JFactory::getApplication();
            $app->enqueueMessage(JText::_('COM_MULTICACHE_PREPARE_SIMULATION_CONTROL_FAILED'), 'error');
        }
    
    }

    protected function prepareLoadSections($jsarray, $tbl, $params)
    {

        self::$_loadsections[1] = $this->getLoadSection(1, $jsarray, $tbl, $params);
        self::$_loadsections[2] = $this->getLoadSection(2, $jsarray, $tbl, $params);
        self::$_loadsections[3] = $this->getLoadSection(3, $jsarray, $tbl, $params);
        self::$_loadsections[4] = $this->getLoadSection(4, $jsarray, $tbl, $params);
    
    }

    protected function prepareCssLoadSections($cssarray, $tbl, $param)
    {

        self::$_loadsections_css[1] = $this->getCssLoadSection(1, $cssarray, $tbl, $param);
        self::$_loadsections_css[2] = $this->getCssLoadSection(2, $cssarray, $tbl, $param);
        self::$_loadsections_css[3] = $this->getCssLoadSection(3, $cssarray, $tbl, $param);
        self::$_loadsections_css[4] = $this->getCssLoadSection(4, $cssarray, $tbl, $param);
   
    }

    protected function prepareGrouploadableUrl($params = null)
    {

        if (! isset(self::$_groups))
        {
            Return false;
        }
        
        foreach (self::$_groups as $key => $grp)
        {
            if ($grp["success"])
            {
                
                self::$_groups[$key]["url"] = MulticacheHelper::getJScodeUrl($key, "raw_url", self::$_principle_jquery_scope, self::$_mediaVersion);
                self::$_groups[$key]["callable_url"] = MulticacheHelper::getJScodeUrl($key, null, self::$_principle_jquery_scope, self::$_mediaVersion);
                self::$_groups[$key]["script_tag_url"] = MulticacheHelper::getJScodeUrl($key, "script_url", self::$_principle_jquery_scope, self::$_mediaVersion, $params);
            }
        }
    
    }

    protected function prepareCssGrouploadableUrl()
    {

        if (! isset(self::$_groups_css))
        {
            Return false;
        }
        
        foreach (self::$_groups_css as $key => $grp)
        {
            if ($grp["success"])
            {
                
                self::$_groups_css[$key]["url"] = MulticacheHelper::getCsscodeUrl($key, "raw_url", self::$_principle_jquery_scope, self::$_mediaVersion);
                self::$_groups_css[$key]["callable_url"] = MulticacheHelper::getCsscodeUrl($key, "link_url", self::$_principle_jquery_scope, self::$_mediaVersion);
                self::$_groups_css[$key]["css_tag_url"] = MulticacheHelper::getCsscodeUrl($key, "link_url", self::$_principle_jquery_scope, self::$_mediaVersion);
            }
        }
    
    }

    protected function prepareDelayable($jsarray, $table)
    {

        $preceedence = isset($table->maintain_preceedence) ? $table->maintain_preceedence : false;
        
        $delay_lag = false;
        $delayable = null;
        $stored_delayable = $this->loadProperty('delayed');
        foreach ($jsarray as $key => $js)
        {
            // reset the delay lag if new delay type
            if (! empty($jsarray[$key]["delay"]))
            {
                $delay_lag = false;
            }
            if (! empty($js["delay"]) || $delay_lag) // here we use !empty as a direct equivalent of isset && =true
            {
                if (! $delay_lag)
                {
                    $delay_type = $jsarray[$key]["delay_type"];
                }
                else
                {
                    $jsarray[$key]["delay_type"] = $delay_type; // aligning the outer and inner keys
                }
                $delayable[$delay_type]["items"][$key] = $jsarray[$key];
                
                unset($jsarray[$key]);
                
                $delay_lag = $preceedence ? true : false;
            }
        }
        
        if (isset($delayable) && isset($stored_delayable))
        {
            
            $delayable = array_replace_recursive($stored_delayable, $delayable); // changed from array_merge_recirsive to maintain keys
        }
        elseif (isset($stored_delayable) && ! isset($delayable))
        {
            $delayable = $stored_delayable;
        }
        
        if (! empty($delayable))
        {
            // lets sort this in two parts to ensure sorting
            $mousemove = $delayable['mousemove']['items'];
            $scroll = $delayable['scroll']['items'];
            $onLoad = $delayable['onload']['items'];
            if (isset($mousemove))
            {
                ksort($mousemove);
                $delayable['mousemove']['items'] = $mousemove;
            }
            if (isset($scroll))
            {
                ksort($scroll);
                $delayable['scroll']['items'] = $scroll;
            }
            if (isset($onLoad))
            {
            	ksort($onLoad);
            	$delayable['onload']['items'] = $onLoad;
            }
            
            self::$_delayable_segment = $delayable;
        }
        Return $jsarray;
    
    }

    protected function prepareDelayableCss($cssarray, $table)
    {

        $preceedence = isset($table->css_maintain_preceedence) ? $table->css_maintain_preceedence : false;
        
        $delay_lag = false;
        $delayable = null;
        $stored_delayable = $this->loadProperty('delayed', 'MulticachePageCss');
        
        foreach ($cssarray as $key => $css)
        {
            // reset the delay lag if new delay type
            if (! empty($cssarray[$key]["delay"]))
            {
                $delay_lag = false;
            }
            if (! empty($css["delay"]) || $delay_lag) // here we use !empty as a direct equivalent of isset && =true
            {
                if (! $delay_lag)
                {
                    $delay_type = $cssarray[$key]["delay_type"];
                }
                else
                {
                    $cssarray[$key]["delay_type"] = $delay_type; // aligning the outer and inner keys
                }
                $delayable[$delay_type]["items"][$key] = $cssarray[$key];
                
                unset($cssarray[$key]);
                
                $delay_lag = $preceedence ? true : false;
            }
        }
        
        if (isset($delayable) && isset($stored_delayable))
        {
            
            $delayable = array_replace_recursive($stored_delayable, $delayable); // changed from array_merge_recirsive to maintain keys
        }
        elseif (isset($stored_delayable) && ! isset($delayable))
        {
            $delayable = $stored_delayable;
        }
        
        if (! empty($delayable))
        {
            // lets sort this in two parts to ensure sorting
            $mousemove = $delayable['mousemove']['items'];
            $scroll = $delayable['scroll']['items'];
            $async = $delayable['async']['items'];
            if (isset($mousemove))
            {
                ksort($mousemove);
                $delayable['mousemove']['items'] = $mousemove;
            }
            if (isset($scroll))
            {
                ksort($scroll);
                $delayable['scroll']['items'] = $scroll;
            }
            if (isset($async))
            {
                ksort($async);
                $delayable['async']['items'] = $async;
            }
            
            self::$_delayable_segment_css = $delayable;
        }
        Return $cssarray;
    
    }

    protected function prepare_JSvars($obj)
    {

        if (! empty($obj->default_scrape_url) && $obj->default_scrape_url != strtolower(JURI::root()))
        {
            
            // 1st check that they are of the same domain
            $base_url = strtolower(substr(JURI::root(), 0, - 1));
            $scrape_url = strtolower($obj->default_scrape_url);
            $same_domain = stristr($scrape_url, $base_url);
            if (! $same_domain)
            {
                $obj->default_scrape_url = JURI::root();
            }
        }
    
    }

    protected function prepareNonTableCssElements()
    {

        $app = JFactory::getApplication();
        $page_css_object = $this->getRelevantPageCss(); // ATTENTION THIS SETTING IS RELATED TO VIEW: Better still to align by signatures but that would not give the option to the user to retain duplicate scripts
        if(empty($page_css_object))
        {
        	Return false;
        }
                                                        
        // get the array keys
        $template_csskeys = $this->getTemplateCssKeys($page_css_object);
        
        $jinput = JFactory::getApplication()->input;
        foreach ($page_css_object as $key => $obj)
        {
            
            foreach ($template_csskeys as $template_csskey)
            {
                $key_state_tag_css = 'com_multicache_css' . $template_csskey . '_' . $key;
                $current_state = $jinput->get($key_state_tag_css);
                
                if ($current_state != $obj[$template_csskey])
                {
                    $page_css_object[$key][$template_csskey] = $current_state;
                }
            }
            
            // attach the cdn url or reset the key
            $cdn_key_css = $jinput->get('com_multicache_csscdnalias_' . $key);
            $cdn_url_css = $jinput->getHtml('cdn_url_css_' . $key);
            if (! empty($cdn_key_css))
            {
                
                if (! empty($cdn_url_css))
                {
                    $page_css_object[$key]['cdn_url_css'] = $cdn_url_css;
                }
                else
                {
                    $page_css_object[$key]['cdnaliascss'] = 0;
                    $page_css_object[$key]['cdn_url_css'] = null;
                }
            }
            // correction from wp comment out latter
            // special case to clear out cdn url
            if (empty($cdn_key_css) /*&& ! empty($cdn_url_css)*/)
            {
                $page_css_object[$key]['cdn_url_css'] = null;
            }
        }
        
        Return $page_css_object;
    
    }

    protected function prepareNonTableElements()
    {

        $app = JFactory::getApplication();
        $page_script_object = $this->getRelevantPageScript(); // ATTENTION THIS SETTING IS RELATED TO VIEW: Better still to align by signatures but that would not give the option to the user to retain duplicate scripts
        if(empty($page_script_object))
        {
        	Return false;
        }
                                                              
        // get the array keys
        $template_keys = $this->getTemplateKeys($page_script_object);
        
        $jinput = JFactory::getApplication()->input;
        foreach ($page_script_object as $key => $obj)
        {
            
            foreach ($template_keys as $template_key)
            {
                $key_state_tag = 'com_multicache_' . $template_key . '_' . $key;
                $current_state = $jinput->get($key_state_tag);
                
                if ($current_state != $obj[$template_key])
                {
                    $page_script_object[$key][$template_key] = $current_state;
                }
            }
            //todo: moderate these raw to lower types
            $ident = $jinput->get('ident_' . $key , null, 'RAW');
            $checkType = $jinput->get('checkType_' . $key, null, 'RAW');
            $thenBack = $jinput->get('thenBack_' . $key, null, 'RAW');
            $mautime = $jinput->get('mautime_' . $key, null, 'INT');
            
            
            
            if(!empty($ident))
            {
            	$page_script_object[$key]['ident'] = $ident;
            }
            if(!empty($checkType))
            {
            	$page_script_object[$key]['checktype'] = $checkType;
            }
            if(!empty($mautime))
            {
            	$mautime = $mautime <30 || $mautime >=1000 ? 30 : $mautime;
            		
            	$page_script_object[$key]['mautime'] = $mautime;
            }
            if(!empty($thenBack))
            {
            	if(strpos($thenBack , "'")!== false)
            	{
            		$thenBack = preg_replace("~\\\'~","'",$thenBack);
            
            	}
            	if(strpos($thenBack , '"')!== false)
            	{
            		$thenBack = preg_replace('~\\\"~','"',$thenBack);
            
            	}
            		
            	$page_script_object[$key]['thenBack'] = $thenBack;
            	$page_script_object[$key]['thenBack_json'] = json_encode($thenBack);
            	$page_script_object[$key]['thenBack_serialized'] = serialize(htmlentities($thenBack));
            		
            }
            // attach the cdn url or reset the key
            $cdn_key = $jinput->get('com_multicache_cdnalias_' . $key);
            $cdn_url = $jinput->getHtml('cdn_url_' . $key);
            if (! empty($cdn_key))
            {
                
                if (! empty($cdn_url))
                {
                    $page_script_object[$key]['cdn_url'] = $cdn_url;
                }
                else
                {
                    $page_script_object[$key]['cdnalias'] = 0;
                    $page_script_object[$key]['cdn_url'] = null;
                }
            }
            // correction from WP comment out latter
            // special case to clear out cdn url
            if (empty($cdn_key) /*&& ! empty($cdn_url)*/)
            {
                $page_script_object[$key]['cdn_url'] = null;
            }
        }
        
        Return $page_script_object;
    
    }

    protected function combineConduitFooter()
    {

        $loadsections = self::$_loadsections;
        $footer_segment = $loadsections[4];
        if (! empty($footer_segment))
        {
            $load_string = unserialize($footer_segment);
        }
        
        $conduit_src = JURI::root() . 'administrator/components/com_multicache/assets/js/conduit_footer.js';
        $load_string .= MulticacheHelper::getloadableSourceScript($conduit_src, false);
        if (empty($load_string))
        {
            Return false;
        }
        self::$_loadsections[4] = serialize($load_string);
        Return true;
    
    }

    protected function correctDelaySignatureHash($delay_obj)
    {

        if (empty($delay_obj))
        {
            Return false;
        }
        
        foreach ($delay_obj as $object)
        {
            
            $this->correctSignatureHash($object["items"]);
        }
    
    }

    protected function correctDelaySignatureHashCss($delay_obj)
    {

        if (empty($delay_obj))
        {
            Return false;
        }
        
        foreach ($delay_obj as $object)
        {
            
            $this->correctSignatureHashCss($object["items"]);
        }
    
    }

    protected function correctSignatureHash($obj)
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

    protected function correctSignatureHashCss($obj)
    {

        if (empty($obj))
        {
            Return false;
        }
        
        foreach ($obj as $key => $css)
        {
            $sig = $css["signature"];
            $alt_sig = $css["alt_signature"];
            if (! isset(self::$_signature_hash_css[$sig]))
            {
                self::$_signature_hash_css[$sig] = true;
            }
            if (isset($alt_sig) && ! isset(self::$_signature_hash_css[$alt_sig]))
            {
                
                self::$_signature_hash_css[$alt_sig] = true;
            }
        }
        Return true;
    
    }

    protected function combineDelay($params)
    {

        if (empty(self::$_delayable_segment))
        {
            Return false;
        }
        
        //ver1.0.1.1
        if(isset(self::$_delayable_segment["scroll"]["resultant_async_defer_loaded"])
        		&& !isset(self::$_delayable_segment["onload"]))
        {
        	Return false;
        }
        $delay = '';
        
     if(!isset(self::$_delayable_segment["scroll"]["resultant_async_defer_loaded"])
		    		&& (!empty(self::$_delayable_segment["scroll"]) ||(!empty(self::$_delayable_segment["mousemove"]))))
		    {
			$delay = self::$_principle_jquery_scope . "( document ).ready(function() {";
			foreach (self::$_delayable_segment as $delay_type_key => $delay_obj)
			{
				if($delay_type_key == 'onload' )
				{
					continue;
				}
				if (! empty($delay_obj["delay_executable_code"]))
				{
					$delay .= unserialize($delay_obj["delay_executable_code"]);
				}
			}
			$delay .= "});";
		    }
		    if(!empty(self::$_delayable_segment['onload']))
		    {
		    	foreach (self::$_delayable_segment as $delay_type_key => $delay_obj)
		    	{
		    		if($delay_type_key == 'scroll' || $delay_type_key == 'mousemove')
		    		{
		    			continue;
		    		}
		    		if (! empty($delay_obj["delay_executable_code"]))
		    		{
		    			$delay .= unserialize($delay_obj["delay_executable_code"]);
		    		}
		    	}
		    }
		    if(empty($delay))
		    {
		    	Return false;
		    }
		    //wrap delay abandoned
		    //$delay = MulticacheHelper::wrapDelay( $delay , self::$_principle_jquery_scope );
		    $delay = serialize($delay); // just to make it compatible with earlier processes
		    // $ds = MulticacheHelper::getloadableCodeScript( $delay ,false );
        $loadsections = self::$_loadsections;
        $footer_segment = $loadsections[4];
        if (! empty($footer_segment))
        {
            $load_string = unserialize($footer_segment);
        }
        
        $load_string .= MulticacheHelper::getloadableCodeScript($delay, false , null , $params);
        if (empty($load_string))
        {
            Return false;
        }
        self::$_loadsections[4] = serialize($load_string);
        Return true;
    
    }

    protected function combineCssDelay($params_extended = null)
    {

        if (empty(self::$_delayable_segment_css)&& empty($params_extended))
        {
            Return false;
        }
        $delay = null;
        $delay_async = null;
        $delay_async_head = null; // needs to go to head
        $delay_async_foot = null;
        $load_string = "";
        $n_script = '';
        if (isset(self::$_delayable_segment_css["scroll"]) || isset(self::$_delayable_segment_css["mousemove"]))
        {
            $delay = self::$_principle_jquery_scope . "( document ).ready(function() {";
            foreach (self::$_delayable_segment_css as $delay_type_key => $delay_obj)
            {
                if ($delay_type_key == 'async')
                {
                    continue;
                }
                if (! empty($delay_obj["delay_executable_code"]))
                {
                    $delay .= unserialize($delay_obj["delay_executable_code"]);
                }
            }
            $delay .= "});";
            // we need to code in the async delay call
           
                                            // $ds = MulticacheHelper::getloadableCodeScript( $delay ,false );
        }
        
        
        if (isset(self::$_delayable_segment_css["async"]))
        {
        	//test async mau
        	if(!isset(self::$_promises))
        	{
        		$this->initiatePromise();
        	}
        	$this->setMau();
        	//end test
            // lets get the src bits
            $async_src_bits = MulticacheHelper::getAsyncSrcbits(self::$_delayable_segment_css["async"]);
            $delay_async_head = unserialize(self::$_delayable_segment_css["async"]["delay_executable_code"]);
            $delay_async_head = !empty($delay_async_head) ? MulticacheJSOptimize::process($delay_async_head) : '';
            $delay_async_head = !empty($delay_async_head) ? MulticacheHelper::getloadableCodeScript($delay_async_head, false, true) : '';
            $delay_async_foot = !empty($async_src_bits) ? MulticacheJSOptimize::process($async_src_bits) : '';
            $delay_async_foot = !empty($delay_async_foot) ?  MulticacheHelper::getloadableCodeScript($delay_async_foot, false, true) : '';
        }
        
        if(!empty($params_extended['css_groupsasync']))
        {
        	//test async mau
        	if(!isset(self::$_promises))
        	{
        		$this->initiatePromise();
        	}
        	$this->setMau();
        	//end test
        	$group_async_src_bits = MulticacheHelper::getGroupAsyncSrcbits(self::$_groups_css , $params_extended);
        
        	if(!isset($delay_async_head))
        	{
        		$group_async_head = MulticacheHelper::getCssdelaycode('async' , null , false , $params_extended);
        
        		$group_async_head = unserialize($group_async_head['code']);
        		$group_async_head = MulticacheJSOptimize::process($group_async_head);
        		$group_async_head = !empty($group_async_head) ? MulticacheHelper::getloadableCodeScript($group_async_head, false, true) : null;
        
        	}
        	$group_async_foot = $group_async_src_bits['inline_code'];
        	//$group_async_foot = MulticacheJSOptimize::process($group_async_foot);
        	//$group_async_foot_noscript = $group_async_src_bits['noscript'];
        	//wp transport
        	$group_async_foot = !empty($group_async_foot)? MulticacheJSOptimize::process($group_async_foot) : '';
        	$group_async_foot_noscript = !empty($group_async_foot)? $group_async_src_bits['noscript'] : '';
        	//end wp transport
        	$group_async_foot = !empty($group_async_foot) ? MulticacheHelper::getloadableCodeScript($group_async_foot, false, true) : null;
        
        	//
        	//excluded scripts
        	if(!empty($group_async_src_bits['excluded_code']))
        	{
        		$group_async_excluded = $group_async_src_bits['excluded_code'];
        		$loadsections_css = self::$_loadsections_css;
        		if(is_array($loadsections_css))
        		{
        			$loadsections_css = array_filter($loadsections_css);
        			$all_keys = array_keys($loadsections_css);
        		}
        	
        		$excluded_groupsasync_section = !empty($all_keys) && is_array($all_keys) ? min($all_keys) : 2;
        		$loading_under = $loadsections_css[$excluded_groupsasync_section];
        		$loading_under = !empty($loading_under)? unserialize($loading_under):'';
        		$loading_under .= $group_async_excluded;
        		self::$_loadsections_css[$excluded_groupsasync_section] = serialize($loading_under);
        	
        	}
        }
        $this->combineMAUCSS();//we need to order scripts after links here
        $loadsections = self::$_loadsections_css;
       /* if (isset($delay_async_head))
        {*/
        if (isset($delay_async_head)
        		||(isset($params_extended['css_groupsasync'])
        				&& !empty($group_async_head)))
        {
            $load_string_head = "";
            $head_segment = $loadsections[2];
            if (! empty($head_segment))
            {
                $load_string_head = unserialize($head_segment);
            }
            if (isset($delay_async_head))
            {
                $load_string_head .= $delay_async_head;
                
                self::$_loadsections_css[2] = serialize($load_string_head);
            }
            elseif(isset($params_extended['css_groupsasync'])
            		&& !empty($group_async_head))
            {
            	$load_string_head .= $group_async_head;
            
            	self::$_loadsections_css[2] = serialize($load_string_head);
            
            }
        }
        $footer_segment = $loadsections[4];
        // we can choose this point to load asyn delay type in head
        $load_string = "";
        if (! empty($footer_segment))
        {
            $load_string = unserialize($footer_segment);
        }
        // we'll need to maintain get loadable code script here, as the css delays are executed through javascript
        
        if (isset($delay))
        {
        	$delay = MulticacheJSOptimize::process($delay);
           // $load_string .= MulticacheHelper::getloadableCodeScript($delay, false);
        	  $load_string .= MulticacheHelper::getloadableCodeScript($delay, false , true);
        }
        if (isset($delay_async_foot))
        {
            $load_string .= $delay_async_foot;
        }
        if (!empty($group_async_foot))
        {
        	$load_string .= $group_async_foot;
        }
        if (! empty(self::$_delayed_noscript))
        {
            //$load_string .= self::$_delayed_noscript;
        	$n_script .= self::$_delayed_noscript;
        }
        if(!empty($group_async_foot_noscript))
        {
        	//$n_script .= $group_async_foot_noscript;
        	$noscript = $group_async_foot_noscript;
        	$noscript = !empty($noscript)? MulticacheCSSOptimize::optimize($noscript): '';
        	$noscript = !empty($noscript)?  MulticacheHelper::noscriptWrap($noscript , true) : '';
        	$n_script .=$noscript;
        
        }
        if(!empty($n_script))
        {
        	
        
        	$load_string .= $n_script;
        }
        
        if (empty($load_string))
        {
            Return false;
        }
        self::$_loadsections_css[4] = serialize($load_string);
        Return true;
    
    }

    protected function combineCssDelayToScript($params_extended = null)
    {

    if (empty(self::$_delayable_segment_css) && empty($params_extended))
			{
				Return false;
			}
            $delay = null;
			$delay_async = null;
			//ver1.0.1.1
			$delay_async_head = null;
			$delay_async_foot = null;
			$load_string = "";
			$n_script = '';
        if (    (
        		isset(self::$_delayable_segment_css["scroll"]) 
        		|| isset(self::$_delayable_segment_css["mousemove"])
        		)
        		&&!isset(self::$_delayable_segment_css["scroll"]["resultant_async_defer_loaded"])
        		)
        {
            $delay = self::$_principle_jquery_scope . "( document ).ready(function() {";
            foreach (self::$_delayable_segment_css as $delay_type_key => $delay_obj)
            {
                if ($delay_type_key == 'async')
                {
                    continue;
                }
                if (! empty($delay_obj["delay_executable_code"]))
                {
                    $delay .= unserialize($delay_obj["delay_executable_code"]);
                }
            }
            $delay .= "});";
            // we need to code in the async delay call
           // $delay = serialize($delay); // just to make it compatible with earlier processes
                                            // $ds = MulticacheHelper::getloadableCodeScript( $delay ,false );
        }
        
       
        if (isset(self::$_delayable_segment_css["async"]))
        {
        	//test async mau
        	if(!isset(self::$_promises))
        	{
        		$this->initiatePromise();
        	}
        	$this->setMau();
            // lets get the src bits
            $async_src_bits = MulticacheHelper::getAsyncSrcbits(self::$_delayable_segment_css["async"]);
            $delay_async_head = unserialize(self::$_delayable_segment_css["async"]["delay_executable_code"]);
            $delay_async_head = MulticacheJSOptimize::process($delay_async_head);
            $delay_async_head = MulticacheHelper::getloadableCodeScript($delay_async_head, true, true);
            $delay_async_foot = MulticacheJSOptimize::process($async_src_bits);
            $delay_async_foot = MulticacheHelper::getloadableCodeScript($delay_async_foot, true, true);
        }
        if(!empty($params_extended['css_groupsasync']))
        {
        	if(!isset(self::$_promises))
        	{
        		$this->initiatePromise();
        	}
        	$this->setMau();
        	$group_async_src_bits = MulticacheHelper::getGroupAsyncSrcbits(self::$_groups_css , $params_extended);
        		
        	if(!isset($delay_async_head))
        	{
        		$group_async_head = MulticacheHelper::getCssdelaycode('async' , null , false , $params_extended);
        			
        		$group_async_head = unserialize($group_async_head['code']);
        		$group_async_head = MulticacheJSOptimize::process($group_async_head);
        		$group_async_head = !empty($group_async_head) ? MulticacheHelper::getloadableCodeScript($group_async_head, true, true) : null;
        			
        	}
        	$group_async_foot = $group_async_src_bits['inline_code'];
        	$group_async_foot = !empty($group_async_foot) ? MulticacheJSOptimize::process($group_async_foot) : '';
        	$group_async_foot_noscript = !empty($group_async_foot) ? $group_async_src_bits['noscript'] :'';
        	$group_async_foot = !empty($group_async_foot) ? MulticacheHelper::getloadableCodeScript($group_async_foot, true, true) : null;
        	
        	//excluded scripts
        	if(!empty($group_async_src_bits['excluded_code']))
        	{
        	$group_async_excluded = $group_async_src_bits['excluded_code'];
        	$loadsections_css = self::$_loadsections_css;
        	if(is_array($loadsections_css))
        	{
        		$loadsections_css = array_filter($loadsections_css);
        		$all_keys = array_keys($loadsections_css);
        	}
        	
        	$excluded_groupsasync_section = !empty($all_keys) && is_array($all_keys) ? min($all_keys) : 2;
        	$loading_under = $loadsections_css[$excluded_groupsasync_section];
        	$loading_under = !empty($loading_under)? unserialize($loading_under):'';
        	$loading_under .= $group_async_excluded;
        	self::$_loadsections_css[$excluded_groupsasync_section] = serialize($loading_under);
        	
        	}
        	//
        }
        $loadsections = self::$_loadsections; // were combining the script to jstweaks script here
        //ver1.0.1.1
        if (isset($delay_async_head) 
					||(isset($params_extended['css_groupsasync']) 
					&& !empty($group_async_head)))
			{
            $load_string_head = "";
            $head_segment = $loadsections[2];
            if (! empty($head_segment))
            {
                $load_string_head = unserialize($head_segment);
            }
            if (isset($delay_async_head))
            {
                $load_string_head .= $delay_async_head;
                self::$_loadsections[2] = serialize($load_string_head);
            }
            elseif(isset($params_extended['css_groupsasync'])
            		&& !empty($group_async_head))
            {
            	$load_string_head .= $group_async_head;
            
            	self::$_loadsections[2] = serialize($load_string_head);
            
            }
        }
        $footer_segment = $loadsections[4];
        // we can choose this point to load asyn delay type in head
        
        if (! empty($footer_segment))
        {
            $load_string = unserialize($footer_segment);
        }
        // we'll need to maintain get loadable code script here, as the css delays are executed through javascript
        
        if (isset($delay))
        {
        	$delay = MulticacheJSOptimize::process($delay);
            $load_string .= MulticacheHelper::getloadableCodeScript($delay, false , true);
        }
        if (isset($delay_async_foot))
        {
            $load_string .= $delay_async_foot;
        }
        if (!empty($group_async_foot))
        {
        	$load_string .= $group_async_foot;
        }
    if (! empty(self::$_delayed_noscript))
			{
				$n_script .= self::$_delayed_noscript;
			}
			
			if(!empty($group_async_foot_noscript))
			{
				$noscript = $group_async_foot_noscript;
				$noscript = !empty($noscript)? MulticacheCSSOptimize::optimize($noscript): '';
				$noscript = !empty($noscript)?  MulticacheHelper::noscriptWrap($noscript , true) : '';
				$n_script .=$noscript;
					
			}
			
			if(!empty($n_script))
			{							
				$load_string .= $n_script;
			}

        if (empty($load_string))
        {
            Return false;
        }
        self::$_loadsections[4] = serialize($load_string);
        Return true;
    
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
                    $load_string .= MulticacheHelper::getloadableSourceScript($obj["absolute_src"], $obj["async"]);
                }
                elseif (! $obj["internal"])
                {
                    $load_string .= MulticacheHelper::getloadableSourceScript($obj["src"], $obj["async"]);
                }
            }
            else
            {
                
                $load_string .= MulticacheHelper::getloadableCodeScript($obj["serialized_code"], $obj["async"]);
            }
        }
        if (empty($load_string))
        {
            Return false;
        }
        self::$_loadsections[4] = serialize($load_string);
        Return true;
    
    }
    
    protected function combineMAUCSS()
    {
    	if(!isset(self::$_promises) || empty(self::$_loadsections_css) || true !== self::$_promises['load_mau'])
    	{
    		Return false;
    	}
    	$loadsections = self::$_loadsections_css;
    
    	if(is_array($loadsections))
    	{
    		$loadsections = array_filter($loadsections);
    	}
    	$all_keys = array_keys($loadsections);
    	$mau_section = !empty($all_keys) && is_array($all_keys) ? min($all_keys) : 2;
    	$mau_string = MulticacheHelper::getMAU();
    	$mau_string = MulticacheJSOptimize::process($mau_string);
    	$mau_string = MulticacheHelper::getloadableCodeScript($mau_string , false ,true);
    	$l_section = $loadsections[$mau_section];
    	if(!empty($l_section))
    	{
    		$l_section_unser = unserialize($l_section);
    		if(!$l_section_unser)
    		{
    			Return false;
    		}
    	}
    	else{
    		$l_section_unser = '';
    	}
    	$l_section_unser .=  $mau_string ;
    	$l_l_sec = serialize($l_section_unser);
    	if(!$l_l_sec)
    	{
    		Return false;
    	}
    	//try another unserialize
    	$test_lsec = unserialize($l_l_sec);
    	if(!$test_lsec)
    	{
    		Return false;
    	}
    
    	self::$_loadsections_css[$mau_section] = $l_l_sec;
    	Return true;
    }
    
    protected function combineMAU()
    {
    	if(!isset(self::$_promises) || empty(self::$_loadsections) || true !== self::$_promises['load_mau'])
    	{
    		Return false;
    	}
    	$loadsections = self::$_loadsections;
    		
    	if(is_array($loadsections))
    	{
    		$loadsections = array_filter($loadsections);
    	}
    	$all_keys = array_keys($loadsections);
    	$mau_section = isset($all_keys) && is_array($all_keys) ? min($all_keys) : 2;
    	$mau_string = MulticacheHelper::getMAU();
    	$mau_string = MulticacheJSOptimize::process($mau_string);
    	$mau_string = MulticacheHelper::getloadableCodeScript($mau_string , false ,true);
    	$l_section = $loadsections[$mau_section];
    		
    	$l_section_unser = unserialize($l_section);
    	if(!$l_section_unser)
    	{
    		Return false;
    	}
    	$l_section_unser = $mau_string . $l_section_unser;
    	$l_l_sec = serialize($l_section_unser);
    	if(!$l_l_sec)
    	{
    		Return false;
    	}
    	//try another unserialize
    	$test_lsec = unserialize($l_l_sec);
    	if(!$test_lsec)
    	{
    		Return false;
    	}
    		
    	self::$_loadsections[$mau_section] = $l_l_sec;
    	Return true;
    }
    

    protected function moderateDefaultLoadsections($jsarray)
    {

        foreach ($jsarray as $key => $js)
        {
            if (isset($js["loadsection"]) && $js["loadsection"] == 0)
            {
                $jsarray[$key]["loadsection"] = 2;
            }
        }
        Return $jsarray;
    
    }

    protected function segregatePlaceDelay($tbl)
    {

        if (empty(self::$_delayable_segment))
        {
            Return false;
        }
        
        $app = JFactory::getApplication();
        //
        foreach (self::$_delayable_segment as $key_delaytype => $delay_seg)
        {
        	if($key_delaytype == 'onload')
        	{
        		continue;
        	}
            $success = $this->placeDelayedCode($delay_seg, $tbl);
            if ($success)
            {
                self::$_delayable_segment[$key_delaytype]["success"] = true;
            }
            else
            {
                self::$_delayable_segment[$key_delaytype]["success"] = false;
                $app->enqueueMessage(JText::_('COM_MULTICACHE_CLASS_MULTICACHE_PAGE_SCRIPTS_PLACE_DELAY_FAILED') . $key_delaytype, 'error');
                $app->redirect('index.php?option=com_multicache&view=config&layout=edit&id=1#page-js-tweaks');
            }
        }
        //compress onload code if required
        if($tbl->compress_js && isset(self::$_delayable_segment['onload']["delay_executable_code"]))
        {
        	$onload_code = unserialize(self::$_delayable_segment['onload']["delay_executable_code"]);
        	if(!$onload_code)
        	{
        		Return;
        	}
        	$onload_code = MulticacheJSOptimize::process($onload_code) ;
        	$onload_code = serialize($onload_code);
        	self::$_delayable_segment['onload']["delay_executable_code"] = $onload_code;
        }
    
    }

    protected function prepareNoscriptAsync($grp, $tbl)
    {

        if (empty($grp["items"]))
        {
            Return false;
        }
        $app = JFactory::getApplication();
        foreach ($grp["items"] as $key => $group)
        {
            $sig = $group["signature"];
            
            if (isset(self::$_cdn_segment[$sig]) && (bool) self::$_cdn_segment[$sig] == true)
            {
                $url = self::$_cdn_segment[$sig];
                $cdn_link = '<link  href="' . $url . '" rel="stylesheet" type="text/css" />';
               // $serialized = serialize('<link type="text/css" href="' . $url . '" />');
               // self::$_delayed_noscript .= MulticacheHelper::noscriptWrap($serialized);
                self::$_delayed_noscript .= $cdn_link;
            }
            elseif(!empty($group['cdnalias']) && !empty($group['cdn_url_css']))
            {
            	$cdn_link = '<link  href="' . $group['cdn_url_css'] . '" rel="stylesheet" type="text/css" />';
            	self::$_delayed_noscript .= $cdn_link;
            	
            }
            elseif (! isset($group["internal"]) && ! empty($group["code"]))
            {
                $unserialized_code = unserialize($group["serialized_code"]);
                $code = ! empty($unserialized_code) ? $unserialized_code : $group["code"];
                // unserialize and tie code here
                if (! empty($code))
                {
                    $inline_async = $code;
                    if ($tbl->compress_css)
                    {
                        $inline_async = MulticacheCSSOptimize::optimize($inline_async);
                    }
                   // self::$_delayed_noscript .= '<noscript><style>' . $inline_async . '</style></noscript>';
                    self::$_delayed_noscript .= '<style>' . $inline_async . '</style>';
                }
                else
                {
                    // register error
                    
                    $app->enqueueMessage(JText::_('COM_MULTICACHE_CLASS_MULTICACHE_PAGE_CSS_URL_NOT_INTERNAL_NOT_CODE_NOSCRIPTASYNC_ERROR'), 'error');
                    Return false;
                }
            }
            
            elseif (isset($group["href"]))
            {
            	$unserialized_linktag = unserialize($group["serialized"]);
            	$linktag = ! empty($unserialized_linktag) ? $unserialized_linktag : '<link href="'.$group["href"].'" rel="stylesheet" type="text/css" />';
                //self::$_delayed_noscript .= MulticacheHelper::noscriptWrap($group["serialized"]);
            	self::$_delayed_noscript .= $linktag;
            }
        }
        if(!empty(self::$_delayed_noscript))
        {
        	self::$_delayed_noscript = '<noscript>' . self::$_delayed_noscript . '</noscript>';
        }
    
    }

    protected function segregatePlaceCssDelay($tbl)
    {

        if (empty(self::$_delayable_segment_css))
        {
            Return false;
        }
        $app = JFactory::getApplication();
        //
        foreach (self::$_delayable_segment_css as $key_delaytype => $delay_seg)
        {
            if ($key_delaytype == 'async')
            {
                
                continue;
            }
            $success = $this->placeCssDelayedCode($delay_seg, $tbl);
            if ($success)
            {
                self::$_delayable_segment_css[$key_delaytype]["success"] = true;
            }
            else
            {
                self::$_delayable_segment_css[$key_delaytype]["success"] = false;
                $app->enqueueMessage(JText::_('COM_MULTICACHE_CLASS_MULTICACHE_PAGE_CSS_PLACE_DELAY_FAILED') . $key_delaytype, 'error');
                $app->redirect('index.php?option=com_multicache&view=config&layout=edit&id=1#page-js-tweaks');
            }
        }
        if (isset(self::$_delayable_segment_css["async"]))
        {
        	//test setting async mau
        	if(!isset(self::$_promises))
        	{
        		$this->initiatePromise();
        	}
        	$this->setMau();
        	//end test
            $success = $this->placeCssAsyncInlineCode(self::$_delayable_segment_css["async"], $tbl);
            $this->prepareNoscriptAsync(self::$_delayable_segment_css["async"], $tbl);
            if ($success)
            {
                self::$_delayable_segment_css["async"]["inline_async"] = true;
            }
        }
    
    }

    protected function placeDelayedCode($grp, $tbl)
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
        $begin_comment = "/* Begin delay prepared by Multicache for " . $grp["delay_callable_url"] . "	*/";
        // $code_string = $begin_comment;
        if (! empty(self::$_jscomments))
        {
            $code_string = $begin_comment;
        }
        
        foreach ($grp["items"] as $key => $group)
        {
            $sig = $group["signature"];
            //promises checktype flag for deep embeding
            $chckTypeFLAG = false;
            if (isset(self::$_cdn_segment[$sig]) && (bool) self::$_cdn_segment[$sig] == true)
            {
                $url = self::$_cdn_segment[$sig];
                $c_string = '
' . self::$_principle_jquery_scope . '.getScript( "' . $url . '", function() {


}).fail(function() {

    console.log("loading failed in ' . $url . '" );


        });

';
                if(!empty($group['promises']))
                {
                	$c_string = $this->preparePromise($group , $c_string);
                }
                $code_string .= $c_string;
            }
            
            elseif (isset($group["internal"]) && $group["internal"] == true)
            {
                // this is src and internal
                // as were callingafter delay no need to curl ;Contrary its a double getscropt hence we will curl
                $url = $group["absolute_src"];
                $url = MulticacheHelper::checkCurlable($url);
                if (isset(self::$_mediaVersion))
                {
                    $url_temp = $url;
                    $j_uri = JURI::getInstance($url);
                    $j_uri->setVar('mediaFormat', self::$_mediaVersion);
                    $url = $j_uri->toString();
                }
                
                $begin_comment = "/* Inserted by Multicache InternalDelay source code insert	url-" . $url . "	 */";
                $end_comment = "/* end Multicache InternalDelay insert */";
                $curl_obj = MulticacheHelper::get_web_page($url);
                if ($curl_obj["http_code"] == 200)
                {
                    if ($tbl->compress_js)
                    {
                        $int_content = MulticacheJSOptimize::process($curl_obj["content"]);
                    }
                    else
                    {
                        $int_content = $curl_obj["content"];
                    }
                    // $c_string .= $begin_comment . MulticacheHelper::clean_code(trim($curl_obj["content"])) . $end_comment;
                    $c_string = ! empty(self::$_jscomments) ? $begin_comment . MulticacheHelper::clean_code(trim($int_content)) . $end_comment : MulticacheHelper::clean_code(trim($int_content));
                    if(!empty($group['promises']))
                    {
                    	$c_string = $this->preparePromise($group , $c_string );
                    }
                }
                else
                {
                    // register error
                    
                    $e_message = "	" . $curl_obj["errmsg"];
                    $app->enqueueMessage(JText::_('COM_MULTICACHE_ERROR_PAGESCRIPT_INTERNALDELAY_CURL_ERROR') . $e_message . ' response- ' . $curl_obj["http_code"], 'warning');
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
';
					if(!empty($group['promises']) && !empty($group['checktype']) && empty($group['mau']))
					{
						$c_string .= $this->successEmbedPromise($group);
						
						$chckTypeFLAG = true;
					}
$c_string .= '
}).fail(function() {
				';
if(!empty($group['promises']) && !empty($group['checktype']) && empty($group['mau']))
{
	$c_string .= $this->failEmbedPromise($group);
	
	$chckTypeFLAG = true;
}
$c_string .= '

    console.log("loading failed in ' . $url . '");


        });

';
                 if(!empty($group['promises']))
                  {
                 	$c_string = $this->preparePromise($group , $c_string , $chckTypeFLAG);
                  }
                $code_string .= $c_string;
            }
            elseif (! isset($group["internal"]) && $group["code"])
            {
                $unserialized_code = unserialize($group["serialized_code"]);
                $code = ! empty($unserialized_code) ? $unserialized_code : $group["code"];
                $begin_comment = "
                /* Multicache Insert for  code   " . str_replace("'", "", str_replace('"', "", substr($group["code"], 0, 10))) . " */
";
                
                $end_comment = "

/* end insert of code 	  " . str_replace("'", "", str_replace('"', "", substr($group["code"], 0, 10))) . " */";
                // unserialize and tie code here
                if (! empty($code))
                {
                    if ($tbl->compress_js)
                    {
                        $unserialized_code = MulticacheJSOptimize::process($code);
                    }
                    else
                    {
                        $unserialized_code = $code;
                    }
                    // $code_string .= $begin_comment . MulticacheHelper::clean_code(trim(unserialize($group["serialized_code"]))) . $end_comment;
                    //$code_string .= ! empty(self::$_jscomments) ? $begin_comment . MulticacheHelper::clean_code(trim($unserialized_code)) . $end_comment : MulticacheHelper::clean_code(trim($unserialized_code));
                    $temp_string =  ! empty(self::$_jscomments) ? $begin_comment . MulticacheHelper::clean_code(trim($unserialized_code)) . $end_comment : MulticacheHelper::clean_code(trim($unserialized_code));
                    if(!empty($group['promises']))
                    {
                    	$temp_string = $this->preparePromise($group , $temp_string);
                    }
                    $code_string .= $temp_string;
                }
                else
                {
                    // register error
                    
                    $app->enqueueMessage(JText::_('COM_MULTICACHE_CLASS_MULTICACHE_PAGE_SCRIPTS_URL_NOT_INTERNAL_NOT_CODE_INDELAY_ERROR'), 'error');
                    Return false;
                }
            }
        }
        $end_comment = "/* End of  delay prepared by Multicache for " . $grp["delay_callable_url"] . "	*/";
        // $code_string .= $end_comment;
        if (! empty(self::$_jscomments))
        {
            $code_string .= $end_comment;
        }
        ob_start();
        echo $code_string;
        $buffer = ob_get_clean();
        $return = MulticacheHelper::writeJsCache($buffer, $grp["delay_callable_url"], true);
        
        Return $return;
    
    }
    
    protected function successEmbedPromise($grp)
		{
			if(empty($grp))
			{
				Return '';
			}
			if(!isset(self::$_promises))
			{
				$this->initiatePromise();
			}
			
            $success_string = $this->getSuccessString($grp);
			Return $success_string;
		}
		protected function getSuccessString($grp = null)
		{
			$debug = false;
			$p_count = $this->getPromiseCount();
			$success_string = <<<PROMISE
			var c = 'undefined' !== p_func_$p_count.checkMate.checkType();
			if(typeof resolve !== 'undefined'  && c ){
					resolve(10);
		}
else if(c){
 p_func_$p_count.thenback();
}
else if(typeof reject !== 'undefined'){
reject();
}
else
{
PROMISE;
			if($debug)
			{
				$success_string .=  <<<PROMISE
alert('p_func_$p_count deep embed failed');
PROMISE;
			}
			$success_string .=  <<<PROMISE
}			
PROMISE;
			Return $success_string;
		}
		protected function failEmbedPromise($grp)
		{
			if(empty($grp))
			{
				Return '';
			}
			if(!isset(self::$_promises))
			{
				$this->initiatePromise();
			}
			//$p_count = $this->getPromiseCount();
			$failed_string = $this->getfailedstring();
			Return $failed_string;
		}
		protected function getfailedstring()
		{
			$failed_string = <<<PROMISE
			if(typeof reject !== 'undefined' ){
					reject();
		}
PROMISE;
			Return $failed_string;
		}
		//$chckTypeFLAG is set to true for deep embed checks
		protected function preparePromise($object , $callback , $chckTypeFLAG = false , $extend_src = false)
		{
			if(empty($object))
			{
				Return $object;
			}
			if(!isset(self::$_promises))
			{				
				$this->initiatePromise();
			}
			$debug = false;

			$p_count = $this->getPromiseCount();
			$promise_func = 'p_func_'.$p_count;
			$promise_string =  <<<PROMISE
			$promise_func = {
 'init' :  function (resolve , reject){
PROMISE;
			if($debug)
			{
				$promise_string .=  <<<PROMISE
alert('fulfilling promise $promise_func resolve ' + resolve + ' reject ' + reject);
alert('here promise ' + $p_count);
PROMISE;
			}
			
			if(!empty($extend_src))
			{
				$load_src = $object['src'];
				$promise_string .=  <<<PROMISE
$promise_func.loadsource('$load_src');
PROMISE;
			}
			else{
      $promise_string .=  <<<PROMISE
$promise_func.callback(resolve , reject);
PROMISE;
			}
			if(!empty($object['mau']))
			{
				$this->setMau();
				$mau_time = !empty($object['mautime'])?$object['mautime'] :30;
				$promise_string .=  <<<PROMISE
           multicache_MAU(resolve,reject ,$promise_func.checkMate, $mau_time);
PROMISE;
			}elseif(!$chckTypeFLAG)
				{
					$promise_string .=  $this->getSuccessString();
				}
				
$promise_string .=  <<<PROMISE

},
 'then' : function(data) {
PROMISE;
			if($debug)
			{
				$promise_string .=  <<<PROMISE
alert('Got data! Promise$p_count fulfilled.' + data);
alert('typeof ' + $promise_func.checkMate.name + ' ' + $promise_func.checkMate.checkType());
PROMISE;
			}
			$promise_string .=  <<<PROMISE
   
   $promise_func.thenback();
   
  },
  'error': function(error) {
PROMISE;
			if($debug)
			{
				$promise_string .=  <<<PROMISE
alert('Promise $promise_func rejected.');
alert(error.message);
PROMISE;
			}
			$promise_string .=  <<<PROMISE
   
  },
 'catch': function(e) { 
PROMISE;
			if($debug)
			{
				$promise_string .=  <<<PROMISE
alert('catch: ', e);
PROMISE;
			}
			$promise_string .=  <<<PROMISE
 
  },
  'checkMate' : {
PROMISE;
if(!empty($object['checktype']))
{
	$c_type = $object['checktype'];
  $promise_string .=  <<<PROMISE
  checkType : function(){
              return typeof $c_type;
              },
              name : '$c_type'
                },
PROMISE;
} else{
  	$promise_string .=  <<<PROMISE
  checkType : function(){
              return true;
  },
               name : ''
  	
                },
PROMISE;
  	
  }
  $promise_string .=  <<<PROMISE
  'callback' : function(resolve , reject){
PROMISE;
			if($debug)
			{
				$promise_string .=  <<<PROMISE
alert('confirm executing callback');
PROMISE;
			}
			
  if(empty($extend_src))
  {
  $promise_string .=  <<<PROMISE
  $callback
PROMISE;
  }
  $promise_string .=  <<<PROMISE
  
  },
 
PROMISE;
  if(!empty($extend_src))
  {
  	$promise_string .=  <<<PROMISE
  'loadsource' : function(s){
  js = document.createElement('script');
  js.src = s; 
  js.async = true;
  var ajs = document.getElementsByTagName('script')[0];
  ajs.parentNode.insertBefore(js, ajs);
PROMISE;
			if($debug)
			{
				$promise_string .=  <<<PROMISE
alert('srcipt insert succesful ' + s);
PROMISE;
			}
			$promise_string .=  <<<PROMISE
  
  },
PROMISE;
  }
 if(!empty($object['thenBack']))
{
	$t_back = trim($object['thenBack']);
	ob_start();
	echo $t_back;
	$t_back = ob_get_clean();
  $promise_string .=  <<<PROMISE
  'thenback' : function(){
PROMISE;
			if($debug)
			{
				$promise_string .=  <<<PROMISE
alert('executing then back func');
PROMISE;
			}
			$promise_string .=  <<<PROMISE
  
  $t_back
  }
PROMISE;
} else{
  	$promise_string .=  <<<PROMISE
  'thenback' : function(){
PROMISE;
			if($debug)
			{
				$promise_string .=  <<<PROMISE
alert('executing then back empty func');
PROMISE;
			}
			$promise_string .=  <<<PROMISE
  }
PROMISE;
  	
  }
  $promise_string .=  <<<PROMISE
  
};
PROMISE;
  $promise_body = <<<PROMISE
  if(window.Promise){
var promise_$p_count = new Promise($promise_func.init);
promise_$p_count.then($promise_func.then , $promise_func.error).catch($promise_func.catch);
  }
  else
  		{
  		$promise_func.callback();
  		}
  
PROMISE;
  $promise_body = $promise_string . $promise_body;
  $promise_body = MulticacheJSOptimize::process($promise_body);
  $this->incrementPromiseCount();
  Return $promise_body;
		}
		protected function initiatePromise()
		{
			if(isset(self::$_promises))
			{
				Return;
			}
			self::$_promises = array();
			self::$_promises['count'] = 0;
			self::$_promises['load_mau'] = false;
			
		}
		protected function setMau()
		{
			if(!isset(self::$_promises))
			{
				Return false;
			}
		self::$_promises['load_mau'] = true;
		}
		protected function getPromiseCount()
		{
			if(!isset(self::$_promises))
			{
				Return false;
			}
			Return self::$_promises['count'];
		}
		protected function incrementPromiseCount()
		{
			if(!isset(self::$_promises))
			{
				Return false;
			}
			self::$_promises['count']++;
		}

    protected function placeCssAsyncInlineCode($grp, $tbl)
    {

        if (empty($grp["items"]) || empty($grp["delay_callable_url"]))
        {
            Return false;
        }
        $app = JFactory::getApplication();
        $has_inline = null;
        $inline_async = '';
        foreach ($grp["items"] as $key => $group)
        {
            if (empty($group["serialized_code"]) 
            		|| (!empty($group['cdnalias']) && !empty($group['cdn_url_css'])))
            {
                continue;
            }
            $has_inline = true;
            $inline_async .= unserialize($group["serialized_code"]);
        }
        if (isset($has_inline) && ! empty($inline_async))
        {
            if ($tbl->compress_css)
            {
                $inline_async = MulticacheCSSOptimize::optimize($inline_async);
            }
            
            ob_start();
            echo $inline_async;
            $buffer = ob_get_clean();
            $return = MulticacheHelper::writeCssCache($buffer, $grp["delay_callable_url"], true);
            Return $return;
        }
        
        Return false;
    
    }

    protected function placeCssDelayedCode($grp, $tbl)
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
        $begin_comment = "<!-- Begin delay prepared by MulticacheCss for " . $grp["delay_callable_url"] . "	-->";
        // $code_string = $begin_comment;
        if (! empty(self::$_css_comments))
        {
            $code_string = $begin_comment;
        }
        
        foreach ($grp["items"] as $key => $group)
        {
            $sig = $group["signature"];
            
            if (isset(self::$_cdn_segment[$sig]) && (bool) self::$_cdn_segment[$sig] == true)
            {
                $url = self::$_cdn_segment[$sig];
                
                $c_string = MulticacheHelper::getCsslinkUrl($url, 'link_url', self::$_mediaVersion);
                $code_string .= $c_string;
                $serialized = serialize('<link type="text/css" href="' . $url . '" />');
                self::$_delayed_noscript .= MulticacheHelper::noscriptWrap($serialized);
            }
            elseif(!empty($group['cdnalias']) && !empty($group['cdn_url_css']))
            {
            	$url = $group['cdn_url_css'];
            	
            	$c_string = MulticacheHelper::getCsslinkUrl($url, 'link_url', self::$_mediaVersion);
            	$code_string .= $c_string;
            	$serialized = serialize('<link type="text/css" href="' . $url . '" />');
            	self::$_delayed_noscript .= MulticacheHelper::noscriptWrap($serialized);
            }
            elseif (! isset($group["internal"]) && ! empty($group["code"]))
            {
                $unserialized_code = unserialize($group["serialized_code"]);
                $code = ! empty($unserialized_code) ? $unserialized_code : $group["code"];
                $begin_comment = "
                <!-- Multicache Insert for  code   " . str_replace("'", "", str_replace('"', "", substr($group["code"], 0, 10))) . " -->
";
                
                $end_comment = "

<!-- end insert of code 	  " . str_replace("'", "", str_replace('"', "", substr($group["code"], 0, 10))) . " -->";
                // unserialize and tie code here
                if (! empty($code))
                {
                    // $code_string .= $begin_comment . MulticacheHelper::clean_code(trim(unserialize($group["serialized_code"]))) . $end_comment;
                    // $code_string .= ! empty(self::$_css_comments) ? $begin_comment . '<style type="text/css">' . trim(unserialize($group["serialized_code"])) . '</style>' . $end_comment : '<style type="text/css">' . trim(unserialize($group["serialized_code"])) . '</style>';
                    if ($tbl->compress_css)
                    {
                        
                        $code_string .= ! empty(self::$_css_comments) ? $begin_comment . '<style type="text/css">' . trim(MulticacheCSSOptimize::optimize($code)) . '</style>' . $end_comment : '<style type="text/css">' . trim(MulticacheCSSOptimize::optimize($code)) . '</style>';
                    }
                    else
                    {
                        $code_string .= ! empty(self::$_css_comments) ? $begin_comment . '<style type="text/css">' . trim($code) . '</style>' . $end_comment : '<style type="text/css">' . trim($code) . '</style>';
                    }
                    self::$_delayed_noscript .= '<noscript><style>' . $code_string . '</style></noscript>';
                }
                else
                {
                    // register error
                    
                    $app->enqueueMessage(JText::_('COM_MULTICACHE_CLASS_MULTICACHE_PAGE_CSS_URL_NOT_INTERNAL_NOT_CODE_INDELAY_ERROR'), 'error');
                    Return false;
                }
            }
            
            elseif (isset($group["href"]))
            {
                if (preg_match('/[^a-zA-Z0-9\/\:\?\#\.]/', $group["href"]) && empty($group["internal"]))
                {
                    $url = $group["href"];
                    $c_string = MulticacheHelper::getCsslinkUrl($url, 'plain_url', self::$_mediaVersion);
                }
                else
                {
                    $url = isset($group["absolute_src"]) ? $group["absolute_src"] : $group["href_clean"];
                    $c_string = MulticacheHelper::getCsslinkUrl($url, 'link_url', self::$_mediaVersion);
                }
                
                $code_string .= $c_string;
                self::$_delayed_noscript .= MulticacheHelper::noscriptWrap($group["serialized"]);
            }
        }
        $end_comment = "<!-- End of Css delay prepared by Multicache for " . $grp["delay_callable_url"] . "	-->";
        // $code_string .= $end_comment;
        if (! empty(self::$_css_comments))
        {
            $code_string .= $end_comment;
        }
        ob_start();
        echo $code_string;
        $buffer = ob_get_clean();
        $return = MulticacheHelper::writeCssCache($buffer, $grp["delay_callable_url"], true);
        
        Return $return;
    
    }

    protected function makeDelaycode()
    {
        // writes the first level js to be called by the main page
        if (empty(self::$_delayable_segment))
        {
            Return false;
        }
        
        foreach (self::$_delayable_segment as $key => $value)
        {
        	if($key == 'onload')
        	{
        		continue;
        	}
            $delay_code = MulticacheHelper::getdelaycode($key, self::$_principle_jquery_scope, self::$_mediaVersion); // initialises the delay code
            
            if (! empty($delay_code))
            {
                self::$_delayable_segment[$key]["delay_executable_code"] = $delay_code["code"];
                self::$_delayable_segment[$key]["delay_callable_url"] = $delay_code["url"];
            }
        }
        //for onload delay
        if(isset(self::$_delayable_segment['onload']))
        {
        	$multicache_exec_code = MulticacheHelper::getonLoadexecCode(self::$_delayable_segment['onload']['items']);
        	$delay_code = MulticacheHelper::getonLoadDelay($multicache_exec_code);
        	if(empty($delay_code))
        	{
        		self::$_delayable_segment['onload']["delay_executable_code"] = null;
        		self::$_delayable_segment['onload']["delay_callable_url"] = null;
        		self::$_delayable_segment['onload']["delay_callable_url"] = false;
        	}
        	self::$_delayable_segment['onload']["delay_executable_code"] = $delay_code;
        	self::$_delayable_segment['onload']["delay_callable_url"] = null;
        	self::$_delayable_segment['onload']["delay_callable_url"] = true;
        }
    
    }

    protected function makeCssDelaycode($params = false)
    {
        // writes the first level js to be called by the main page
        if (empty(self::$_delayable_segment_css))
        {
            Return false;
        }
        
        foreach (self::$_delayable_segment_css as $key => $value)
        {
            $delay_code = MulticacheHelper::getCssdelaycode($key, self::$_principle_jquery_scope, self::$_mediaVersion , $params); // initialises the delay code
            
            if (! empty($delay_code))
            {
                self::$_delayable_segment_css[$key]["delay_executable_code"] = $delay_code["code"];
                self::$_delayable_segment_css[$key]["delay_callable_url"] = $delay_code["url"];
            }
        }
    
    }

    protected function writeGroupCode($tbl)
    {

        if (! isset(self::$_groups))
        {
            Return false;
        }
        
        foreach (self::$_groups as $key => $grp)
        {
            if ($grp["success"])
            {
                $file_name = $grp["name"] . ".js";
                if ($tbl->compress_js)
                {
                    $unserialized_group_code = MulticacheJSOptimize::process(unserialize($grp["combined_code"]));
                }
                else
                {
                    $unserialized_group_code = unserialize($grp["combined_code"]);
                }
                
                $success = MulticacheHelper::writeJsCache($unserialized_group_code, $file_name, $tbl->js_switch);
                self::$_groups[$key]["success"] = ! empty($success) ? true : false;
            }
        }
    
    }

    protected function writeGroupCssCode($tbl)
    {

        if (! isset(self::$_groups_css))
        {
            Return false;
        }
        
        foreach (self::$_groups_css as $key => $grp)
        {
            if ($grp["success"])
            {
                $file_name = $grp["name"] . ".css";
                if ($tbl->compress_css)
                {
                    $ret_content = trim(MulticacheCSSOptimize::optimize(unserialize($grp["combined_code"])));
                }
                else
                {
                    $ret_content = trim(unserialize($grp["combined_code"]));
                }
                $success = MulticacheHelper::writeCssCache($ret_content, $file_name, $tbl->css_switch);
                self::$_groups_css[$key]["success"] = ! empty($success) ? true : false;
            }
        }
    
    }

    protected function combineGroupCode($tbl)
    {

        if (empty(self::$_groups))
        {
            Return false;
        }
        foreach (self::$_groups as $group_name => $group)
        {
            self::$_groups[$group_name]["combined_code"] = $this->getCombinedCode($group["items"], $tbl);
            self::$_groups[$group_name]["success"] = ! empty(self::$_groups[$group_name]["combined_code"]) ? true : false;
        }
    
    }

    protected function combineCssGroupCode($tbl)
    {

        if (empty(self::$_groups_css))
        {
            Return false;
        }
        
        foreach (self::$_groups_css as $group_name => $group)
        {
            self::$_groups_css[$group_name]["combined_code"] = $this->getCombinedCssCode($group["items"], $tbl);
            self::$_groups_css[$group_name]["success"] = ! empty(self::$_groups_css[$group_name]["combined_code"]) ? true : false;
        }
    
    }
    protected function combineCssDelayloadUrlToGroup($tbl)
    {
    	if (empty(self::$_delayable_segment_css) || empty(self::$_groups)
    			||!(isset(self::$_delayable_segment_css["scroll"]) 
    					|| isset(self::$_delayable_segment_css["mousemove"]))
    			)
    	{
    		Return false;
    	}
    	$index = count(self::$_groups);
    	$group = false;
    	//check for succesful combinations
    	while($index){
    		$max_group = "group-" .$index;
    		if(self::$_groups[$max_group]["success"] === true)
    		{
    			$group = $max_group;
    			break;
    		}
    		$index--;
    	}
    	if(false === $group)
    	{
    		Return false;
    	}
    	$combined_code = unserialize(self::$_groups[$group]['combined_code']);
    	if(false === $combined_code)
    	{
    		Return false;
    	}
    	$delay = null;
    	//$delay_async = null;dealing with asynchro css separately as it is not jquery dependent
    	
    		$delay = self::$_principle_jquery_scope . "( document ).ready(function() {";
    		foreach (self::$_delayable_segment_css as $delay_type_key => $delay_obj)
    		{
    			if ($delay_type_key == 'async')
    			{
    				continue;
    			}
    			if (! empty($delay_obj["delay_executable_code"]))
    			{
    				$delay .= unserialize($delay_obj["delay_executable_code"]);
    			}
    		}
    		$delay .= "});";
    		if ($tbl->compress_js)
    		{
    			$delay = trim(MulticacheJSOptimize::process($delay));
    		}
    		$combined_code .= $delay;
    		$serialized_combined_code = serialize($combined_code);
    		if(false === $serialized_combined_code)
    		{
    			Return false;
    		}
    		self::$_groups[$group]['combined_code'] = $serialized_combined_code;
    		self::$_delayable_segment_css["scroll"]["resultant_async_defer_loaded"] = true;
    		self::$_delayable_segment_css["mousemove"]["resultant_async_defer_loaded"] = true;
    	
    }
 protected function combineDelayloadUrlToGroup($tbl)
 {
 	if (empty(self::$_delayable_segment) || empty(self::$_groups))
 	{
 		Return false;
 	}
 	$index = count(self::$_groups);
 	 $group = false;
 	//check for succesful combinations
 	while($index){
 		$max_group = "group-" .$index;
 		if(self::$_groups[$max_group]["success"] === true)
 		{
 			$group = $max_group;
 			break;
 		}
 		$index--;
 	}
 	 if(false === $group)
 	 {
 	 	Return false;
 	 }
 	$combined_code = unserialize(self::$_groups[$group]['combined_code']);
 	if(false === $combined_code)
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
 	if ($tbl->compress_js)
 	{
 		$delay = trim(MulticacheJSOptimize::process($delay));
 	}
 	$combined_code .= $delay;
 	$serialized_combined_code = serialize($combined_code);
 	if(false === $serialized_combined_code)
 	{
 		Return false;
 	}
 	self::$_groups[$group]['combined_code'] = $serialized_combined_code;
 	self::$_delayable_segment["scroll"]["resultant_async_defer_loaded"] = true;
 	self::$_delayable_segment["mousemove"]["resultant_async_defer_loaded"] = true;
 	
 }
    protected function initialiseGroupHash($jsarray)
    {

        foreach ($jsarray as $key => $value)
        {
            if (isset($jsarray[$key]["group"]))
            {
                $group_number = $jsarray[$key]["group"];
                
                self::$_groups[$group_number]["name"] = $group_number;
                self::$_groups[$group_number]["url"] = null; // this is the raw url
                self::$_groups[$group_number]["callable_url"] = null; // a getScript code with url embed
                self::$_groups[$group_number]["script_tag_url"] = null; // a script taged url
                
                self::$_groups[$group_number]["combined_code"] = null;
                self::$_groups[$group_number]["success"] = null;
                
                self::$_groups[$group_number]["items"][] = $value;
            }
        }
    
    }

    protected function initialiseCssGroupHash($cssarray)
    {
    	if(empty($cssarray))
    	{
    		Return false;
    	}

        foreach ($cssarray as $key => $value)
        {
            if (isset($cssarray[$key]["group"]))
            {
                $group_number = $cssarray[$key]["group"];
                
                self::$_groups_css[$group_number]["name"] = $group_number;
                self::$_groups_css[$group_number]["url"] = null; // this is the raw url
                self::$_groups_css[$group_number]["callable_url"] = null; // a getScript code with url embed
                self::$_groups_css[$group_number]["css_tag_url"] = null; // a script taged url
                
                self::$_groups_css[$group_number]["combined_code"] = null;
                self::$_groups_css[$group_number]["success"] = null;
                
                self::$_groups_css[$group_number]["items"][] = $value;
            }
        }
    
    }

    protected function assignGroups($jsarray)
    {

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
            
            // if any one side is internal but not library group but not cdn
            // we need to account for missing keys due to setIgnore and dontLoad operations
            $prev_key = $this->getPreviousKey($key, $jsarray);
            $next_key = $this->getNextKey($key, $jsarray);
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

    protected function isGroupWorthy($cssarray, $key)
    {

        foreach ($cssarray as $key2 => $css)
        {
            if ($key == $key2)
            {
                continue;
            }
            // check loadsections && group_number
            if ($cssarray[$key]["loadsection"] == $css["loadsection"] && $cssarray[$key]["group_number"] == $css["group_number"])
            {
                Return true;
            }
        }
        Return false;
    
    }

    protected function assignCssGroups($cssarray, $table)
    {

        foreach ($cssarray as $key => $css)
        {
            
            // exclude cdns
            if (! $cssarray[$key]["grouping"] || $cssarray[$key]["loadsection"] >= 5)
            {
                
                continue;
            }
            if ($table->css_maintain_preceedence)
            {
                // if any one side is internal but not library group but not cdn
                // we need to account for missing keys due to setIgnore and dontLoad operations
                $prev_key = $this->getPreviousCssKey($key, $cssarray);
                $next_key = $this->getNextCssKey($key, $cssarray);
                if (((! empty($prev_key) || $prev_key === 0) && isset($cssarray[$prev_key]) && // following rules only if the previous key exists
($cssarray[$prev_key]["loadsection"] == $cssarray[$key]["loadsection"])) || // dont group varying loadsections
                                                                                            // should not be a cdn cdnalias
                                                                                            // can be grouped with next key
                ((! empty($next_key) || $next_key === 0) && isset($cssarray[$next_key]) && ($cssarray[$next_key]["loadsection"] == $cssarray[$key]["loadsection"])))
                // checking next internal
                {
                    
                    if ($css["group_number"] != 0)
                    {
                        
                        $group_code = "group-" . $css["loadsection"] . "-sub-" . $css["group_number"];
                    }
                    else
                    {
                        $group_code = "group-" . $css["loadsection"];
                    }
                    
                    $cssarray[$key]["group"] = $group_code;
                }
            }
            else
            {
                $group_worthy = $this->isGroupWorthy($cssarray, $key);
                if ($group_worthy)
                {
                    if ($css["group_number"] != 0)
                    {
                        
                        $group_code = "group-" . $css["loadsection"] . "-sub-" . $css["group_number"];
                    }
                    else
                    {
                        $group_code = "group-" . $css["loadsection"];
                    }
                    $cssarray[$key]["group"] = $group_code;
                }
            }
        }
        
        Return $cssarray;
    
    }

    protected function setCDNtosignature($jsarray)
    {
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

    protected function setCDNtosignatureCss($cssarray)
    {
        // get all cdn signatures
        foreach ($cssarray as $key => $css)
        {
            if (! empty($css["cdn_url_css"]))
            {
                $sig = $css["signature"];
                self::$_cdn_segment_css[$sig] = $css["cdn_url_css"];
            }
        }
        foreach ($cssarray as $key => $css)
        {
            $sig = $css["signature"];
            if (isset(self::$_cdn_segment_css[$sig]) && empty($css["cdn_url_css"]))
            {
                $cssarray[$key]["cdnaliascss"] = 1;
                $cssarray[$key]["cdn_url_css"] = self::$_cdn_segment_css[$sig];
            }
        }
        Return $cssarray;
    
    }

    protected function deferAsync($jsarray)
    {

        foreach ($jsarray as $key => $js)
        {
            if (! empty($js["async"])) // here we use !empty as a direct equivalent of isset && =true
            {
                self::$_async_segment[$key] = $jsarray[$key];
                unset($jsarray[$key]);
            }
        }
        
        Return $jsarray;
    
    }

    protected function ConfirmIgnoreDontLoad($jsarray)
    {

        foreach ($jsarray as $key => $js)
        {
            $sig = $js["signature"];
            if (isset(self::$_unset_hash[$sig]))
            {
                // were unsetting duplicated scripts that have either been ignored or set to dontload
                // this allows selecting any one script for ignore or dontload
                
                unset($jsarray[$key]); // signature_hash need not be updated as the operation is performed before its setting.
            }
        }
        Return $jsarray;
    
    }

    protected function setIgnore($jsarray)
    {

        foreach ($jsarray as $key => $js)
        {
            if (! empty($js["ignore"]))
            {
                $sig = $js["signature"];
                self::$_unset_hash[$sig] = 1;
                unset($jsarray[$key]); // signature_hash need not be updated as the operation is performed before its setting.
            }
        }
        Return $jsarray;
    
    }

    protected function setCssIgnore($css_array)
    {

        foreach ($css_array as $key => $css)
        {
            if (! empty($css["ignore"]))
            {
                unset($css_array[$key]); // signature_hash need not be updated as the operation is performed before its setting.
            }
        }
        Return $css_array;
    
    }

    protected function setDontLoad($jsarray)
    {

        $stored_dontmove_items = $this->loadProperty('dontmove');
        if (isset($stored_dontmove_items))
        {
            // extracting the url and hash
            foreach ($stored_dontmove_items as $key => $dontmove)
            {
                $sig = $dontmove["signature"];
                $alt_sig = $dontmove["alt_signature"];
                if (isset($sig))
                {
                    self::$_dontmovesignature_hash[$sig] = true;
                }
                if (isset($alt_sig))
                {
                    self::$_dontmovesignature_hash[$alt_sig] = true;
                }
                $src = $dontmove['src'];
                if (! empty($src))
                {
                    $cleaned_src = str_replace(array(
                        'https',
                        'http',
                        '://',
                        '//',
                        'www.'
                    ), '', $src);
                    self::$_dontmoveurls[$cleaned_src] = 1;
                }
            }
            self::$_dontmove_items = $stored_dontmove_items;
        }
        
        foreach ($jsarray as $key => $js)
        {
            if ($js["loadsection"] == 5)
            {
                $sig = $js["signature"];
                $alt_sig = $js["alt_signature"];
                if (isset($sig))
                {
                    self::$_signature_hash[$sig] = true;
                }
                if (isset($alt_sig))
                {
                    self::$_signature_hash[$alt_sig] = true;
                }
                self::$_unset_hash[$sig] = 1;
                unset($jsarray[$key]);
            }
            else if ($js["loadsection"] >= 6)
            {
                $sig = $js["signature"];
                $alt_sig = $js["alt_signature"];
                if (isset($sig))
                {
                    self::$_dontmovesignature_hash[$sig] = true;
                }
                if (isset($alt_sig))
                {
                    self::$_dontmovesignature_hash[$alt_sig] = true;
                }
                $src = $js['src'];
                if (! empty($src))
                {
                    $cleaned_src = str_replace(array(
                        'https',
                        'http',
                        '://',
                        '//',
                        'www.'
                    ), '', $src);
                    self::$_dontmoveurls[$cleaned_src] = 1;
                }
                self::$_dontmove_items[] = $jsarray[$key];
                self::$_unset_hash[$sig] = 1;
                unset($jsarray[$key]);
            }
        }
        Return $jsarray;
    
    }

    protected function setDontLoadCss($cssarray)
    {

        foreach ($cssarray as $key => $css)
        {
            if ($css["loadsection"] >= 5)
            {
                $sig = $css["signature"];
                $alt_sig = $css["alt_signature"];
                if (isset($sig))
                {
                    self::$_signature_hash_css[$sig] = true;
                }
                if (isset($alt_sig))
                {
                    self::$_signature_hash_css[$alt_sig] = true;
                }
                unset($cssarray[$key]);
            }
        }
        Return $cssarray;
    
    }

    protected function deferAdvertisement($jsarray)
    {

        $advertisement = null;
        $stored_advertisement = $this->loadProperty('advertisements');
        foreach ($jsarray as $key => $js)
        {
            if (! empty($js["advertisement"])) // here we use !empty as a direct equivalent of isset && =true
            {
                $advertisement[$key] = $jsarray[$key];
                unset($jsarray[$key]);
            }
        }
        if (isset($advertisement) && isset($stored_advertisement))
        {
            $advertisement = array_replace($stored_advertisement, $advertisement);
        }
        elseif (isset($stored_advertisement) && ! isset($advertisement))
        {
            $advertisement = $stored_advertisement;
        }
        
        if (! empty($advertisement))
        {
            ksort($advertisement);
            self::$_advertisement_segment = $advertisement;
        }
        Return $jsarray;
    
    }

    protected function deferSocial($jsarray)
    {

        $social = null;
        $stored_social = $this->loadProperty('social');
        foreach ($jsarray as $key => $js)
        {
            if (! empty($js["social"]) && empty($js["delay"])) // here we use !empty as a direct equivalent of isset && =true
            {
                $social[$key] = $jsarray[$key];
                unset($jsarray[$key]);
            }
        }
        
        if (isset($social) && isset($stored_social))
        {
            $social = array_replace($stored_social, $social);
        }
        elseif (isset($stored_social) && ! isset($social))
        {
            $social = $stored_social;
        }
        
        if (! empty($social))
        {
            ksort($social);
            self::$_social_segment = $social;
        }
        Return $jsarray;
    
    }

    protected function placeDuplicatesBack($jsarray, $type = 'script', $class_name = "MulticachePageScripts")
    {

        $check_property = $type == 'script' ? property_exists($class_name, 'original_script_array') : property_exists($class_name, 'original_css_array');
        if (! (class_exists($class_name) && $check_property && property_exists($class_name, 'duplicates')))
        {
            Return $jsarray;
        }
        $duplicates_array = $class_name::$duplicates;
        $original_array = $type == 'script' ? $class_name::$original_script_array : $class_name::$original_css_array; // basis for indexing
        $newjsarray = array();
        foreach ($original_array as $key => $orig)
        {
            
            if (! isset($jsarray[$key]))
            {
                $newjsarray[$key] = isset($duplicates_array[$key]) ? $duplicates_array[$key] : $orig;
            }
            else
            {
                $newjsarray[$key] = $jsarray[$key];
            }
        }
        
        Return $newjsarray;
    
    }

    protected function removeDuplicateScripts($jsarray)
    {

        foreach ($jsarray as $key => $js)
        {
            
            if (isset($jsarray[$key]["duplicate"]))
            {
                self::$_duplicates[$key] = $jsarray[$key]; // store duplicates incase its called back
                unset($jsarray[$key]);
            }
        }
        
        Return $jsarray;
    
    }

    protected function removeDuplicateCss($cssarray)
    {

        foreach ($cssarray as $key => $css)
        {
            
            if (isset($cssarray[$key]["duplicate"]))
            {
                self::$_duplicates_css[$key] = $cssarray[$key]; // store duplicates incase its called back
                unset($cssarray[$key]);
            }
        }
        
        Return $cssarray;
    
    }

    protected function performPreceedenceModeration($jsarray)
    {

        $cur_load_sec = 0;
        foreach ($jsarray as $key => $js)
        {
            if ($js["loadsection"] > $cur_load_sec && $js["loadsection"] < 5)
            {
                $cur_load_sec = $js["loadsection"];
            }
            $js["loadsection"] = $cur_load_sec;
            $jsarray[$key] = $js;
        }
        Return $jsarray;
    
    }

    protected function setSignatureHash($jsarray)
    {

        foreach ($jsarray as $key => $js)
        {
            $sig = $js["signature"];
            $alt_sig = $js["alt_signature"];
            if (isset(self::$_signature_hash[$sig]))
            {
                $jsarray[$key]["duplicate"] = true;
            }
            self::$_signature_hash[$sig] = true;
            if (isset($alt_sig) && ! isset(self::$_signature_hash[$alt_sig]))
            {
                self::$_signature_hash[$alt_sig] = true;
            }
        }
        Return $jsarray;
    
    }

    protected function setSignatureHashCss($cssarray)
    {

        foreach ($cssarray as $key => $css)
        {
            $sig = $css["signature"];
            $alt_sig = $css["alt_signature"];
            if (isset(self::$_signature_hash_css[$sig]))
            {
                $cssarray[$key]["duplicate"] = true;
            }
            self::$_signature_hash_css[$sig] = true;
            if (isset($alt_sig) && ! isset(self::$_signature_hash_css[$alt_sig]))
            {
                self::$_signature_hash_css[$alt_sig] = true;
            }
        }
        Return $cssarray;
    
    }

    protected function validatePageScript($page_new_script, $table)
    {

        $success = $this->validateLibrary($page_new_script);
        
        if (! $success)
        {
            Return false;
        }
        /*
         * Any other validation here else you can return the success flg without requiring the if return structure
         */
        // principle library cannot be delayed
        // in preceedence mode prior to principl library cannot be delayed
        $success = $this->applyDelayRules($page_new_script, $table);
        if (! $success)
        {
            Return false;
        }
        Return true;
    
    }

    protected function applyDelayRules($page_new_script, $table)
    {

        $app = JFactory::getApplication();
        // principle library cannot be delayed
        // in preceedence mode prior to principl library cannot be delayed
        $preceedence = $table->maintain_preceedence ? $table->maintain_preceedence : null;
        $delay_set = false;
        
        foreach ($page_new_script as $key => $obj)
        {
            if (! empty($obj["delay"]))
            {
                $delay_set = true;
            }
            if ($preceedence && ! empty($obj["library"]) && $delay_set)
            {
                
                // throw error scripts before primary library cannot be delayed in preceedence mode
                $app->enqueueMessage(JText::_('COM_MULTICACHE_ERROR_PAGESCRIPT_APPLYDELAYRULES_SCRIPTSBEFORELIBRARY_CANNOTDELAY'), 'error');
                Return false;
            }
            elseif (! $preceedence && ! empty($obj["library"]) && ! empty($obj["delay"]))
            {
                // throw error the priimary library cannot be delayed
                $app->enqueueMessage(JText::_('COM_MULTICACHE_ERROR_PAGESCRIPT_APPLYDELAYRULES_PRINCIPLELIBRARY_CANNOTDELAY'), 'error');
                Return false;
            }
        }
        Return true;
    
    }

    protected function validateLibrary($page_new_script)
    {

        $app = JFactory::getApplication();
        $library = false;
        $library_count = 0;
        foreach ($page_new_script as $key => $obj)
        {
            if (! empty($obj["library"]))
            {
                $library = true;
                $library_count ++;
            }
            if (! empty($obj["library"]) && ! empty($obj["ignore"]))
            {
                $app->enqueueMessage(JText::_('COM_MULTICACHE_ERROR_PAGESCRIPT_LIBRARY_CANNOTBEIGNORED'), 'warning');
                $app->redirect('index.php?option=com_multicache&view=config&layout=edit&id=1#page-js-tweaks');
            }
            
            if (! empty($obj["library"]) && $obj["loadsection"] >= 5)
            {
                $app->enqueueMessage(JText::_('COM_MULTICACHE_ERROR_PAGESCRIPT_LIBRARY_CANNOTBEUNLOADED'), 'warning');
                $app->redirect('index.php?option=com_multicache&view=config&layout=edit&id=1#page-js-tweaks');
            }
        }
        if (! $library || $library_count == 0)
        {
            $app->enqueueMessage(JText::_('COM_MULTICACHE_ERROR_PAGESCRIPT_LIBRARY_NOTSET'), 'warning');
            // if($this->getParam(0)->js_switch)
            $app->redirect('index.php?option=com_multicache&view=config&layout=edit&id=1#page-js-tweaks');
            Return false;
        }
        if ($library_count > 1)
        {
            $app->enqueueMessage(JText::_('COM_MULTICACHE_ERROR_PAGESCRIPT_LIBRARY_ONLYREQUIRESPRIMARYLIBRARY'), 'error');
            // if($this->getParam(0)->js_switch)
            $app->redirect('index.php?option=com_multicache&view=config&layout=edit&id=1#page-js-tweaks');
            Return false;
        }
        Return true;
    
    }

    protected function checkUrldburlArray($u, $type = 'google')
    {

        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select($db->quoteName('id'));
        $query->from($db->quoteName('#__multicache_urlarray'));
        $query->where($db->quoteName('url') . ' = ' . $db->quote($u));
        $query->where($db->quoteName('type') . ' = ' . $db->quote($type));
        $db->setQuery($query);
        $result = $db->loadObject();
        return (bool) $result;
    
    }

    protected function storeUrlArray($useg)
    {

        $db = JFactory::getDbo();
        
        $cache_id_array = $this->getCache_id($useg);
        $insertObj = new stdClass();
        $insertObj->url = $useg;
        $insertObj->cache_id = $cache_id_array['original'];
        $insertObj->cache_id_alt = $cache_id_array['alternate'];
        $insertObj->type = 'manual';
        $insertObj->created = date('Y-m-d');
        $result = $db->insertObject('#__multicache_urlarray', $insertObj);
    
    }

    protected function clearTable($tbl = '#__multicache_urlarray', $type = 'manual')
    {

        $db = JFactory::getDbo();
        $query = $db->getQuery('true');
        $query->delete($db->quoteName($tbl));
        $query->where($db->quoteName('type') . ' = ' . $db->quote($type));
        $db->setQuery($query);
        $db->execute();
    
    }

    protected function loadProperty($property_name, $class_name = "MulticachePageScripts")
    {

        if (! class_exists($class_name))
        {
            Return null;
        }
        
        if (! property_exists($class_name, $property_name))
        {
            Return null;
        }
        Return $class_name::$$property_name;
    
    }

    protected function resetMaxTests($budget, $id)
    {

        $db = JFactory::getDBo();
        $updateObject = new stdClass();
        $updateObject->id = $id;
        $updateObject->max_tests = $budget;
        $result = $db->updateObject('#__multicache_advanced_test_results', $updateObject, 'id');
    
    }

    protected function resetGroupCycles($tbl, $id)
    {

        $app = JFactory::getApplication();
        $min_precache = (int) $tbl->precache_factor_min;
        $max_precache = (int) $tbl->precache_factor_max;
        $min_cachecompression = (float) $tbl->gzip_factor_min; // $min_gzip -> $min_cachecompression
        $max_cachecompression = (float) $tbl->gzip_factor_max; // $max_gzip -> $max_cachecompression
        $step_cachecompression = (float) $tbl->gzip_factor_step; // $step_gzip -> $step_cachecompression
        
        $precache_sequences = ($max_precache - $min_precache) + 1;
        $step_cachecompression = empty($step_cachecompression) ? 1 : $step_cachecompression; // filtering the input for 0
        $cachecompression_sequences = (int) (($max_cachecompression - $min_cachecompression) / $step_cachecompression);
        $cachecompression_sequences = ($cachecompression_sequences <= 1) ? 1 : $cachecompression_sequences;
        if ($tbl->simulation_advanced)
        {
            if (class_exists('Loadinstruction') && property_exists('Loadinstruction', 'loadinstruction'))
            {
                $load_states = count(Loadinstruction::$loadinstruction);
                $expected_tests = $cachecompression_sequences * $precache_sequences * $load_states * $tbl->gtmetrix_cycles;
            }
            else
            {
                $app->enqueueMessage(JText::_('COM_MULTICACHE_CONFIG_ADVANCEDMODE_EXPECTED_TESTS_NOTACCURATE_SIMULATION_INITIALISATION_INCOMPLETE'), 'notice');
                $expected_tests = $cachecompression_sequences * $precache_sequences * $tbl->gtmetrix_cycles;
            }
        }
        else
        {
            $expected_tests = $cachecompression_sequences * $precache_sequences * $tbl->gtmetrix_cycles;
        }
        
        $db = JFactory::getDBo();
        $updateObject = new stdClass();
        $updateObject->id = $id;
        $updateObject->expected_tests = $expected_tests;
        $updateObject->cycles = $tbl->gtmetrix_cycles;
        $result = $db->updateObject('#__multicache_advanced_testgroups', $updateObject, 'id');
    
    }

}