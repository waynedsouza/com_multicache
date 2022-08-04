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

JLoader::register('Loadinstruction', JPATH_COMPONENT . '/lib/loadinstruction.php');
JLoader::register('JsStrategySimControl', JPATH_ROOT . '/administrator/components/com_multicache/lib/jscachestrategy_simcontrol.php');
JLoader::register('JsStrategy', JPATH_ROOT . '/administrator/components/com_multicache/lib/jscachestrategy.php');
JLog::addLogger(array(
    'text_file' => 'errors.php'
), JLog::ALL, array(
    'error'
));
use Joomla\Registry\Registry;

class MulticacheFrontendHelper
{

    public static function get_web_page($url)
    {

        if (! function_exists('curl_version'))
        {
            $app = JFactory::getApplication();
            $e_message = JText::_('COM_MULTICACHEFRONTEND_HELPER_GETWEBPAGE_CURL_DOESNOTEXIST');
            $app->enqueueMessage($e_message, 'error');
            JLog::add(JText::_($e_message), JLog::ERROR);
            Return false;
        }
        
        $user_agent = 'Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';
        
        $options = array(
            
            CURLOPT_CUSTOMREQUEST => "GET", // set request type post or get
            CURLOPT_POST => false, // set to GET
            CURLOPT_USERAGENT => $user_agent, // set user agent
            CURLOPT_COOKIEFILE => "cookie.txt", // set cookie file
            CURLOPT_COOKIEJAR => "cookie.txt", // set cookie jar
            CURLOPT_RETURNTRANSFER => true, // return web page
            CURLOPT_HEADER => false, // don't return headers
            CURLOPT_FOLLOWLOCATION => true, // follow redirects
            CURLOPT_ENCODING => "gzip,deflate", // handle only gzip & deflate encodings Joomla 3
            CURLOPT_AUTOREFERER => true, // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120, // timeout on connect
            CURLOPT_TIMEOUT => 120, // timeout on response
            CURLOPT_MAXREDIRS => 10
        ); // stop after 10 redirects
        
        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        $content = curl_exec($ch);
        $err = curl_errno($ch);
        $errmsg = curl_error($ch);
        $header = curl_getinfo($ch);
        curl_close($ch);
        
        $header['errno'] = $err;
        $header['errmsg'] = $errmsg;
        $header['content'] = $content;
        return $header;
    
    }

    public static function establish_factors($precache_factor, $gzip_factor)
    {

        require_once (JPATH_CONFIGURATION . '/configuration.php');
        // Create a JConfig object
        $config = new JConfig();
        $config->gzip_factor = $gzip_factor;
        $config->precache_factor = $precache_factor;
        $registry = new Registry();
        $registry->loadObject($config);
        self::writeConfigFile($registry);
    
    }

    public static function setJsSimulation($sim = 1, $advanced = 'normal', $load_state = null)
    {

        $advanced = ($advanced == 'advanced') ? 1 : NULL;
        
        $app = JFactory::getApplication();
        $plugin = JPluginHelper::getPlugin('system', 'multicache');
        $extensionTable = JTable::getInstance('extension');
        $pluginId = $extensionTable->find(array(
            'element' => 'multicache',
            'folder' => 'system'
        ));
        $pluginRow = $extensionTable->load($pluginId);
        $params = new JRegistry($plugin->params);
        $params->set('js_simulation', $sim);
        $params->set('js_advanced', $advanced);
        if (isset($load_state))
        {
            if ($load_state == 0)
            {
                $params->set('js_loadinstruction', null);
            }
            else
            {
                $params->set('js_loadinstruction', $load_state);
            }
        }
        $extensionTable->bind(array(
            'params' => $params->toString()
        ));
        if (! $extensionTable->check())
        {
            $app->setError('lastcreatedate: check: ' . $extensionTable->getError());
            return false;
        }
        if (! $extensionTable->store())
        {
            $app->setError('lastcreatedate: store: ' . $extensionTable->getError());
            return false;
        }
    
    }

    public static function lockSimControl($lock = 0)
    {

        $app = JFactory::getApplication();
        $plugin = JPluginHelper::getPlugin('system', 'multicache');
        $extensionTable = JTable::getInstance('extension');
        $pluginId = $extensionTable->find(array(
            'element' => 'multicache',
            'folder' => 'system'
        ));
        $pluginRow = $extensionTable->load($pluginId);
        $params = new JRegistry($plugin->params);
        if (! empty($lock))
        {
            $params->set('lock_sim_control', TRUE);
        }
        else
        {
            $params->set('lock_sim_control', false);
        }
        $extensionTable->bind(array(
            'params' => $params->toString()
        ));
        if (! $extensionTable->check())
        {
            $app->setError('lastcreatedate: check: ' . $extensionTable->getError());
            return false;
        }
        if (! $extensionTable->store())
        {
            $app->setError('lastcreatedate: store: ' . $extensionTable->getError());
            return false;
        }
        Return true;
    
    }

    /*
     * Stubs are an essential component of JsCacheSTrategy. It is used to custimise the identification process of
     * --pre header
     * --head section
     * --body section and
     * --footer.
     * Allowing a callback function to suitably place the strartegized code into.
     */
    public static function prepareStubs($tbl)
    {

        if (! empty($tbl->pre_head_stub_identifiers))
        {
            $head_open = json_decode($tbl->pre_head_stub_identifiers);
        }
        else
        {
            $head_open = array(
                "<head>"
            );
        }
        if (! empty($tbl->head_stub_identifiers))
        {
            $head = json_decode($tbl->head_stub_identifiers);
        }
        else
        {
            $head = array(
                "</head>"
            );
        }
        if (! empty($tbl->body_stub_identifiers))
        {
            $body = json_decode($tbl->body_stub_identifiers);
        }
        else
        {
            $body = array(
                "<body>"
            );
        }
        if (! empty($tbl->footer_stub_identifiers))
        {
            $footer = json_decode($tbl->footer_stub_identifiers);
        }
        else
        {
            $footer = array(
                "</body>"
            );
        }
        
        $stub_identifiers = array(
            "head_open" => $head_open,
            "head" => $head,
            "body" => $body,
            "footer" => $footer
        );
        
        Return serialize($stub_identifiers);
    
    }

    public static function PrepareJSTexcludes($url_include_switch, $query_include_switch, $urls, $queries, $component_excludes, $url_string_excludes)
    {

        if (empty($urls) && empty($queries) && empty($component_excludes) && empty($url_string_excludes))
        {
            Return null;
        }
        $excurl = null;
        $q_val = null;
        if (! empty($urls))
        {
            $excurl = array();
            foreach ($urls as $url)
            {
                $excurl[$url] = 1;
            }
        }
        if (! empty($queries))
        {
            $q_val = array();
            foreach ($queries as $query)
            {
                $split = explode("=", $query);
                $key = $split[0];
                $value = isset($split[1]) ? $split[1] : 1;
                $q_val[$key][$value] = 1;
            }
        }
        
        $jsexcludes = new stdClass();
        $jsexcludes->urlswitch = $url_include_switch;
        $jsexcludes->queryswitch = $query_include_switch;
        $jsexcludes->settings = array(
            "url_switch" => $url_include_switch,
            "query_switch" => $query_include_switch
        );
        $jsexcludes->url = isset($excurl) ? $excurl : null;
        $jsexcludes->query = isset($q_val) ? $q_val : null;
        $jsexcludes->url_strings = ! empty($url_string_excludes) ? $url_string_excludes : null;
        $jsexcludes->component = ! empty($component_excludes) ? $component_excludes : null;
        Return $jsexcludes;
    
    }

    public static function clean_cache($group, $id = null)
    {

        if (isset($id))
        {
            $id2 = substr($id, - 1) != '/' ? $id . '/' : null;
            
            $id3 = substr($id, - 1) == '/' ? substr_replace($id, '', - 1) : null;
        }
        
        $cache = self::getCache();
        $cache->clean($group, 'both');
        if (isset($id))
        {
            $cache->remove($id, 'page');
        }
        // two variants
        if (isset($id2))
        {
            $cache->remove($id2, 'page');
        }
        if (isset($id3))
        {
            $cache->remove($id3, 'page');
        }
        
        $cache->clean('_system', 'both');
        $cache->clean('com_config', 'both');
        $cache = self::getCache(true);
        $cache->clean($group);
        if (isset($id))
        {
            $cache->remove($id, 'page');
        }
        // two variants
        if (isset($id2))
        {
            $cache->remove($id2, 'page');
        }
        if (isset($id3))
        {
            $cache->remove($id3, 'page');
        }
        $cache->clean('_system', 'both');
        $cache->clean('com_config', 'both');
    
    }

    public static function writeLoadInstructions($preset, $loadinstruction, $working_instruction, $original_instruction, $pagescripts, $allow_multiple_orphaned)
    {

        if (! isset($pagescripts['working_script_array']))
        {
            Return false;
        }
        if (empty($preset))
        {
            if (! class_exists('Loadinstruction'))
            {
                Return false;
            }
            if (property_exists('Loadinstruction', 'preset'))
            {
                $preset = Loadinstruction::$preset;
            }
        }
        
        if (empty($loadinstruction))
        {
            if (! class_exists('Loadinstruction'))
            {
                Return false;
            }
            if (property_exists('Loadinstruction', 'loadinstruction'))
            {
                $loadinstruction = Loadinstruction::$loadinstruction;
            }
        }
        
        if (empty($working_instruction))
        {
            if (! class_exists('Loadinstruction'))
            {
                Return false;
            }
            if (property_exists('Loadinstruction', 'working_instruction'))
            {
                $working_instruction = Loadinstruction::$working_instruction;
            }
        }
        
        if (empty($original_instruction))
        {
            if (! class_exists('Loadinstruction'))
            {
                Return false;
            }
            if (property_exists('Loadinstruction', 'original_instruction'))
            {
                $original_instruction = Loadinstruction::$original_instruction;
            }
        }
        
        $preset = var_export($preset, true);
        $loadinstruction = var_export($loadinstruction, true);
        $working_instruction = var_export($working_instruction, true);
        $original_instruction = var_export($original_instruction, true);
        $working_script_array = var_export($pagescripts['working_script_array'], true);
        $social = ! empty($pagescripts['social']) ? var_export($pagescripts['social'], true) : null;
        $advertisements = ! empty($pagescripts['advertisements']) ? var_export($pagescripts['advertisements'], true) : null;
        $async = ! empty($pagescripts['async']) ? var_export($pagescripts['async'], true) : null;
        $delayed = ! empty($pagescripts['delayed']) ? var_export($pagescripts['delayed'], true) : null;
        $dontmove = ! empty($pagescripts['dontmove']) ? var_export($pagescripts['dontmove'], true) : null;
        if (isset($allow_multiple_orphaned) && is_array($allow_multiple_orphaned))
        {
            $allow_multiple_orphaned = var_export($allow_multiple_orphaned, true);
        }
        elseif (empty($allow_multiple_orphaned))
        {
            $allow_multiple_orphaned = null;
        }
        
        ob_start();
        echo "<?php

/**
 *
 * @version 1.0.1.5
 * @package com_multicache
 * @copyright Copyright (C) Multicache.org 2015. All rights reserved.
 * @licenseEULA see LICENSE.txt - http://multicache.org/terms-conditions/end-user-license-agreement.html
 * @author Wayne DSouza <consulting@OnlineMarketingConsultants.in> - http://OnlineMarketingConsultants.in
 * @description Multicache Loadinstruction Cache Strategy Map
 * @message: This class may be overwritten automatically - make your changes in the control panel
 *
 */


defined('JPATH_PLATFORM') or die;

class Loadinstruction{

";
        $cl_buf = ob_get_clean();
        if (! empty($preset))
        {
            ob_start();
            echo "

public static \$preset  = " . trim($preset) . ";

";
            $cl_buf .= ob_get_clean();
        }
        
        if (! empty($loadinstruction))
        {
            
            ob_start();
            echo "


public static \$loadinstruction  = " . trim($loadinstruction) . ";

";
            $cl_buf .= ob_get_clean();
        }
        
        if (! empty($working_instruction))
        {
            
            ob_start();
            echo "


public static \$working_instruction  = " . trim($working_instruction) . ";

";
            $cl_buf .= ob_get_clean();
        }
        
        if (! empty($original_instruction))
        {
            
            ob_start();
            echo "


public static \$original_instruction  = " . trim($original_instruction) . ";

";
            $cl_buf .= ob_get_clean();
        }
        
        // start
        if (! empty($working_script_array))
        {
            
            ob_start();
            echo "


public static \$working_script_array  = " . $working_script_array . ";

";
            $cl_buf .= ob_get_clean();
        }
        
        if (! empty($social))
        {
            
            ob_start();
            echo "


public static \$social  = " . $social . ";

";
            $cl_buf .= ob_get_clean();
        }
        
        if (! empty($advertisements))
        {
            
            ob_start();
            echo "


public static \$advertisements  = " . $advertisements . ";

";
            $cl_buf .= ob_get_clean();
        }
        
        if (! empty($async))
        {
            
            ob_start();
            echo "


public static \$async  = " . $async . ";

";
            $cl_buf .= ob_get_clean();
        }
        
        if (! empty($delayed))
        {
            
            ob_start();
            echo "


public static \$delayed  = " . $delayed . ";

";
            $cl_buf .= ob_get_clean();
        }
        // end
        
        if (! empty($dontmove))
        {
            
            ob_start();
            echo "


public static \$dontmove  = " . $dontmove . ";

";
            $cl_buf .= ob_get_clean();
        }
        
        if (! empty($allow_multiple_orphaned))
        {
            
            ob_start();
            echo "


public static \$allow_multiple_orphaned  = " . $allow_multiple_orphaned . ";

";
            $cl_buf .= ob_get_clean();
        }
        
        ob_start();
        echo "
        }
        ";
        $cl_buf .= ob_get_clean();
        
        $cl_buf = serialize($cl_buf);
        
        $dir = JPATH_ROOT . '/components/com_multicache/lib';
        $filename = 'loadinstruction.php';
        $success = self::writefileTolocation($dir, $filename, $cl_buf);
        Return $success;
        // $success = self::writeLoadinstructionset(serialize($cl_buf));
    }

    public static function getJScodeUrl($load_state, $key, $type = null, $jquery_scope = "$", $media = "default")
    {

        $base_url = '//' . str_replace("http://", "", strtolower(substr(JURI::root(), 0, - 1)) . '/media/com_multicache/assets/js/jscache/');
        
        if (isset($type) && $type == "raw_url")
        {
            Return $base_url . $key . '-' . $load_state . ".js?mediaVersion=" . $media;
        }
        // script_url
        
        if (isset($type) && $type == "script_url")
        {
            $script = '<script src="' . $base_url . $key . '-' . $load_state . '.js?mediaVersion=' . $media . '"   type="text/javascript" ></script>';
            Return serialize($script);
        }
        $url = $jquery_scope . '.getScript(' . '"' . $base_url . $key . '-' . $load_state . '.js?mediaVersion=' . $media . '"' . ');';
        
        Return serialize($url);
    
    }

    public static function getMediaFormat($length = 4)
    {

        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i ++)
        {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    
    }

    public static function clean_code($code_string)
    {

        if (! empty($code_string) && substr($code_string, - 1, 1) != ";")
        {
            $code_string = $code_string . ";";
        }
        Return $code_string;
    
    }

    public static function writeJsCache($obj, $filename)
    {

        $dir = JURI::root() . 'media/com_multicache/assets/js/jscache';
        if (! is_dir($dir))
        {
            
            // Make sure the index file is there
            $indexFile = $dir . '/index.html';
            @mkdir($dir) && file_put_contents($indexFile, '<!DOCTYPE html><title></title>');
        }
        if (! is_dir($dir))
        {
            Return false;
        }
        $cl_buf = serialize($obj);
        
        $success = self::writefileTolocation($dir, $filename, $cl_buf);
        Return $success;
    
    }

    public static function getdelaycode($delay_type, $jquery_scope = "$", $mediaFormat)
    {

        $app = JFactory::getApplication();
        // $delay_type = self::extractDelayType($delay_array);
        $base_url = '//' . str_replace("http://", "", strtolower(substr(JURI::root(), 0, - 1)) . '/media/com_multicache/assets/js/jscache/');
        if ($delay_type == "scroll")
        {
            $name = "simcontrol_onscrolldelay.js";
            $url = $base_url . $name;
            $inline_code = '
var url_' . $delay_type . ' = "' . $url . '?mediaFormat=' . $mediaFormat . '";var script_delay_' . $delay_type . '_counter = 0;var max_trys_' . $delay_type . ' = 3;var inter_lock_' . $delay_type . '= 1;' . $jquery_scope . '(window).scroll(function() {/*alert("count "+script_delay_' . $delay_type . '_counter;*/console.log("count " + script_delay_' . $delay_type . '_counter);console.log("scroll detected" );if(!inter_lock_' . $delay_type . '){return;/*an equivalent to continue*/}++script_delay_' . $delay_type . '_counter;if(script_delay_' . $delay_type . '_counter <=  max_trys_' . $delay_type . ') {inter_lock_' . $delay_type . ' = 0;console.log("getscrpt call on ' . $delay_type . '" );' . $jquery_scope . '.getScript( url_' . $delay_type . ', function() {' . $jquery_scope . '( this ).unbind( "scroll" );script_delay_' . $delay_type . '_counter =  max_trys_' . $delay_type . '+1;}).fail(function() {/*alert("loading failed" + url_' . $delay_type . ');*/console.log("loading failed in " + url_' . $delay_type . ' +" trial "+ script_delay_' . $delay_type . '_counter);inter_lock_' . $delay_type . ' = 1;});}else{/* alert("giving up");*/' . $jquery_scope . '( this ).unbind( "scroll" );console.log("failed scroll loading  "+ url_' . $delay_type . '+"  giving up" );}});';
        }
        elseif ($delay_type == "mousemove")
        {
            $name = "simcontrol_onmousemovedelay.js";
            $url = $base_url . $name;
            $inline_code = '
var url_' . $delay_type . ' = "' . $url . '?mediaFormat=' . $mediaFormat . '";var script_delay_' . $delay_type . '_counter = 0;var max_trys_' . $delay_type . ' = 3;var inter_lock_' . $delay_type . '= 1;' . $jquery_scope . '(window).on("mousemove" ,function( event ) {/*alert("count "+script_delay_counter);*/console.log("count " + script_delay_' . $delay_type . '_counter);console.log("mousemove detected" );if(!inter_lock_' . $delay_type . '){return;/*an equivalent to continue*/}++script_delay_' . $delay_type . '_counter;if(script_delay_' . $delay_type . '_counter <= max_trys_' . $delay_type . ') {inter_lock_' . $delay_type . ' = 0;console.log("getscrpt call on ' . $delay_type . '" );' . $jquery_scope . '.getScript( url_' . $delay_type . ', function() {' . $jquery_scope . '( this ).unbind( "mousemove" );script_delay_' . $delay_type . '_counter =  max_trys_' . $delay_type . ';}).fail(function() {/*alert("loading failed" + url_' . $delay_type . ');*/console.log("loading failed in " + url_' . $delay_type . '  +" trial "+ script_delay_' . $delay_type . '_counter);inter_lock_' . $delay_type . ' = 1;});}else{/* alert("giving up");*/' . $jquery_scope . '( this ).unbind( "mousemove" );console.log("failed loading "+ url_' . $delay_type . '+"  giving up" );}});';
        }
        else
        {
            $app->enqueueMessage(JText::_('COM_MULTICACHE_ERROR_JQUERY_DELAY_TYPE_UNLISTED_CONDITION'), 'notice');
        }
        
        $obj["code"] = serialize($inline_code);
        $obj["url"] = $name;
        
        Return $obj;
    
    }

    public static function getloadableSourceScript($script_bit, $async = false)
    {

        if (empty($script_bit))
        {
            Return false;
        }
        
        if ($async)
        {
            Return '<script src="' . $script_bit . '" async type="text/javascript"></script>';
        }
        Return '<script src="' . $script_bit . '" type="text/javascript"></script>';
    
    }

    public static function getloadableCodeScript($code_bit, $async = false)
    {

        if (empty($code_bit))
        {
            Return false;
        }
        
        if ($async)
        {
            Return '<script  async type="text/javascript">' . unserialize($code_bit) . '</script>';
        }
        Return '<script  type="text/javascript">' . unserialize($code_bit) . '</script>';
    
    }

    public static function writeJsCacheStrategy($signature_hash, $loadsection, $switch = null, $load_state = null, $stubs = null, $JSTexclude = null, $dontmove_js = null, $dontmove_urls_js = null)
    {

        if (empty($signature_hash))
        {
            if (! class_exists('JsStrategySimControl'))
            {
                Return false;
            }
            if (method_exists('JsStrategySimControl', 'getJsSignature'))
            {
                $signature_hash = JsStrategySimControl::getJsSignature();
            }
            else
            {
                $signature_hash = null;
            }
        }
        if (empty($loadsection))
        {
            if (! class_exists('JsStrategySimControl'))
            {
                Return false;
            }
            if (method_exists('JsStrategySimControl', 'getLoadSection'))
            {
                $loadsection = JsStrategySimControl::getLoadSection();
            }
            else
            {
                $loadsection = null;
            }
        }
        
        $file = JPATH_ADMINISTRATOR . '/components/com_multicache/lib/jscachestrategy_simcontrol.php';
        $signature_hash = preg_replace('/\s/', '', var_export($signature_hash, true));
        $signature_hash = str_replace(',)', ')', $signature_hash);
        $loadsection = var_export($loadsection, true);
        $load_state = isset($load_state) ? var_export($load_state, true) : null;
        
        $stubs = var_export($stubs, true);
        if (! empty($JSTexclude->url))
        {
            $JSTurl = preg_replace('/\s/', '', var_export($JSTexclude->url, true));
            $JSTurl = str_replace(',)', ')', $JSTurl);
        }
        if (! empty($JSTexclude->query))
        {
            $JSTquery = preg_replace('/\s/', '', var_export($JSTexclude->query, true));
            $JSTquery = str_replace(',)', ')', $JSTquery);
        }
        if (! empty($JSTexclude->settings))
        {
            $JSTsettings = var_export($JSTexclude->settings, true);
        }
        if (! empty($JSTexclude->component))
        {
            $JSTcomponents = preg_replace('/\s/', '', var_export($JSTexclude->component, true));
            $JSTcomponents = str_replace(',)', ')', $JSTcomponents);
        }
        if (! empty($JSTexclude->url_strings))
        {
            $JSTurlstrings = preg_replace('/\s/', '', var_export($JSTexclude->url_strings, true));
            $JSTurlstrings = str_replace(',)', ')', $JSTurlstrings);
        }
        // $dontmove_js
        // $dontmove_urls_js
        if (! empty($dontmove_js))
        {
            $dontmove_js = preg_replace('/\s/', '', var_export($dontmove_js, true));
            $dontmove_js = str_replace(',)', ')', $dontmove_js);
        }
        if (! empty($dontmove_urls_js))
        {
            $dontmove_urls_js = var_export($dontmove_urls_js, true);
            // $dontmove_urls_js = str_replace(',)', ')', $dontmove_urls_js);
        }
        
        if (property_exists('Loadinstruction', 'allow_multiple_orphaned'))
        {
            $allow_multiple_orphaned = Loadinstruction::$allow_multiple_orphaned;
        }
        
        ob_start();
        echo "<?php
/**
 *
 * @version 1.0.1.5
 * @package com_multicache
 * @copyright Copyright (C) Multicache.org 2015. All rights reserved.
 * @licenseGNU GENERAL PUBLIC LICENSE see LICENSE.txt - http://multicache.org/terms-conditions/end-user-license-agreement.html
 * @author Wayne DSouza <consulting@OnlineMarketingConsultants.in> - http://OnlineMarketingConsultants.in
 * @description MulticacheSimControl javascript strategy handler
 * @message This class may be overwritten automatically - make your changes in the control panel
 *
 */


defined('JPATH_PLATFORM') or die;
class JsStrategySimControl{
public static \$js_switch = " . $switch . "	;

public static \$simulation_id = " . $load_state . "	;


public static \$stubs = " . $stubs . " ;
    ";
        $cl_buf = ob_get_clean();
        if (! empty($JSTexclude->settings) && (! empty($JSTexclude->url) || ! empty($JSTexclude->query)))
        {
            ob_start();
            echo "
public static \$JSTsetting = " . $JSTsettings . ";
  ";
            $cl_buf .= ob_get_clean();
        }
        if (! empty($JSTexclude->url))
        {
            ob_start();
            echo "
public static \$JSTCludeUrl = " . $JSTurl . ";
  ";
            $cl_buf .= ob_get_clean();
        }
        
        if (! empty($JSTexclude->query))
        {
            
            ob_start();
            echo "
public static \$JSTCludeQuery = " . $JSTquery . ";
  ";
            $cl_buf .= ob_get_clean();
        }
        if (! empty($JSTexclude->component))
        {
            
            ob_start();
            echo "
public static \$JSTexcluded_components = " . $JSTcomponents . ";
  ";
            $cl_buf .= ob_get_clean();
        }
        if (! empty($JSTexclude->url_strings))
        {
            
            ob_start();
            echo "
public static \$JSTurl_strings = " . $JSTurlstrings . ";
  ";
            $cl_buf .= ob_get_clean();
        }
        
        if (! empty($dontmove_js))
        {
            
            ob_start();
            echo "
public static \$dontmove_js = " . $dontmove_js . ";
  ";
            $cl_buf .= ob_get_clean();
        }
        if (! empty($dontmove_urls_js))
        {
            
            ob_start();
            echo "
public static \$dontmove_urls_js = " . $dontmove_urls_js . ";
  ";
            $cl_buf .= ob_get_clean();
        }
        
        if (! empty($allow_multiple_orphaned))
        {
            
            ob_start();
            echo "
public static \$allow_multiple_orphaned = " . $allow_multiple_orphaned . ";
  ";
            $cl_buf .= ob_get_clean();
        }
        ob_start();
        echo "


public static function getJsSignature(){
\$sigss = " . trim($signature_hash) . ";
Return \$sigss;
}


public static function getLoadSection(){
\$loadsec = " . trim($loadsection) . ";
Return \$loadsec;
}


}
?>";
        $cl_buf .= ob_get_clean();
        $cl_buf = serialize($cl_buf);
        
        $dir = JPATH_ADMINISTRATOR . '/components/com_multicache/lib';
        $filename = 'jscachestrategy_simcontrol.php';
        $success = self::writefileTolocation($dir, $filename, $cl_buf);
        Return $success;
    
    }

    public static function writeJsCacheStrategyMain($signature_hash, $loadsection, $switch, $load_state, $stubs = null, $JSTexclude = null, $dontmove_js = null, $dontmove_urls_js = null)
    {

        if (empty($signature_hash) || empty($loadsection) || ! isset($switch) || ! isset($load_state))
        {
            Return false;
        }
        
        $signature_hash = preg_replace('/\s/', '', var_export($signature_hash, true));
        $signature_hash = str_replace(',)', ')', $signature_hash);
        $loadsection = var_export($loadsection, true);
        $load_state = isset($load_state) ? var_export($load_state, true) : null;
        $stubs = var_export($stubs, true);
        if (! empty($JSTexclude->url))
        {
            $JSTurl = preg_replace('/\s/', '', var_export($JSTexclude->url, true));
            $JSTurl = str_replace(',)', ')', $JSTurl);
        }
        if (! empty($JSTexclude->query))
        {
            $JSTquery = preg_replace('/\s/', '', var_export($JSTexclude->query, true));
            $JSTquery = str_replace(',)', ')', $JSTquery);
        }
        if (! empty($JSTexclude->settings))
        {
            $JSTsettings = var_export($JSTexclude->settings, true);
        }
        if (! empty($JSTexclude->component))
        {
            $JSTcomponents = preg_replace('/\s/', '', var_export($JSTexclude->component, true));
            $JSTcomponents = str_replace(',)', ')', $JSTcomponents);
        }
        if (! empty($JSTexclude->url_strings))
        {
            $JSTurlstrings = preg_replace('/\s/', '', var_export($JSTexclude->url_strings, true));
            $JSTurlstrings = str_replace(',)', ')', $JSTurlstrings);
        }
        // $dontmove_js
        // $dontmove_urls_js
        if (! empty($dontmove_js))
        {
            $dontmove_js = preg_replace('/\s/', '', var_export($dontmove_js, true));
            $dontmove_js = str_replace(',)', ')', $dontmove_js);
        }
        if (! empty($dontmove_urls_js))
        {
            $dontmove_urls_js = var_export($dontmove_urls_js, true);
            // $dontmove_urls_js = str_replace(',)', ')', $dontmove_urls_js);
        }
        if (property_exists('Loadinstruction', 'allow_multiple_orphaned'))
        {
            $allow_multiple_orphaned = Loadinstruction::$allow_multiple_orphaned;
        }
        if (class_exists('JsStrategy'))
        {
            $other_vars = get_class_vars('JsStrategy');
            $additional_vars = new stdClass();
            foreach ($other_vars as $key => $var)
            {
                switch ($key)
                {
                    case 'js_switch':
                    case 'simulation_id':
                    case 'stubs':
                    case 'JSTsetting':
                    case 'JSTCludeUrl':
                    case 'JSTCludeQuery':
                    case 'JSTexcluded_components':
                    case 'JSTurl_strings':
                    case 'dontmove_js':
                    case 'dontmove_urls_js':
                    case 'allow_multiple_orphaned':
                        continue 2; // switch is considered a loop for the purposes o continue
                }
                
                $additional_vars->$key = var_export($var, true);
            }
        }
        
        ob_start();
        echo "<?php
/**
*
* @version 1.0.1.5
* @package com_multicache
* @copyright Copyright (C) Multicache.org 2015. All rights reserved.
* @licenseEULA see LICENSE.txt - http://multicache.org/terms-conditions/end-user-license-agreement.html
* @author Wayne DSouza <consulting@OnlineMarketingConsultants.in> - http://OnlineMarketingConsultants.in
* @desription MulticacheJSCacheStrategy authored by MulticacheSimControl
* @message This class may be overwritten automatically - make your changes in the control panel
*/

        defined('JPATH_PLATFORM') or die();
class JsStrategy{
public static \$js_switch = " . $switch . ";

public static \$simulation_id = " . $load_state . "	;

public static \$stubs = " . $stubs . " ;
     ";
        $cl_buf = ob_get_clean();
        if (! empty($JSTexclude->settings) && (! empty($JSTexclude->url) || ! empty($JSTexclude->query)))
        {
            ob_start();
            echo "
public static \$JSTsetting = " . $JSTsettings . ";
  ";
            $cl_buf .= ob_get_clean();
        }
        if (! empty($JSTexclude->url))
        {
            ob_start();
            echo "
public static \$JSTCludeUrl = " . $JSTurl . ";
  ";
            $cl_buf .= ob_get_clean();
        }
        
        if (! empty($JSTexclude->query))
        {
            
            ob_start();
            echo "
public static \$JSTCludeQuery = " . $JSTquery . ";
  ";
            $cl_buf .= ob_get_clean();
        }
        if (! empty($JSTexclude->component))
        {
            
            ob_start();
            echo "
public static \$JSTexcluded_components = " . $JSTcomponents . ";
  ";
            $cl_buf .= ob_get_clean();
        }
        if (! empty($JSTexclude->url_strings))
        {
            
            ob_start();
            echo "
public static \$JSTurl_strings = " . $JSTurlstrings . ";
  ";
            $cl_buf .= ob_get_clean();
        }
        
        if (! empty($additional_vars))
        {
            
            ob_start();
            foreach ($additional_vars as $key => $var)
            {
                echo "
public static \$$key = " . $var . ";
  ";
            }
            $cl_buf .= ob_get_clean();
        }
        if (! empty($dontmove_js))
        {
            
            ob_start();
            echo "
public static \$dontmove_js = " . $dontmove_js . ";
  ";
            $cl_buf .= ob_get_clean();
        }
        if (! empty($dontmove_urls_js))
        {
            
            ob_start();
            echo "
public static \$dontmove_urls_js = " . $dontmove_urls_js . ";
  ";
            $cl_buf .= ob_get_clean();
        }
        if (! empty($allow_multiple_orphaned))
        {
            
            ob_start();
            echo "
public static \$allow_multiple_orphaned = " . $allow_multiple_orphaned . ";
  ";
            $cl_buf .= ob_get_clean();
        }
        ob_start();
        echo "



public static function getJsSignature(){
\$sigss = " . trim($signature_hash) . ";
Return \$sigss;
}


public static function getLoadSection(){
\$loadsec = " . trim($loadsection) . ";
Return \$loadsec;
}


}
?>";
        $cl_buf .= ob_get_clean();
        $cl_buf = serialize($cl_buf);
        
        $dir = JPATH_ADMINISTRATOR . '/components/com_multicache/lib';
        $filename = 'jscachestrategy.php';
        $success = self::writefileTolocation($dir, $filename, $cl_buf);
        Return $success;
    
    }
    
    // start transport from WP
    public static function largeIntCompare($a, $b, $s = null)
    {
        // check if they're valid positive numbers, extract the whole numbers and decimals
        if (! preg_match("~^\+?(\d+)(\.\d+)?$~", $a, $match1) || ! preg_match("~^\+?(\d+)(\.\d+)?$~", $b, $match2))
        {
            return false;
        }
        
        // remove leading zeroes from whole numbers
        $a = ltrim($match1[1], '0');
        $b = ltrim($match2[1], '0');
        
        // first, we can just check the lengths of the numbers, this can help save processing time
        // if $a is longer than $b, return 1.. vice versa with the next step.
        if (strlen($a) > strlen($b))
        {
            return 1;
        }
        else
        {
            if (strlen($a) < strlen($b))
            {
                return - 1;
            }
            
            // if the two numbers are of equal length, we check digit-by-digit
            else
            {
                
                // remove ending zeroes from decimals and remove point
                $decimal1 = isset($match1[2]) ? rtrim(substr($match1[2], 1), '0') : '';
                $decimal2 = isset($match2[2]) ? rtrim(substr($match2[2], 1), '0') : '';
                
                // scaling if defined
                if ($s !== null)
                {
                    $decimal1 = substr($decimal1, 0, $s);
                    $decimal2 = substr($decimal2, 0, $s);
                }
                
                // calculate the longest length of decimals
                $DLen = max(strlen($decimal1), strlen($decimal2));
                
                // append the padded decimals onto the end of the whole numbers
                $a .= str_pad($decimal1, $DLen, '0');
                $b .= str_pad($decimal2, $DLen, '0');
                
                // check digit-by-digit, if they have a difference, return 1 or -1 (greater/lower than)
                for ($i = 0; $i < strlen($a); $i ++)
                {
                    if ((int) $a{$i} > (int) $b{$i})
                    {
                        return 1;
                    }
                    else if ((int) $a{$i} < (int) $b{$i})
                    {
                        return - 1;
                    }
                }
                
                // if the two numbers have no difference (they're the same).. return 0
                return 0;
            }
        }
    
    }
    
    // end transport from WP
    protected static function writefileTolocation($dir, $filename, $contents)
    {

        $app = JFactory::getApplication();
        jimport('joomla.filesystem.path');
        jimport('joomla.filesystem.file');
        
        $file = $dir . '/' . $filename;
        $ftp = JClientHelper::getCredentials('ftp', true);
        
        // Attempt to make the file writeable if using FTP.
        if (! $ftp['enabled'] && file_exists($file) && JPath::isOwner($file) && ! JPath::setPermissions($file, '0644'))
        {
            $emessage = "COM_MULTICACHE_FRONTENDHELPER_ERROR_WRITEFILETOLOCATION_FILELOC_NOTWRITABLE";
            $app->enqueueMessage(JText::_($emessage), 'warning');
            JLog::add(JText::_($emessage) . '	' . $file, JLog::WARNING);
        }
        $class_path = unserialize($contents);
        $class_path = str_ireplace("\x0D", "", $class_path);
        if (! JFile::write($file, $class_path))
        {
            $emessage = "COM_MULTICACHE_FRONTEND_HELPER_ERROR_WRITE_FILETOLOCATION_FAILED";
            throw new RuntimeException(JText::_($emessage) . '	' . $file);
            JLog::add(JText::_($emessage) . '	' . $file, JLog::ERROR);
        }
        
        // Attempt to make the file unwriteable if using FTP.
        if (! $ftp['enabled'] && file_exists($file) && JPath::isOwner($file) && ! JPath::setPermissions($file, '0444'))
        {
            $emessage = "COM_MULTICACHE_FRONTENDHELPER_WRITEFILETOLOCATION_ERROR_NOTUNWRITABLE";
            $app->enqueueMessage(JText::_($emessage) . '	' . $file, 'warning');
            JLog::add(JText::_($emessage) . '	' . $file, JLog::WARNING);
        }
        
        return true;
    
    }

    protected static function writeConfigFile(JRegistry $config)
    {

        jimport('joomla.filesystem.path');
        jimport('joomla.filesystem.file');
        
        // Set the configuration file path.
        $file = JPATH_CONFIGURATION . '/configuration.php';
        
        // Get the new FTP credentials.
        $ftp = JClientHelper::getCredentials('ftp', true);
        
        $app = JFactory::getApplication();
        
        // Attempt to make the file writeable if using FTP.
        if (! $ftp['enabled'] && JPath::isOwner($file) && ! JPath::setPermissions($file, '0644'))
        {
            $app->enqueueMessage(JText::_('COM_MULTICACHEFRONTEND_ERROR_CONFIGURATION_PHP_NOTWRITABLE'), 'notice');
        }
        
        // Attempt to write the configuration file as a PHP class named JConfig.
        $configuration = $config->toString('PHP', array(
            'class' => 'JConfig',
            'closingtag' => false
        ));
        
        if (! JFile::write($file, $configuration))
        {
            throw new RuntimeException(JText::_('COM_MULTICACHE_ERROR_WRITE_FAILED'));
        }
        
        // Attempt to make the file unwriteable if using FTP.
        if (! $ftp['enabled'] && JPath::isOwner($file) && ! JPath::setPermissions($file, '0444'))
        {
            $app->enqueueMessage(JText::_('COM_MULTICACHE_ERROR_CONFIGURATION_PHP_NOTUNWRITABLE'), 'notice');
        }
        
        return true;
    
    }

    protected static function getCache($admin = NULL)
    {

        $conf = JFactory::getConfig();
        
        $options = array(
            'defaultgroup' => '',
            'storage' => $conf->get('cache_handler', ''),
            'caching' => true,
            'cachebase' => $admin ? JPATH_ADMINISTRATOR . '/cache' : $conf->get('cache_path', JPATH_SITE . '/cache')
        );
        
        $cache = JCache::getInstance('', $options);
        
        return $cache;
    
    }

}