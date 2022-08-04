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

JLoader::register('Services_WTF_Test', JPATH_COMPONENT . '/lib/Services_WTF_Test.php');
JLoader::register('MulticachePageScripts', JPATH_ROOT . '/administrator/components/com_multicache/lib/pagescripts.php');
JLog::addLogger(array(
    'text_file' => 'errors.php'
), JLog::ALL, array(
    'error'
));
JLoader::register('JsStrategy', JPATH_ROOT . '/administrator/components/com_multicache/lib/jscachestrategy.php');

use Joomla\Registry\Registry;

/**
 * Multicache helper.
 */
class MulticacheHelper
{

    protected static $_unique_script = null;

    /**
     * Gets a list of the actions that can be performed.
     *
     * @return JObject
     * @since 1.6
     */
    public static function getActions()
    {

        $user = JFactory::getUser();
        $result = new JObject();
        
        $assetName = 'com_multicache';
        
        $actions = array(
            'core.admin',
            'core.manage',
            'core.create',
            'core.edit',
            'core.edit.own',
            'core.edit.state',
            'core.delete'
        );
        
        foreach ($actions as $action)
        {
            $result->set($action, $user->authorise($action, $assetName));
        }
        
        return $result;
    
    }

    /**
     * Configure the Linkbar.
     */
    public static function addSubmenu($vName = '')
    {

        JHtmlSidebar::addEntry(JText::_('COM_MULTICACHE_TITLE_ADVANCED_SIMULATION_MULTICACHE_DASHBOARD'), 'index.php?option=com_multicache&view=advancedsimulation', $vName == 'advancedsimulation');
        
        JHtmlSidebar::addEntry(JText::_('COM_MULTICACHE_TITLE_MULTICACHE_URL_DASHBOARD'), 'index.php?option=com_multicache&view=urls', $vName == 'urls');
        JHtmlSidebar::addEntry(JText::_('COM_MULTICACHE_TITLE_MULTICACHE_GROUP_CACHE'), 'index.php?option=com_multicache&view=multicache', $vName == 'multicache');
        JHtmlSidebar::addEntry(JText::_('COM_MULTICACHE_TITLE_MULTICACHE_PAGE_CACHE'), 'index.php?option=com_multicache&view=pagecache', $vName == 'pagecache');
    
    }

    /**
     * Get a list of filter options for the application clients.
     *
     * @return array An array of JHtmlOption elements.
     */
    public static function getClientOptions()
    {
        // Build the filter options.
        $options = array();
        $options[] = JHtml::_('select.option', '0', JText::_('JSITE'));
        $options[] = JHtml::_('select.option', '1', JText::_('JADMINISTRATOR'));
        return $options;
    
    }

    public static function getHammerOptions()
    {

        $options = array();
        $options[] = JHtml::_('select.option', '0', JText::_('COM_MULTICACHE_MCD_CART_MODE'));
        $options[] = JHtml::_('select.option', '1', JText::_('COM_MULTICACHE_MCD_MULTIADMIN_MODE'));
        $options[] = JHtml::_('select.option', '2', JText::_('COM_MULTICACHE_MCD_PAGE_STRICT'));
        $options[] = JHtml::_('select.option', '3', JText::_('COM_MULTICACHE_MCD_PAGE_HIGH_HAMMERED'));
        return $options;
    
    }

    public static function setLockOff()
    {

        $app = JFactory::getApplication();
        $plugin = JPluginHelper::getPlugin('system', 'multicache');
        $extensionTable = JTable::getInstance('extension');
        $pluginId = $extensionTable->find(array(
            'element' => 'multicache',
            'folder' => 'system'
        ));
        if (! isset($pluginId))
        {
            $app->enqueueMessage(JText::_('COM_MULTICACHE_REQUIRES_MULTICACHE_PLUGIN'), 'error');
            Return false;
        }
        $pluginRow = $extensionTable->load($pluginId);
        $params = new JRegistry($plugin->params);
        $params->set('lock_sim_control', FALSE);
        $extensionTable->bind(array(
            'params' => $params->toString()
        ));
        if (! $extensionTable->check())
        {
            $app->enqueueMessage(JText::_('COM_MULTICACHE_HELPER_SETLOCKOFF_FAILED_TABLECHECK') . '  ' . $extensionTable->getError(), 'error');
            return false;
        }
        if (! $extensionTable->store())
        {
            $app->enqueueMessage(JText::_('COM_MULTICACHE_HELPER_SETLOCKOFF_FAILED_TABLECHECK') . '  ' . $extensionTable->getError(), 'error');
            return false;
        }
    
    }

    public static function getSimObj()
    {
        // Build the filter options.
        $options = array();
        $options[] = JHtml::_('select.option', 'simulation', JText::_('COM_MULTICACHE_OPTIONS_SIMULATION'));
        $options[] = JHtml::_('select.option', 'fixed', JText::_('COM_MULTICACHE_OPTIONS_FIXED'));
        return $options;
    
    }

    public static function getComponentOptions()
    {

        $options = array();
        $options[] = JHtml::_('select.option', '0', JText::_('COM_MULTICACHE_CONFIG_COMPONENT_OPTION_BLANK_LABEL'));
        $options[] = JHtml::_('select.option', '1', JText::_('COM_MULTICACHE_CONFIG_COMPONENT_OPTION_EXCLUDE_LABEL'));
        return $options;
    
    }

    public static function getCompleteFlag()
    {
        // Build the filter options.
        $options = array();
        $options[] = JHtml::_('select.option', 'show_inprogress', JText::_('COM_MULTICACHE_OPTIONS_SHOW_INPROGRESS'));
        $options[] = JHtml::_('select.option', 'show_only_complete', JText::_('COM_MULTICACHE_OPTIONS_SHOW_COMPLETE'));
        return $options;
    
    }

    public static function checkCurlable($u)
    {
    	if(strpos(trim($u) , 'http://') !== 0 && strpos(trim($u) , 'https://') !== 0)
    	{
    		//check for //
    		if(strpos($u, '//') === 0)
    		{
    			$u = JURI::getInstance()->isSSL()?  'https:'.$u: 'http:'.$u;
    		}
    		elseif(preg_match('~^[a-zA-Z0-9]+~' , $u))
    		{
    			$u = JURI::getInstance()->isSSL()?  'https://'.$u: 'http://'.$u;
    		}
    	}
    	Return $u;
    }
    
    public static function getTolerances()
    {
        // Build the filter options.
        $options = array();
        $options[] = JHtml::_('select.option', 'show_danger_tolerance', JText::_('COM_MULTICACHE_OPTIONS_TOLERANCE_SHOW_DANGER'));
        $options[] = JHtml::_('select.option', 'show_warning_tolerance', JText::_('COM_MULTICACHE_OPTIONS_TOLERANCE_SHOW_WARNING'));
        $options[] = JHtml::_('select.option', 'show_success_tolerance', JText::_('COM_MULTICACHE_OPTIONS_TOLERANCE_SHOW_SUCCESS'));
        $options[] = JHtml::_('select.option', 'show_unhighlighted_tolerance', JText::_('COM_MULTICACHE_OPTIONS_TOLERANCE_SHOW_UNHIGHLIGHTED'));
        return $options;
    
    }

    public static function getCacheStandardOptions()
    {

        $options = array();
        $options[] = JHtml::_('select.option', '1', JText::_('COM_MULTICACHE_CACHESTANDARDOPTIONS_STANDARD_LABEL'));
        $options[] = JHtml::_('select.option', '2', JText::_('COM_MULTICACHE_CACHESTANDARDOPTIONS_NONSTANDARD_LABEL'));
        return $options;
    
    }

    public static function getCacheTypes()
    {
        // Build the filter options.
        $options = array();
        $options[] = JHtml::_('select.option', '1', JText::_('COM_MULTICACHE_OPTION_CACHE_TYPE_FILE'));
        $options[] = JHtml::_('select.option', '2', JText::_('COM_MULTICACHE_OPTION_CACHE_TYPE_MEMCACHE'));
        return $options;
    
    }

    public static function getUniqueScripts($page_scripts_array)
    {

        $unique_scripts = array();
        foreach ($page_scripts_array as $page_script)
        {
            $sig = $page_script["signature"];
            $unique_scripts[$sig] = $page_script;
        }
        self::$_unique_script = $unique_scripts;
        Return count($unique_scripts);
    
    }

    public static function getUniqueScriptAsArray()
    {

        Return isset(self::$_unique_script) ? self::$_unique_script : false;
    
    }

    public static function getPageCssObject($page_css)
    {

        if (empty($page_css))
        {
            Return false;
        }
        $page_css_obj = array();
        $clean_code = array(
            "'",
            '"',
            " ",
            ";"
        );
        
        foreach ($page_css as $key => $css)
        {
            
            $page_css_obj[$key]["name"] = ! empty($css["href"]) ? (string) $css["href"] : (string) strip_tags(substr(str_replace($clean_code, '', $css["code"]), 0, 120));
            $page_css_obj[$key]["signature"] = $css["signature"];
            
            // get loadsection options
            $options = self::getLoadSectionOptions();
            $selected = isset($css["loadsection"]) ? $css["loadsection"] : 0;
            $attribs = 'class=" com_multicache_cssloadsection " style="width:110px;"'; // make this out of params
            $loadsection[$key] = JHTML::_('select.genericlist', $options, 'com_multicache_cssloadsection_' . $key, $attribs, 'value', 'text', $selected, false, false);
            $page_css_obj[$key]["loadsection"] = JHTML::_('select.genericlist', $options, 'com_multicache_cssloadsection_' . $key, $attribs, 'value', 'text', $selected, false, false);
            if (isset($selected))
            {
                $page_css_obj[$key]["params"]["loadsection"] = $selected;
            }
            
            // get delay options
            $options = self::getGenericYesNo();
            $selected = isset($css["delay"]) ? $css["delay"] : null;
            $delay[$key] = JHTML::_('select.genericlist', $options, 'com_multicache_cssdelay_' . $key, $attribs, 'value', 'text', $selected, false, false);
            $page_css_obj[$key]["delay"] = JHTML::_('select.genericlist', $options, 'com_multicache_cssdelay_' . $key, $attribs, 'value', 'text', $selected, false, false);
            
            // set the delay params
            if (! empty($selected))
            {
                $page_css_obj[$key]["params"]["delay"] = $selected;
            }
            
            // get delay type
            $options = self::getCssDelayTypes();
            $selected = isset($css["delay_type"]) ? $css["delay_type"] : 'async';
            $attribs = 'style="width:120px;"'; // make this out of params
            $delaytype[$key] = JHTML::_('select.genericlist', $options, 'com_multicache_cssdelay_type_' . $key, $attribs, 'value', 'text', $selected, false, false);
            $page_css_obj[$key]["delay_type"] = JHTML::_('select.genericlist', $options, 'com_multicache_cssdelay_type_' . $key, $attribs, 'value', 'text', $selected, false, false);
            
            // load CDN alias
            $options = self::getGenericYesNo();
            $selected = isset($css["cdnalias"]) ? $css["cdnalias"] : null;
            $attribs = 'style="width:60px;"'; // make this out of params
            $cdnAlias[$key] = JHTML::_('select.genericlist', $options, 'com_multicache_csscdnalias_' . $key, $attribs, 'value', 'text', $selected, false, false);
            $page_css_obj[$key]["cdnAlias"] = JHTML::_('select.genericlist', $options, 'com_multicache_csscdnalias_' . $key, $attribs, 'value', 'text', $selected, false, false);
            // place the cdn_url only if it exists
            if (isset($css["cdn_url_css"]))
            {
                $page_css_obj[$key]["params"]["cdn_url_css"] = $css["cdn_url_css"];
            }
            // get ignore options
            $options = self::getGenericYesNo();
            $selected = isset($css["ignore"]) ? $css["ignore"] : null;
            $ignore[$key] = JHTML::_('select.genericlist', $options, 'com_multicache_cssignore_' . $key, $attribs, 'value', 'text', $selected, false, false);
            $page_css_obj[$key]["ignore"] = JHTML::_('select.genericlist', $options, 'com_multicache_cssignore_' . $key, $attribs, 'value', 'text', $selected, false, false);
            // get grouping options
            $options = self::getGenericYesNo();
            $isInternal = (isset($css["internal"]) && $css["internal"] == "internal") ? 1 : 0;
            $splIdentifiers = (isset($css["attributes"]) && ! empty($css["attributes"])) ? 1 : 0;
            // $selected = isset($css["grouping"]) ? $css["grouping"] : ((! $isInternal || $splIdentifiers) ? 1 : 0);
            $selected = isset($css["grouping"]) ? $css["grouping"] : 1;
            $grouping[$key] = JHTML::_('select.genericlist', $options, 'com_multicache_cssgrouping_' . $key, $attribs, 'value', 'text', $selected, false, false);
            $page_css_obj[$key]["grouping"] = JHTML::_('select.genericlist', $options, 'com_multicache_cssgrouping_' . $key, $attribs, 'value', 'text', $selected, false, false);
            
            // get group number
            $options = self::getCssGroupNumber();
            $attribs = 'style="width:120px;"'; // make this out of params
            $selected = isset($css["group_number"]) ? $css["group_number"] : null;
            $group_number[$key] = JHTML::_('select.genericlist', $options, 'com_multicache_cssgroup_number_' . $key, $attribs, 'value', 'text', $selected, false, false);
            $page_css_obj[$key]["group_number"] = JHTML::_('select.genericlist', $options, 'com_multicache_cssgroup_number_' . $key, $attribs, 'value', 'text', $selected, false, false);
        }
        
        $CssTransposeObject = new stdClass();
        $CssTransposeObject->loadsection = $loadsection;
        $CssTransposeObject->delay = $delay;
        $CssTransposeObject->delay_type = $delaytype;
        $CssTransposeObject->cdnalias = $cdnAlias;
        $CssTransposeObject->ignore = $ignore;
        $CssTransposeObject->grouping = $grouping;
        $CssTransposeObject->group_number = $group_number;
        
        $CssScriptObject = new stdClass();
        $CssScriptObject->cssobject = $page_css_obj;
        $CssScriptObject->CssTransposeObject = $CssTransposeObject;
        
        Return $CssScriptObject;
    
    }

    public static function prepareScriptLazy($jquery_scope = "jQuery")
    {

        $inline_code = $jquery_scope . '(function() {' . $jquery_scope . '("img.multicache_lazy").show().lazyload();});';
        
        //
        Return serialize($inline_code);
    
    }

    public static function prepareStylelazy()
    {

        $inline_code = '.multicache_lazy {display: none;}';
        Return serialize($inline_code);
    
    }

    public static function prepareLazyloadParams($tbl, $script, $style)
    {

        $params = array();
        $params["llswitch"] = $tbl->image_lazy_switch;
        $params["container_selector_switch"] = $tbl->image_lazy_container_switch;
        $params["container_rules"] = ! empty($tbl->image_lazy_container_strings) ? serialize(self::decodeObj($tbl->image_lazy_container_strings)) : null;
        $params["img_selectors_switch"] = $tbl->image_lazy_image_selector_include_switch;
        $params["img_selector_rules"] = ! empty($tbl->image_lazy_image_selector_include_strings) ? serialize(self::decodeObj($tbl->image_lazy_image_selector_include_strings)) : null;
        $params["image_deselector_switch"] = $tbl->image_lazy_image_selector_exclude_switch;
        $params["image_deselector_rules"] = ! empty($tbl->image_lazy_image_selector_exclude_strings) ? serialize(self::decodeObj($tbl->image_lazy_image_selector_exclude_strings)) : null;
        $params["ll_script"] = $script; // pre serialized
        $params["ll_style"] = $style; // pre serialized
        Return serialize($params);
    
    }

    protected static function decodeObj($obj)
    {

        $obj = json_decode($obj);
        Return $obj;
    
    }

    public static function makeWinSafeArray($array)
    {

        If (empty($array) || !is_array($array))
        {
            Return $array;
        }
        
        $array = array_map(array(
            'self',
            'removeCarriage'
        ), $array);
        $array = array_filter($array);
        Return array_map('trim', $array);
    
    }

    protected static function removeCarriage($s1)
    {

        Return preg_replace('~\\r~', '', $s1);
    
    }

    public static function getPageScriptObject($page_scripts_array)
    {

        if (empty($page_scripts_array))
        {
            Return false;
        }
        $page_obj = array();
        $clean_code = array(
            "'",
            '"',
            " ",
            ";"
        );
        
        foreach ($page_scripts_array as $key => $script)
        {
            
            $page_obj[$key]["name"] = ! empty($script["src"]) ? (string) $script["src"] : (string) strip_tags(substr(str_replace($clean_code, '', $script["code"]), 0, 120));
            $page_obj[$key]["signature"] = $script["signature"];
            
            // get library options
            
            $options = self::getGenericYesNo();
            $selected = isset($script["library"]) ? $script["library"] : 0;
            $attribs = 'style="width:60px;"'; // make this out of params
            $library[$key] = JHTML::_('select.genericlist', $options, 'com_multicache_library_' . $key, $attribs, 'value', 'text', $selected, false, false);
            $page_obj[$key]["library"] = JHTML::_('select.genericlist', $options, 'com_multicache_library_' . $key, $attribs, 'value', 'text', $selected, false, false);
            
            if (isset($selected))
            {
                $page_obj[$key]["params"]["library"] = $selected;
            }
            // get loadsection options
            $options = self::getLoadSectionOptions();
            $selected = isset($script["loadsection"]) ? $script["loadsection"] : 0;
            $attribs = 'class=" com_multicache_loadsection " style="width:110px;"'; // make this out of params
            $loadsection[$key] = JHTML::_('select.genericlist', $options, 'com_multicache_loadsection_' . $key, $attribs, 'value', 'text', $selected, false, false);
            $page_obj[$key]["loadsection"] = JHTML::_('select.genericlist', $options, 'com_multicache_loadsection_' . $key, $attribs, 'value', 'text', $selected, false, false);
            if (isset($selected))
            {
                $page_obj[$key]["params"]["loadsection"] = $selected;
            }
            
            // get advertisement options
            $options = self::getGenericYesNo();
            $selected = isset($script["advertisement"]) ? $script["advertisement"] : null;
            $attribs = 'style="width:60px;"'; // make this out of params
            $advertisement[$key] = JHTML::_('select.genericlist', $options, 'com_multicache_advertisement_' . $key, $attribs, 'value', 'text', $selected, false, false);
            $page_obj[$key]["advertisement"] = JHTML::_('select.genericlist', $options, 'com_multicache_advertisement_' . $key, $attribs, 'value', 'text', $selected, false, false);
            
            // get social options
            $options = self::getGenericYesNo();
            $selected = isset($script["social"]) ? $script["social"] : null;
            
            $social[$key] = JHTML::_('select.genericlist', $options, 'com_multicache_social_' . $key, $attribs, 'value', 'text', $selected, false, false);
            $page_obj[$key]["social"] = JHTML::_('select.genericlist', $options, 'com_multicache_social_' . $key, $attribs, 'value', 'text', $selected, false, false);
            
            // get delay options
            $options = self::getGenericYesNo();
            $selected = isset($script["delay"]) ? $script["delay"] : null;
            $delay[$key] = JHTML::_('select.genericlist', $options, 'com_multicache_delay_' . $key, $attribs, 'value', 'text', $selected, false, false);
            $page_obj[$key]["delay"] = JHTML::_('select.genericlist', $options, 'com_multicache_delay_' . $key, $attribs, 'value', 'text', $selected, false, false);
            
            // set the delay params
            if (! empty($selected))
            {
                $page_obj[$key]["params"]["delay"] = $selected;
            }
            
            // get delay type
            $options = self::getDelayTypes();
            $selected = isset($script["delay_type"]) ? $script["delay_type"] : 'mousemove';
            $attribs = 'style="width:120px;"'; // make this out of params
            $delaytype[$key] = JHTML::_('select.genericlist', $options, 'com_multicache_delay_type_' . $key, $attribs, 'value', 'text', $selected, false, false);
            $page_obj[$key]["delay_type"] = JHTML::_('select.genericlist', $options, 'com_multicache_delay_type_' . $key, $attribs, 'value', 'text', $selected, false, false);
            
            
            if (isset($script["delay_type"]))
            {
            	$page_obj[$key]["params"]["delay_type"] = $selected;
            }
            if (isset($script["ident"]))
            {
            	$page_obj[$key]["params"]["ident"] = $script["ident"];
            }
            
            //start
            //promises
            $options = self::getGenericYesNo();
            $selected = isset($script["promises"]) ? $script["promises"] : null;
             $attribs = 'style="width:60px;"'; // make this out of params
           // $title_tag = __('Wrap in promise','multicache-plugin');
            
            $promises[$key] = JHTML::_('select.genericlist', $options, 'com_multicache_promises_' . $key, $attribs, 'value', 'text', $selected, false, false);
            $page_obj[$key]["promises"] = JHTML::_('select.genericlist', $options, 'com_multicache_promises_' . $key, $attribs, 'value', 'text', $selected, false, false);
            if (isset($script["promises"]))
            {
            	$page_obj[$key]["params"]["promises"] = $selected;
            }
            //MAU promises
            $options = self::getGenericYesNo();
            $selected = isset($script["mau"]) ? $script["mau"] : null;
            // $attribs = 'style="width:60px;"'; // make this out of params
           // $title_tag = __('Incorporate MAU multicache Async Utility','multicache-plugin');
           
            $mau[$key] = JHTML::_('select.genericlist', $options, 'com_multicache_mau_' . $key, $attribs, 'value', 'text', $selected, false, false);
            $page_obj[$key]["mau"] = JHTML::_('select.genericlist', $options, 'com_multicache_mau_' . $key, $attribs, 'value', 'text', $selected, false, false);
            if (isset($script["mau"]))
            {
            	$page_obj[$key]["params"]["mau"] = $selected;
            }
            //$delaytype[$key] = JHTML::_('select.genericlist', $options, 'com_multicache_delay_type_' . $key, $attribs, 'value', 'text', $selected, false, false);
            //$page_obj[$key]["delay_type"] = JHTML::_('select.genericlist', $options, 'com_multicache_delay_type_' . $key, $attribs, 'value', 'text', $selected, false, false);
            
            if (isset($script["checktype"]))
            {
            	$page_obj[$key]["params"]["checktype"] = $script["checktype"];
            }
            if (isset($script["thenBack"]))
            {
            	$page_obj[$key]["params"]["thenBack"] = $script["thenBack"];
            }
            if (isset($script["mautime"]))
            {
            	$page_obj[$key]["params"]["mautime"] = $script["mautime"];
            }
            //end
            
            // load CDN alias
            $options = self::getGenericYesNo();
            $selected = isset($script["cdnalias"]) ? $script["cdnalias"] : null;
            $attribs = 'style="width:60px;"'; // make this out of params
            $cdnAlias[$key] = JHTML::_('select.genericlist', $options, 'com_multicache_cdnalias_' . $key, $attribs, 'value', 'text', $selected, false, false);
            $page_obj[$key]["cdnAlias"] = JHTML::_('select.genericlist', $options, 'com_multicache_cdnalias_' . $key, $attribs, 'value', 'text', $selected, false, false);
            // place the cdn_url only if it exists
            if (isset($script["cdn_url"]))
            {
                $page_obj[$key]["params"]["cdn_url"] = $script["cdn_url"];
            }
            // get ignore options
            $options = self::getGenericYesNo();
            $selected = isset($script["ignore"]) ? $script["ignore"] : null;
            $ignore[$key] = JHTML::_('select.genericlist', $options, 'com_multicache_ignore_' . $key, $attribs, 'value', 'text', $selected, false, false);
            $page_obj[$key]["ignore"] = JHTML::_('select.genericlist', $options, 'com_multicache_ignore_' . $key, $attribs, 'value', 'text', $selected, false, false);
        }
        
        $pageTransposeObject = new stdClass();
        $pageTransposeObject->library = $library;
        $pageTransposeObject->loadsection = $loadsection;
        $pageTransposeObject->advertisement = $advertisement;
        $pageTransposeObject->social = $social;
        $pageTransposeObject->delay = $delay;
        $pageTransposeObject->delay_type = $delaytype;
        $pageTransposeObject->promises = $promises;
        $pageTransposeObject->mau = $mau;
        $pageTransposeObject->cdnalias = $cdnAlias;
        $pageTransposeObject->ignore = $ignore;
        
        $pageScriptObject = new stdClass();
        $pageScriptObject->pageobject = $page_obj;
        $pageScriptObject->pagetransposeobject = $pageTransposeObject;
        
        Return $pageScriptObject;
    
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

    /*
     * public static function PrepareCSSexcludes($url_include_switch, $query_include_switch, $urls, $queries, $component_excludes, $url_string_excludes)
     * {
     *
     * if (empty($urls) && empty($queries) && empty($component_excludes) && empty($url_string_excludes))
     * {
     * Return null;
     * }
     * $excurl = null;
     * $q_val = null;
     *
     * if (! empty($urls))
     * {
     * $excurl = array();
     * foreach ($urls as $url)
     * {
     * $excurl[$url] = 1;
     * }
     * }
     * if (! empty($queries))
     * {
     * $q_val = array();
     * foreach ($queries as $query)
     * {
     * $split = explode("=", $query);
     * $key = $split[0];
     * $value = isset($split[1]) ? $split[1] : 1;
     * $q_val[$key][$value] = 1;
     * }
     * }
     *
     * $cssexcludes = new stdClass();
     * $cssexcludes->urlswitch = $url_include_switch;
     * $cssexcludes->queryswitch = $query_include_switch;
     * $cssexcludes->settings = array(
     * "url_switch" => $url_include_switch,
     * "query_switch" => $query_include_switch
     * );
     * $cssexcludes->url = isset($excurl) ? $excurl : null;
     * $cssexcludes->query = isset($q_val) ? $q_val : null;
     * $cssexcludes->url_strings = ! empty($url_string_excludes) ? $url_string_excludes : null;
     * $cssexcludes->component = ! empty($component_excludes) ? $component_excludes : null;
     * Return $cssexcludes;
     *
     * }
     */
    public static function getAllComponents()
    {

        $ignorable = array(
            'com_admin',
            'com_cache',
            'com_categories',
            'com_checkin',
            'com_config',
            'com_cpanel',
            'com_installer',
            'com_languages',
            'com_media',
            'com_menus',
            'com_messages',
            'com_modules',
            'com_plugins',
            'com_redirect',
            'com_templates'
        );
        
        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->select('element')
            ->from('#__extensions')
            ->where('type = ' . $db->quote('component'))
            ->where('enabled = 1');
        $db->setQuery($query);
        $result = $db->loadColumn();
        if (isset($result) && is_array($result))
        {
            $result = array_diff($result, $ignorable);
        }
        
        return $result;
    
    }
    // The Cart Object is not prepared in the frontend helper as it is asumed to be activated in backend
    public static function prepareCartObject($urls, $session_vars = null, $cart_diff = null, $cart_mode = null, $distribution = null)
    {

        if (empty($urls) && empty($session_vars) && empty($cart_diff))
        {
            Return false;
        }
        if (is_array($urls))
        {
            $cart_urls = array();
            foreach ($urls as $url)
            {
                $cart_urls[$url] = 1;
            }
        }
        else
        {
            $cart_urls = null;
        }
        $cart_urls = preg_replace('/\s/', '', var_export($cart_urls, true));
        $cart_urls = str_replace(',)', ')', $cart_urls);
        
        $session_items = null;
        if (! empty($session_vars) && is_array($session_vars))
        {
            $session_vars = array_filter($session_vars);
            if (! empty($session_vars))
            {
                foreach ($session_vars as $key => $var)
                {
                    if (! empty($var))
                    {
                        $parts = (explode(',', $var));
                        $session_items[$parts[0]] = $parts[1];
                    }
                }
            }
        }
        
        // $session_items = array_filter($session_items);
        
        $session_items = preg_replace('/\s/', '', var_export($session_items, true));
        $session_items = str_replace(',)', ')', $session_items);
        
        $diff_vars = null;
        if (! empty($cart_diff) && is_array($cart_diff))
        {
            $cart_diff = array_filter($cart_diff);
            if (! empty($cart_diff))
            {
                foreach ($cart_diff as $key => $diff_v)
                {
                    
                    if (! empty($diff_v))
                    {
                        $diff_vars[$diff_v] = 1;
                    }
                }
            }
        }
        
        $diff_vars = preg_replace('/\s/', '', var_export($diff_vars, true));
        $diff_vars = str_replace(',)', ')', $diff_vars);
        
        $settings = array(
            'cart_mode' => $cart_mode,
            'distribution' => $distribution
        );
        $settings = preg_replace('/\s/', '', var_export($settings, true));
        $settings = str_replace(',)', ')', $settings);
        $cart_mode = var_export($cart_mode, true);
        $distribution = var_export($distribution, true);
        
        ob_start();
        echo "<?php
/**
 * @MulticacheCart Object
 * @version 1.0.1.5
 * @package com_multicache
 * @copyright Copyright (C) Multicache.org 2015. All rights reserved.
 * @licenseGNU GENERAL PUBLIC LICENSE  see LICENSE.txt - http://multicache.org/terms-conditions/end-user-license-agreement.html
 * @author Wayne DSouza <consulting@OnlineMarketingConsultants.in> - http://OnlineMarketingConsultants.in
 * @This class may be overwritten automatically - make your changeds in the control panel
 */

defined('JPATH_PLATFORM') or die;
class CartMulticache{

public static \$vars = array('urls' => " . trim($cart_urls) . ", 'cart_diff_vars' => " . trim($diff_vars) . " , 'session_vars' => " . trim($session_items) . " , 'cart_mode' => " . trim($cart_mode) . " , 'distribution' => " . trim($distribution) . ");

public static \$session_vars = " . trim($session_items) . ";


public static \$cart_settings	= " . trim($settings) . ";
}
?>";
        $cl_buf = ob_get_clean();
        $cl_buf = serialize($cl_buf);
        
        $dir = JPATH_ADMINISTRATOR . '/components/com_multicache/lib';
        $filename = 'cartmulticache.php';
        $success = self::writefileTolocation($dir, $filename, $cl_buf);
        Return $success;
    
    }

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

    public static function setJsSwitch($js_switch, $conduit_switch = null, $testing_switch = null, $advanced = null, $js_comments = null, $debug_mode, $orphaned = null, $css_switch, $css_comments, $compress_css, $minify_html, $compress_js, $orphaned_styles_loading, $img_tweaks , $css_groups_async = false)
    {

        $app = JFactory::getApplication();
        $plugin = JPluginHelper::getPlugin('system', 'multicache');
        $extensionTable = JTable::getInstance('extension');
        $pluginId = $extensionTable->find(array(
            'element' => 'multicache',
            'folder' => 'system'
        ));
        if (! isset($pluginId))
        {
            $app->enqueueMessage(JText::_('COM_MULTICACHE_REQUIRES_MULTICACHE_PLUGIN'), 'error');
            Return false;
        }
        $pluginRow = $extensionTable->load($pluginId);
        $params = new JRegistry($plugin->params);
        $params->set('js_switch', $js_switch);
        $params->set('css_switch', $css_switch);
        $params->set('css_comments', $css_comments);
        $params->set('compress_css', $compress_css);
        $params->set('minify_html', $minify_html);
        $params->set('compress_js', $compress_js);
        $params->set('img_tweaks', $img_tweaks);
        $params->set('css_groupsasync' , $css_groups_async);
        
        if (isset($conduit_switch))
        {
            $params->set('conduit_switch', $conduit_switch);
        }
        if (isset($testing_switch))
        {
            $params->set('testing_switch', $testing_switch);
        }
        if (isset($advanced))
        {
            $params->set('js_advanced', $advanced);
        }
        if (isset($js_comments))
        {
            $params->set('js_comments', $js_comments);
        }
        if (isset($orphaned))
        {
            $params->set('js_orphaned', $orphaned);
        }
        if (isset($orphaned_styles_loading))
        {
            $params->set('orphaned_styles_loading', $orphaned_styles_loading);
        }
        $params->set('debug_mode', $debug_mode);
        $extensionTable->bind(array(
            'params' => $params->toString()
        ));
        if (! $extensionTable->check())
        {
            $e_message = 'lastcreatedate: check: ' . $extensionTable->getError();
            $app->enqueueMessage($e_message, 'error');
            return false;
        }
        if (! $extensionTable->store())
        {
            $e_message = 'lastcreatedate: store: ' . $extensionTable->getError();
            $app->enqueueMessage($e_message, 'error');
            
            return false;
        }
        Return true;
    
    }
    
    protected static function specify($a)
    {
    	$str_len = strlen($a);
    	 
    	if($str_len <= 5)
    	{
    		Return null;
    	}
    	Return $a;
    }
    protected static function getIdent($obj)
    {
    	if(!empty($obj['ident']))    	{
    
    		Return 	$obj['ident'];
    	}
    	if(!empty($obj['src']))
    	{
    		$a = 	$obj['src'];
    		$a = str_replace(array('http://' ,'https://') , '' , $a);
    		$a = preg_replace('~[\?\'"].*~' , '' , $a);
    		Return $a;
    	}
    	else
    	{
    		$a = $obj['code'];
    	}
    	$p = array_filter(array_map("self::specify" ,preg_split('~[\s\'\"]~' , $a ,-1, PREG_SPLIT_NO_EMPTY)));
    	$ln = array_map('strlen' , $p);
    	$max = max($ln);
    	$key = array_search($max,$ln);
    	Return $p[$key];
    }
    public static function getonLoadexecCode($obj)
    {
    	$combine_instruc = 'false';//hardcoding this value for nowv-1.0.0.4
    	if(empty($obj))
    	{
    		Return false;
    	}
    	$cl_buf = "";
    	$k = 0;
    	$m = count($obj) - 1;
    	foreach($obj As $key => $value)
    	{
    		$src_code = !empty($value['src'])? 'src' : 'code';
    
    		if($src_code == 'src')
    		{
    			$name = preg_replace('~[^a-zA-Z]~','',$value['src']);
    		}
    		else{
    			$name = preg_replace('~[^a-zA-Z]~','',$value['code']);
    		}
    		$name = substr($name , -5 , 5);
    		$name = $name. '_multicache_ol_delay';
    		$ident = self::getIdent($value);
    		$type = $src_code == 'src'? 'src' :'text';
    		$string = $src_code == 'src'? $value['src'] : json_encode($value['code']);
    		$combine = $src_code == 'src'? 'null' : $combine_instruc;
    		ob_start();
    		echo '
    		 '. $k .' : {
    				"name"   : "'.$name.'",
    				"ident"  : "'.$ident.'",
    				"type"   : "'.$type.'",
    				';
    		if($src_code == 'src')
    		{
    			echo '"string" : "'.$string.'",';
    		}
    		else{
    			echo '"string" : '.$string.',';
    		}
    		echo '
    				"combine": '.$combine.'
    				     }';
    		if($k< $m)
    		{
    			echo ",
    					";
    		}
    		$cl_buf .= ob_get_clean();
    		$k++;
    	}
    	Return $cl_buf;
    }
    
    public static function getonLoadDelay($multicache_exec_code)
    {
    	if(empty($multicache_exec_code))
    	{
    		Return false;
    	}
    	ob_start();
    	echo '
    			var elementPresent=function(e,c,t){for(var n=0;n<e.length;n++)if(elem_sub=e[n][t],(-1===elem_sub.indexOf("multicache_exec_code")||-1===elem_sub.indexOf("elementPresent"))&&""!=elem_sub&&(iof=elem_sub.indexOf(c),-1!==iof))return!0;return!1},multicache_exec_code={'.$multicache_exec_code.'};!function(e,c,n){function i(){var e,t=(Object.keys(multicache_exec_code).length,c.getElementsByTagName(n)),i="",o=function(e,t,i,o,a){o=c.createElement(n),"text"===a?(o.text=e,o.id=t,o.setAttribute("async","")):(o.src=e,o.id=t,o.setAttribute("async","")),i.parentNode.insertBefore(o,i)};for(var a in multicache_exec_code){var l=elementPresent(t,multicache_exec_code[a].ident,multicache_exec_code[a].type);l||("text"===multicache_exec_code[a].type?multicache_exec_code[a].combine===!1?o(multicache_exec_code[a].string,multicache_exec_code[a].name,t[0],e,multicache_exec_code[a].type):i+=multicache_exec_code[a].string:"src"===multicache_exec_code[a].type&&o(multicache_exec_code[a].string,multicache_exec_code[a].name,t[0],e,multicache_exec_code[a].type))}""!==i&&o(i,"m_comb",t[0],e,"text")}t=typeof n,e.addEventListener?(console.log("adding event listner load"),e.addEventListener("load",i,!1)):e.attachEvent&&(console.log("attaching event"),e.attachEvent("onload",i))}(window,document,"script"),console.log("end");
    			';
    	/*
    	 echo "
    	 var  elementPresent = function(u , b , t)
    	 {
    	 console.log('u -' + u + ' b - ' + b + ' t - ' + t);
    	 for(var j = 0 ; j< u.length; j++){
    	 elem_sub = u[j][t];
    	 //skip this script
    	 if( elem_sub.indexOf('multicache_exec_code') !== -1
    	 && elem_sub.indexOf('elementPresent') !== -1){
    	 console.log('skipping');
    	 continue;
    	 }
    	 if(elem_sub == ''){
    	 continue;
    	 }
    	 iof = elem_sub.indexOf(b);
    	 console.log(iof + ' ' +  elem_sub + ' ' + b);
    	 if(iof !== -1){
    	 console.log(' elem_sub ' +  elem_sub + ' b val ' + b);
    	 return true;
    	 }
    	 }
    	 return false;
    	 }
    	 var multicache_exec_code = {
    	 ".$multicache_exec_code."
    	 };
    	 console.log('start');
    	 (function(w, d, s) {
    	 console.log('in');
    	 t = typeof s;
    	 console.log('type of s' + t);
    	 function multicache_odstart(){
    	 var code_string = '';
    	 var len_loadable = Object.keys(multicache_exec_code).length;
    	 var js, fjs = d.getElementsByTagName(s);
    	 var combined_code = '';
    	 var multicache_load = function(c_u_string, id , u ,js , t) {
    	 console.log('executing multicache_odstart');
    	 js = d.createElement(s);
    	 if(t === 'text'){
    	 js.text = c_u_string;
    	 js.id = id;js.setAttribute('async', '');
    	 }
    	 else
    	 {
    	 js.src = c_u_string; js.id = id;js.setAttribute('async', '');
    	 }
    	 console.log(' end code ' + js);
    	 u.parentNode.insertBefore(js, u);
    	 };
    	 //for code
    	 for(var q in multicache_exec_code )
    	 {
    	 console.log('mexec ' + multicache_exec_code[q].name);
    	 var p = elementPresent(fjs , multicache_exec_code[q].ident , multicache_exec_code[q].type);
    	 alert('p is ' + p) ;
    	 if(!p)
    	 {
    	 if(multicache_exec_code[q].type === 'text' )
    	 {
    	 if(multicache_exec_code[q].combine === false)
    	 {
    	 multicache_load(multicache_exec_code[q].string , multicache_exec_code[q].name , fjs[0] , js , multicache_exec_code[q].type );
    	 }
    	 else{
    	 combined_code += multicache_exec_code[q].string;
    	 }
    	 }
    	 else if(multicache_exec_code[q].type === 'src')
    	 {
    	 multicache_load(multicache_exec_code[q].string , multicache_exec_code[q].name , fjs[0] , js , multicache_exec_code[q].type);
    	 }
    	 }
    	 }
    	 if(combined_code !=='')
    	 {
    	 multicache_load(combined_code , 'm_comb' , fjs[0] , js , 'text');
    	 }
    
    	 }
    	 if (w.addEventListener)
    	 {
    	 console.log('adding event listner load');
    	 w.addEventListener('load', multicache_odstart , false);
    	 }
    	 else if (w.attachEvent)
    	 {
    	 console.log('attaching event');
    	 w.attachEvent('onload',multicache_odstart);
    	 }
    	 }(window, document, 'script'));
    	 ";*/
    	$cl_buf = ob_get_clean();
    	Return serialize($cl_buf);
    }

    public static function getdelaycode($delay_type, $jquery_scope = "jQuery", $mediaFormat)
    {

        $app = JFactory::getApplication();
        // $delay_type = self::extractDelayType($delay_array);
        // $base_url = '//' . str_replace("http://", "", strtolower(substr(JURI::root(), 0, - 1)) . '/media/com_multicache/assets/js/jscache/');
        $base_url = JURI::root() . 'media/com_multicache/assets/js/jscache/';
        // $base_url = '//' . str_replace("http://", "", strtolower(substr(JURI::root(), 0, - 1)) . '/administrator/components/com_multicache/assets/js/jscache/');
        if ($delay_type == "scroll")
        {
            $name = "onscrolldelay.js";
            $url = $base_url . $name;
            $inline_code = 'var url_' . $delay_type . ' = "' . $url . '?mediaFormat=' . $mediaFormat . '";var script_delay_' . $delay_type . '_counter = 0;var max_trys_' . $delay_type . ' = 3;var inter_lock_' . $delay_type . '= 1;' . $jquery_scope . '(window).scroll(function(event) {/*alert("count "+script_delay_' . $delay_type . '_counter);*/console.log("count " + script_delay_' . $delay_type . '_counter);console.log("scroll detected" );if(!inter_lock_' . $delay_type . '){return;/*an equivalent to continue*/}++script_delay_' . $delay_type . '_counter;if(script_delay_' . $delay_type . '_counter <=  max_trys_' . $delay_type . ') {inter_lock_' . $delay_type . ' = 0;' . $jquery_scope . '( this).unbind( event );console.log("unbind");' . $jquery_scope . '.getScript( url_' . $delay_type . ', function() {console.log("getscrpt call on ' . $delay_type . '" );script_delay_' . $delay_type . '_counter =  max_trys_' . $delay_type . '+1;}).fail(function(jqxhr, settings, exception) {/*alert("loading failed" + url_' . $delay_type . ');*/console.log("loading failed in " + url_' . $delay_type . ' +" trial "+ script_delay_' . $delay_type . '_counter);console.log("exception -"+ exception);console.log("settings -"+ settings);console.log("jqxhr -"+ jqxhr);inter_lock_' . $delay_type . ' = 1;});}else{/* alert("giving up");*/' . $jquery_scope . '( this).unbind( "scroll" );console.log("failed scroll loading  "+ url_' . $delay_type . '+"  giving up" );}});';
        }
        elseif ($delay_type == "mousemove")
        {
            $name = "onmousemovedelay.js";
            $url = $base_url . $name;
            $inline_code = 'var url_' . $delay_type . ' = "' . $url . '?mediaFormat=' . $mediaFormat . '";var script_delay_' . $delay_type . '_counter = 0;var max_trys_' . $delay_type . ' = 3;var inter_lock_' . $delay_type . '= 1;' . $jquery_scope . '(window).on("mousemove" ,function( event ) {/*alert("count "+script_delay_counter);*/console.log("count " + script_delay_' . $delay_type . '_counter);console.log("mousemove detected" );if(!inter_lock_' . $delay_type . '){return;/*an equivalent to continue*/}++script_delay_' . $delay_type . '_counter;if(script_delay_' . $delay_type . '_counter <= max_trys_' . $delay_type . ') {inter_lock_' . $delay_type . ' = 0;' . $jquery_scope . '( this).unbind( event );console.log("unbind");' . $jquery_scope . '.getScript( url_' . $delay_type . ', function() {console.log("getscrpt call on ' . $delay_type . '" );script_delay_' . $delay_type . '_counter =  max_trys_' . $delay_type . ';}).fail(function(jqxhr, settings, exception) {/*alert("loading failed" + url_' . $delay_type . ');*/console.log("loading failed in " + url_' . $delay_type . '  +" trial "+ script_delay_' . $delay_type . '_counter);console.log("exception -"+ exception);console.log("settings -"+ settings);console.log("jqxhr -"+ jqxhr);inter_lock_' . $delay_type . ' = 1;});}else{/* alert("giving up");*/' . $jquery_scope . '( this).unbind( "mousemove" );console.log("failed loading "+ url_' . $delay_type . '+"  giving up" );}});';
        }
        else
        {
            $app->enqueueMessage(JText::_('COM_MULTICACHE_ERROR_JQUERY_DELAY_TYPE_UNLISTED_CONDITION'), 'notice');
        }
        
        $obj["code"] = serialize($inline_code);
        $obj["url"] = $name;
        
        Return $obj;
    
    }

    public static function getCssdelaycode($delay_type, $jquery_scope = "jQuery", $mediaFormat = false , $params = false)
    {

        $app = JFactory::getApplication();
        // $delay_type = self::extractDelayType($delay_array);
        /*
         * $base_url = '//' . str_replace(array(
         * "http://",
         * "https://"
         * ), array(
         * "",
         * ""
         * ), strtolower(substr(JURI::root(), 0, - 1)) . '/media/com_multicache/assets/css/csscache/');
         */
        $base_url = JURI::root() . 'media/com_multicache/assets/css/csscache/';
        if ($delay_type == "scroll")
        {
            $name = "onscrolldelay.html";
            $url = $base_url . $name;
            $inline_code = 'var css_url_' . $delay_type . ' = "' . $url . '?mediaFormat=' . $mediaFormat . '";var css_delay_' . $delay_type . '_counter = 0;var css_max_trys_' . $delay_type . ' = 3;var css_inter_lock_' . $delay_type . '= 1;' . $jquery_scope . '(window).scroll(function(event) {/*alert("count "+css_delay_' . $delay_type . '_counter);*/console.log("count " + css_delay_' . $delay_type . '_counter);console.log("scroll detected" );if(!css_inter_lock_' . $delay_type . '){return;/*an equivalent to continue*/}++css_delay_' . $delay_type . '_counter;if(css_delay_' . $delay_type . '_counter <=  css_max_trys_' . $delay_type . ') {css_inter_lock_' . $delay_type . ' = 0;' . $jquery_scope . '( this).unbind( event );console.log("unbind");' . $jquery_scope . '.ajax({url:  css_url_' . $delay_type . ',type: "GET",dataType: "html",cache: !1,success: function(t){console.log("getscrpt call on ' . $delay_type . '");css_delay_' . $delay_type . '_counter = css_max_trys_' . $delay_type . '+1;' . $jquery_scope . '("body").append(t);}}).fail(function(jqxhr, settings, exception) {console.log("loading failed in " + css_url_' . $delay_type . ' + " trial " + css_delay_' . $delay_type . '_counter);console.log("exception -"+ exception);console.log("settings -"+ settings);css_inter_lock_' . $delay_type . ' = 1;});}else{/* alert("giving up");*/' . $jquery_scope . '( this).unbind( "scroll" );console.log("failed scroll loading  "+ css_url_' . $delay_type . '+"  giving up" );}});';
        }
        elseif ($delay_type == "mousemove")
        {
            $name = "onmousemovedelay.html";
            $url = $base_url . $name;
            // $inline_code = 'var url_' . $delay_type . ' = "' . $url . '?mediaFormat=' . $mediaFormat . '";var script_delay_' . $delay_type . '_counter = 0;var max_trys_' . $delay_type . ' = 3;var inter_lock_' . $delay_type . '= 1;' . $jquery_scope . '(window).on("mousemove" ,function( event ) {/*alert("count "+script_delay_counter);*/console.log("count " + script_delay_' . $delay_type . '_counter);console.log("mousemove detected" );if(!inter_lock_' . $delay_type . '){return;/*an equivalent to continue*/}++script_delay_' . $delay_type . '_counter;if(script_delay_' . $delay_type . '_counter <= max_trys_' . $delay_type . ') {inter_lock_' . $delay_type . ' = 0;' . $jquery_scope . '( this).unbind( event );console.log("unbind");' . $jquery_scope . '.getScript( url_' . $delay_type . ', function() {console.log("getscrpt call on ' . $delay_type . '" );script_delay_' . $delay_type . '_counter = max_trys_' . $delay_type . ';}).fail(function() {/*alert("loading failed" + url_' . $delay_type . ');*/console.log("loading failed in " + url_' . $delay_type . ' +" trial "+ script_delay_' . $delay_type . '_counter);inter_lock_' . $delay_type . ' = 1;});}else{/* alert("giving up");*/' . $jquery_scope . '( this).unbind( "mousemove" );console.log("failed loading "+ url_' . $delay_type . '+" giving up" );}});';
            $inline_code = 'var css_url_' . $delay_type . ' = "' . $url . '?mediaFormat=' . $mediaFormat . '";var css_delay_' . $delay_type . '_counter = 0;var css_max_trys_' . $delay_type . ' = 3;var css_inter_lock_' . $delay_type . '= 1;' . $jquery_scope . '(window).on("mousemove" ,function( event ) {/*alert("count "+css_delay_' . $delay_type . '_counter);*/console.log("count " + css_delay_' . $delay_type . '_counter);console.log("mousemove detected" );if(!css_inter_lock_' . $delay_type . '){return;/*an equivalent to continue*/}++css_delay_' . $delay_type . '_counter;if(css_delay_' . $delay_type . '_counter <= css_max_trys_' . $delay_type . ') {css_inter_lock_' . $delay_type . ' = 0;' . $jquery_scope . '( this).unbind( event );console.log("unbind");' . $jquery_scope . '.ajax({url:  css_url_' . $delay_type . ',type: "GET",dataType: "html",cache: !1,success: function(t){console.log("getscrpt call on ' . $delay_type . '");css_delay_' . $delay_type . '_counter = css_max_trys_' . $delay_type . ';' . $jquery_scope . '("body").append(t);}}).fail(function(jqxhr, settings, exception) {console.log("loading failed in " + css_url_' . $delay_type . ' + " trial " + css_delay_' . $delay_type . '_counter);console.log("exception -"+ exception);console.log("settings -"+ settings);console.log("jqxhr -"+ jqxhr);css_inter_lock_' . $delay_type . ' = 1;});}else{/* alert("giving up");*/' . $jquery_scope . '( this).unbind( "mousemove" );console.log("failed mousemove loading "+ css_url_' . $delay_type . '+"  giving up" );}});';
        }
        elseif ($delay_type == "async")
        {
            $name = "async.css";
            $url = $base_url . $name;
            // $inline_code = 'var url_' . $delay_type . ' = "' . $url . '?mediaFormat=' . $mediaFormat . '";var script_delay_' . $delay_type . '_counter = 0;var max_trys_' . $delay_type . ' = 3;var inter_lock_' . $delay_type . '= 1;' . $jquery_scope . '(window).on("mousemove" ,function( event ) {/*alert("count "+script_delay_counter);*/console.log("count " + script_delay_' . $delay_type . '_counter);console.log("mousemove detected" );if(!inter_lock_' . $delay_type . '){return;/*an equivalent to continue*/}++script_delay_' . $delay_type . '_counter;if(script_delay_' . $delay_type . '_counter <= max_trys_' . $delay_type . ') {inter_lock_' . $delay_type . ' = 0;' . $jquery_scope . '( this).unbind( event );console.log("unbind");' . $jquery_scope . '.getScript( url_' . $delay_type . ', function() {console.log("getscrpt call on ' . $delay_type . '" );script_delay_' . $delay_type . '_counter = max_trys_' . $delay_type . ';}).fail(function() {/*alert("loading failed" + url_' . $delay_type . ');*/console.log("loading failed in " + url_' . $delay_type . ' +" trial "+ script_delay_' . $delay_type . '_counter);inter_lock_' . $delay_type . ' = 1;});}else{/* alert("giving up");*/' . $jquery_scope . '( this).unbind( "mousemove" );console.log("failed loading "+ url_' . $delay_type . '+" giving up" );}});';
            if("2" === $params['css_groupsasync'])
            {
            	$inline_code = 'window.MULTICACHEASYNCOLEVENT=!1,window.MULTICACHEASYNCOLLOADED=[],window.MULTICACHEASYNCOLSTACK=[];var loadCSS=function(e,n,t,i){!1===window.MULTICACHEASYNCOLEVENT&&-1===window.MULTICACHEASYNCOLSTACK.indexOf(e)?window.MULTICACHEASYNCOLSTACK.push(e):-1===window.MULTICACHEASYNCOLLOADED.indexOf(e)&&-1===window.MULTICACHEASYNCOLSTACK.indexOf(e)&&multicacheLoadSingle(e)},multicacheLoadSingle=function(e,n){n="undefined"==typeof n?document.getElementsByTagName("script")[0]:n;var t=window.document.createElement("link");t.rel="stylesheet",t.href=e,a=n.parentNode.insertBefore(t,n),console.log("insert success val of a "+a+" href "+e)},loadStackMulticache=function(e){i=document.getElementsByTagName("script")[0],s=document.styleSheets;for(var n in e)multicacheLoadSingle(e[n],i),window.MULTICACHEASYNCOLLOADED.push(e[n])};!function(e,n,t){var i=function(){window.MULTICACHEASYNCOLEVENT=!0,loadStackMulticache(window.MULTICACHEASYNCOLSTACK)};e.addEventListener?e.addEventListener("load",i,!1):e.attachEvent&&e.attachEvent("onload",i)}(window,document,"script");';
            	$inline_code = 'window.MULTICACHEASYNCOLEVENT=!1,window.MULTICACHEASYNCOLLOADED=[],window.MULTICACHEASYNCOLSTACK=[];var loadCSS=function(e,n,t,i){!1===window.MULTICACHEASYNCOLEVENT&&-1===window.MULTICACHEASYNCOLSTACK.indexOf(e)?window.MULTICACHEASYNCOLSTACK.push(e):-1===window.MULTICACHEASYNCOLLOADED.indexOf(e)&&-1===window.MULTICACHEASYNCOLSTACK.indexOf(e)&&multicacheLoadSingle(e)},multicacheLoadSingle=function(e,n){function t(){window.document.styleSheets.length>i?o.media=media||"all":setTimeout(t)}n="undefined"==typeof n?document.getElementsByTagName("script")[0]:n;var i=window.document.styleSheets.length,o=window.document.createElement("link");o.rel="stylesheet",o.href=e,o.media="only x",a=n.parentNode.insertBefore(o,n),console.log("insert success val of a "+a+" href "+e),t()},loadStackMulticache=function(e){i=document.getElementsByTagName("script")[0],s=document.styleSheets;for(var n in e)multicacheLoadSingle(e[n],i),window.MULTICACHEASYNCOLLOADED.push(e[n])};!function(e,n,t){var i=function(){window.MULTICACHEASYNCOLEVENT=!0,loadStackMulticache(window.MULTICACHEASYNCOLSTACK)};e.addEventListener?e.addEventListener("load",i,!1):e.attachEvent&&e.attachEvent("onload",i)}(window,document,"script");';
            	$inline_code = 'window.MULTICACHEASYNCOLEVENT=!1,window.MULTICACHEASYNCOLLOADED=[],window.MULTICACHEASYNCOLSTACK=[],window.MULTICACHEASYNCOLLOADSTACK=[];var loadCSS=function(e,n,C,i){!1===window.MULTICACHEASYNCOLEVENT&&-1===window.MULTICACHEASYNCOLSTACK.indexOf(e)?window.MULTICACHEASYNCOLSTACK.push(e):-1===window.MULTICACHEASYNCOLLOADED.indexOf(e)&&-1===window.MULTICACHEASYNCOLSTACK.indexOf(e)&&multicacheLoadSingle(e)},multicacheLoadSingle=function(e,n){n="undefined"==typeof n?document.getElementsByTagName("script")[0]:n;var C=window.document.createElement("link");C.rel="stylesheet",C.href=e,C.media="only x",a=n.parentNode.insertBefore(C,n),console.log("insert success val of a "+a+" href "+e),window.MULTICACHEASYNCOLLOADSTACK.push(C)},loadStackMulticache=function(e){i=document.getElementsByTagName("script")[0],s=document.styleSheets;for(var n in e)multicacheLoadSingle(e[n],i),window.MULTICACHEASYNCOLLOADED.push(e[n])};!function(e,n,C){var i=function(){window.MULTICACHEASYNCOLEVENT=!0,loadStackMulticache(window.MULTICACHEASYNCOLSTACK),setTimeout(function(){alert("fini -"+n);for(var e in window.MULTICACHEASYNCOLLOADSTACK)window.MULTICACHEASYNCOLLOADSTACK[e].media="all";n.media="all"},3e3)};e.addEventListener?e.addEventListener("load",i,!1):e.attachEvent&&e.attachEvent("onload",i)}(window,document,"script");';
            	$inline_code = 'window.MULTICACHEASYNCOLEVENT=!1,window.MULTICACHEASYNCOLLOADED=[],window.MULTICACHEASYNCOLSTACK=[],window.MULTICACHEASYNCOLLOADSTACK=[];var loadCSS=function(e,n,C,i){!1===window.MULTICACHEASYNCOLEVENT&&-1===window.MULTICACHEASYNCOLSTACK.indexOf(e)?window.MULTICACHEASYNCOLSTACK.push(e):-1===window.MULTICACHEASYNCOLLOADED.indexOf(e)&&-1===window.MULTICACHEASYNCOLSTACK.indexOf(e)&&multicacheLoadSingle(e)},multicacheLoadSingle=function(e,n){n="undefined"==typeof n?document.getElementsByTagName("script")[0]:n;var C=window.document.createElement("link");C.rel="stylesheet",C.href=e,C.media="only x",a=n.parentNode.insertBefore(C,n),console.log("insert success val of a "+a+" href "+e),window.MULTICACHEASYNCOLLOADSTACK.push(C)},loadStackMulticache=function(e){i=document.getElementsByTagName("script")[0],s=document.styleSheets;for(var n in e)multicacheLoadSingle(e[n],i),window.MULTICACHEASYNCOLLOADED.push(e[n])};!function(e,n,C){var i=function(){window.MULTICACHEASYNCOLEVENT=!0,loadStackMulticache(window.MULTICACHEASYNCOLSTACK),setTimeout(function(){for(var e in window.MULTICACHEASYNCOLLOADSTACK)window.MULTICACHEASYNCOLLOADSTACK[e].media="all";n.media="all"},3e4)};e.addEventListener?e.addEventListener("load",i,!1):e.attachEvent&&e.attachEvent("onload",i)}(window,document,"script");';
            	$inline_code = 'window.MULTICACHEASYNCOLEVENT=!1,window.MULTICACHEASYNCOLLOADED=[],window.MULTICACHEASYNCOLSTACK=[],window.MULTICACHEASYNCOLLOADSTACK=[];var loadCSS=function(e,n,C,i){!1===window.MULTICACHEASYNCOLEVENT&&-1===window.MULTICACHEASYNCOLSTACK.indexOf(e)?window.MULTICACHEASYNCOLSTACK.push(e):-1===window.MULTICACHEASYNCOLLOADED.indexOf(e)&&-1===window.MULTICACHEASYNCOLSTACK.indexOf(e)&&multicacheLoadSingle(e)},multicacheLoadSingle=function(e,n){n="undefined"==typeof n?document.getElementsByTagName("script")[0]:n;var C=window.document.createElement("link");C.rel="stylesheet",C.href=e,C.media="only x",a=n.parentNode.insertBefore(C,n),console.log("insert success val of a "+a+" href "+e),window.MULTICACHEASYNCOLLOADSTACK.push(C)},loadStackMulticache=function(e){i=document.getElementsByTagName("script")[0],s=document.styleSheets;for(var n in e)multicacheLoadSingle(e[n],i),window.MULTICACHEASYNCOLLOADED.push(e[n])};!function(e,n,C){var i=function(){window.MULTICACHEASYNCOLEVENT=!0,loadStackMulticache(window.MULTICACHEASYNCOLSTACK);var e=0;setTimeout(function(){for(var C in window.MULTICACHEASYNCOLLOADSTACK)window.MULTICACHEASYNCOLLOADSTACK[C].media="all",console.log(e++);n.media="all"},3e4)};e.addEventListener?e.addEventListener("load",i,!1):e.attachEvent&&e.attachEvent("onload",i)}(window,document,"script");';
            	$inline_code = 'window.MULTICACHEASYNCOLEVENT=!1,window.MULTICACHEASYNCOLLOADED=[],window.MULTICACHEASYNCOLSTACK=[],window.MULTICACHEASYNCOLLOADSTACK=[],window.MULTICACHEASYNCOLLOADSTACK_B=[],window.MULTICACHEASYNCOLCOUNTER=0;var loadCSS=function(e,n){("undefined"==typeof window.MULTICACHEASYNCNONOLEVENT&&!1===window.MULTICACHEASYNCOLEVENT||"undefined"!=typeof window.MULTICACHEASYNCNONOLEVENT&&"undefined"==typeof window.MULTICACHEASYNCNONOLEND)&&-1===window.MULTICACHEASYNCOLSTACK.indexOf(e)?(window.MULTICACHEASYNCOLSTACK.push(e),"undefined"!=typeof n&&(window.MULTICACHEASYNCOLLOADSTACK_B[e]=n)):-1===window.MULTICACHEASYNCOLLOADED.indexOf(e)&&-1===window.MULTICACHEASYNCOLSTACK.indexOf(e)?multicacheLoadSingle(e):console.log("bounced "+e)},multicacheLoadSingle=function(e,n){var o=++window.MULTICACHEASYNCOLCOUNTER,i="p_func_"+o;"undefined"==typeof window.MULTICACHEBAIL&&(window[i]={name:i+"_object",data:[],listner:function(e,n){window[i].data.ref_obj=e,window[i].CSSloaded(n+e.href+e.nodeType+e.media,window[i])},CSSloaded:function(e,n){n.initialised=1},checkMate:{checkType:function(){return typeof window[i].initialised},name:i+"_init_check"},nopromise_callback:function(){d.onload=function(){"all"!==this.media&&window[i].listner(this,"onload listener NOPROMISE 1")},d.addEventListener&&d.addEventListener("load",function(){window[i].listner(this,"onload listener NO PROMISE 2")},!1),d.onreadystatechange=function(){var e=d.readyState;("loaded"===e||"complete"===e)&&(d.onreadystatechange=null,window[i].listner(this,"onload listener NO PROMISE 3"))},multicache_MAU(window[i].nopromise_resolve,window[i].nopromise_reject,window[i].checkMate,30,void 0,void 0,i)},nopromise_resolve:function(){var e=window[i].data,n=window.MULTICACHEASYNCOLLOADSTACK_B[e.ref_obj.href];void 0!==n?multicache_MAU(window[i].callback,window[i].callback,{checkType:function(){return"undefined"}},n,1,3,i):"undefined"!=typeof e.ref_obj&&window[i].callback()},nopromise_reject:function(){console.log("in no promise reject"+i)},init:function(e,n){d.onload=function(){"all"!==this.media&&window[i].listner(this,"onload listener 1 ")},d.addEventListener&&d.addEventListener("load",function(){window[i].listner(this,"onload listener 2")},!1),d.onreadystatechange=function(){var e=d.readyState;("loaded"===e||"complete"===e)&&(d.onreadystatechange=null,window[i].listner(this,"onload listener 3"))},multicache_MAU(e,n,window[i].checkMate,30,void 0,void 0,i)},callback:function(){var e=window[i].data;"undefined"!=typeof e.ref_obj&&(e.ref_obj.media="all")},then:function(e){var n=window[i].data,o=window.MULTICACHEASYNCOLLOADSTACK_B[n.ref_obj.href];void 0!==o?multicache_MAU(window[i].callback,window[i].callback,{checkType:function(){return"undefined"}},o,1,3,i):"undefined"!=typeof n.ref_obj&&window[i].callback()},error:function(e){console.log("Promise rejected."+i),console.log(e.message);var n=window[i].data,o=window.MULTICACHEASYNCOLLOADSTACK_B[n.ref_obj.href];void 0!==o?multicache_MAU(window[i].callback,window[i].callback,{checkType:function(){return"undefined"}},o,1,3,i):"undefined"!=typeof n.ref_obj&&window[i].callback()},"catch":function(e){console.log("catch "+i),console.log("catch: ",e);var n=window[i].data,o=window.MULTICACHEASYNCOLLOADSTACK_B[n.ref_obj.href];void 0!==o?multicache_MAU(window[i].callback,window[i].callback,{checkType:function(){return"undefined"}},o,1,3,i):"undefined"!=typeof n.ref_obj&&window[i].callback()}}),n="undefined"==typeof n?document.getElementsByTagName("script")[0]:n;var d=window.document.createElement("link");if(d.rel="stylesheet",d.href=e,d.media="only x","undefined"==typeof window.MULTICACHEBAIL)if(window.Promise){window[i].data.ref_obj=d;var t="promise_"+o;window[t]=new Promise(window[i].init),window[t].then(window[i].then,window[i].error)["catch"](window[i]["catch"])}else window[i].nopromise_callback();else d.media="all";a=n.parentNode.insertBefore(d,n),window.MULTICACHEASYNCOLLOADSTACK.push(d)},loadStackMulticache=function(e){i=document.getElementsByTagName("script")[0],s=document.styleSheets;for(var n in e)multicacheLoadSingle(e[n],i),window.MULTICACHEASYNCOLLOADED.push(e[n])};!function(e,n,o){var i=function(){console.log("BAILING");var e=typeof window.MULTICACHEBAIL;if(window.MULTICACHEBAIL=1,"undefined"===e)for(var n in window.MULTICACHEASYNCOLLOADSTACK)"all"!==window.MULTICACHEASYNCOLLOADSTACK[n].media&&(window.MULTICACHEASYNCOLLOADSTACK[n].media="all")},d=function(){i(),this.removeEventListener("scroll",arguments.callee)},t=function(){i(),this.removeEventListener&&this.removeEventListener("mousemove",arguments.callee),this.detachEvent&&this.detachEvent("onmousemove",arguments.callee)},a=function(){window.MULTICACHEASYNCOLEVENT=!0,loadStackMulticache(window.MULTICACHEASYNCOLSTACK)};e.addEventListener?("undefined"==typeof window.MULTICACHEASYNCNONOLEVENT&&e.addEventListener("load",a,!1),e.addEventListener("scroll",d,!1),e.addEventListener("mousemove",t,!1)):e.attachEvent&&("undefined"==typeof window.MULTICACHEASYNCNONOLEVENT&&e.attachEvent("onload",a),e.attachEvent("onscroll",d),e.attachEvent("onmousemove",t))}(window,document,"script");';
            }
            else
            {
            $inline_code = 'function loadCSS(e,n,o,t){"use strict";var d=window.document.createElement("link"),i=n||window.document.getElementsByTagName("script")[0],s=window.document.styleSheets;return d.rel="stylesheet",d.href=e,d.media="only x",t&&(d.onload=t),i.parentNode.insertBefore(d,i),d.onloadcssdefined=function(n){for(var o,t=0;t<s.length;t++)s[t].href&&s[t].href.indexOf(e)>-1&&(o=!0);o?n():setTimeout(function(){d.onloadcssdefined(n)})},d.onloadcssdefined(function(){d.media=o||"all"}),d}';
            $inline_code = 'window.MULTICACHEASYNCNONOLEVENT=!0,window.MULTICACHEASYNCOLEVENT=!1,window.MULTICACHEASYNCOLLOADED=[],window.MULTICACHEASYNCOLSTACK=[],window.MULTICACHEASYNCOLLOADSTACK=[],window.MULTICACHEASYNCOLLOADSTACK_B=[],window.MULTICACHEASYNCOLCOUNTER=0;var loadCSS=function(e,n){("undefined"==typeof window.MULTICACHEASYNCNONOLEVENT&&!1===window.MULTICACHEASYNCOLEVENT||"undefined"!=typeof window.MULTICACHEASYNCNONOLEVENT&&"undefined"==typeof window.MULTICACHEASYNCNONOLEND)&&-1===window.MULTICACHEASYNCOLSTACK.indexOf(e)?(window.MULTICACHEASYNCOLSTACK.push(e),"undefined"!=typeof n&&(window.MULTICACHEASYNCOLLOADSTACK_B[e]=n)):-1===window.MULTICACHEASYNCOLLOADED.indexOf(e)&&-1===window.MULTICACHEASYNCOLSTACK.indexOf(e)?multicacheLoadSingle(e):console.log("bounced "+e)},multicacheLoadSingle=function(e,n){var o=++window.MULTICACHEASYNCOLCOUNTER,i="p_func_"+o;"undefined"==typeof window.MULTICACHEBAIL&&(window[i]={name:i+"_object",data:[],listner:function(e,n){window[i].data.ref_obj=e,window[i].CSSloaded(n+e.href+e.nodeType+e.media,window[i])},CSSloaded:function(e,n){n.initialised=1},checkMate:{checkType:function(){return typeof window[i].initialised},name:i+"_init_check"},nopromise_callback:function(){d.onload=function(){"all"!==this.media&&window[i].listner(this,"onload listener NOPROMISE 1")},d.addEventListener&&d.addEventListener("load",function(){window[i].listner(this,"onload listener NO PROMISE 2")},!1),d.onreadystatechange=function(){var e=d.readyState;("loaded"===e||"complete"===e)&&(d.onreadystatechange=null,window[i].listner(this,"onload listener NO PROMISE 3"))},multicache_MAU(window[i].nopromise_resolve,window[i].nopromise_reject,window[i].checkMate,30,void 0,void 0,i)},nopromise_resolve:function(){var e=window[i].data,n=window.MULTICACHEASYNCOLLOADSTACK_B[e.ref_obj.href];void 0!==n?multicache_MAU(window[i].callback,window[i].callback,{checkType:function(){return"undefined"}},n,1,3,i):"undefined"!=typeof e.ref_obj&&window[i].callback()},nopromise_reject:function(){console.log("in no promise reject"+i)},init:function(e,n){d.onload=function(){"all"!==this.media&&window[i].listner(this,"onload listener 1 ")},d.addEventListener&&d.addEventListener("load",function(){window[i].listner(this,"onload listener 2")},!1),d.onreadystatechange=function(){var e=d.readyState;("loaded"===e||"complete"===e)&&(d.onreadystatechange=null,window[i].listner(this,"onload listener 3"))},multicache_MAU(e,n,window[i].checkMate,30,void 0,void 0,i)},callback:function(){var e=window[i].data;"undefined"!=typeof e.ref_obj&&(e.ref_obj.media="all")},then:function(e){var n=window[i].data,o=window.MULTICACHEASYNCOLLOADSTACK_B[n.ref_obj.href];void 0!==o?multicache_MAU(window[i].callback,window[i].callback,{checkType:function(){return"undefined"}},o,1,3,i):"undefined"!=typeof n.ref_obj&&window[i].callback()},error:function(e){console.log("Promise rejected."+i),console.log(e.message);var n=window[i].data,o=window.MULTICACHEASYNCOLLOADSTACK_B[n.ref_obj.href];void 0!==o?multicache_MAU(window[i].callback,window[i].callback,{checkType:function(){return"undefined"}},o,1,3,i):"undefined"!=typeof n.ref_obj&&window[i].callback()},"catch":function(e){console.log("catch "+i),console.log("catch: ",e);var n=window[i].data,o=window.MULTICACHEASYNCOLLOADSTACK_B[n.ref_obj.href];void 0!==o?multicache_MAU(window[i].callback,window[i].callback,{checkType:function(){return"undefined"}},o,1,3,i):"undefined"!=typeof n.ref_obj&&window[i].callback()}}),n="undefined"==typeof n?document.getElementsByTagName("script")[0]:n;var d=window.document.createElement("link");if(d.rel="stylesheet",d.href=e,d.media="only x","undefined"==typeof window.MULTICACHEBAIL)if(window.Promise){window[i].data.ref_obj=d;var t="promise_"+o;window[t]=new Promise(window[i].init),window[t].then(window[i].then,window[i].error)["catch"](window[i]["catch"])}else window[i].nopromise_callback();else d.media="all";a=n.parentNode.insertBefore(d,n),window.MULTICACHEASYNCOLLOADSTACK.push(d)},loadStackMulticache=function(e){i=document.getElementsByTagName("script")[0],s=document.styleSheets;for(var n in e)multicacheLoadSingle(e[n],i),window.MULTICACHEASYNCOLLOADED.push(e[n])};!function(e,n,o){var i=function(){console.log("BAILING");var e=typeof window.MULTICACHEBAIL;if(window.MULTICACHEBAIL=1,"undefined"===e)for(var n in window.MULTICACHEASYNCOLLOADSTACK)"all"!==window.MULTICACHEASYNCOLLOADSTACK[n].media&&(window.MULTICACHEASYNCOLLOADSTACK[n].media="all")},d=function(){i(),this.removeEventListener("scroll",arguments.callee)},t=function(){i(),this.removeEventListener&&this.removeEventListener("mousemove",arguments.callee),this.detachEvent&&this.detachEvent("onmousemove",arguments.callee)},a=function(){window.MULTICACHEASYNCOLEVENT=!0,loadStackMulticache(window.MULTICACHEASYNCOLSTACK)};e.addEventListener?("undefined"==typeof window.MULTICACHEASYNCNONOLEVENT&&e.addEventListener("load",a,!1),e.addEventListener("scroll",d,!1),e.addEventListener("mousemove",t,!1)):e.attachEvent&&("undefined"==typeof window.MULTICACHEASYNCNONOLEVENT&&e.attachEvent("onload",a),e.attachEvent("onscroll",d),e.attachEvent("onmousemove",t))}(window,document,"script");';
            }
        }
        else
        {
            $app->enqueueMessage(JText::_('COM_MULTICACHE_ERROR_JQUERY_CSS_DELAY_TYPE_UNLISTED_CONDITION'), 'notice');
        }
        
        $obj["code"] = serialize($inline_code);
        $obj["url"] = $name;
        
        Return $obj;
    
    }

    public static function get_web_page($url)
    {

        if (strpos($url, '//') === 0)
        {
            $url = 'http:' . $url;
        }
        
        if (! function_exists('curl_version'))
        {
            if (class_exists('JHTTP'))
            {
                
                $web = new JHTTP();
                $ret = $web->get($url);
                
                $page = array();
                if (! empty($ret))
                {
                    foreach ($ret as $key => $obj)
                    {
                        if ($key == 'body')
                        {
                            $key_name = 'content';
                        }
                        elseif ($key == 'code')
                        {
                            $key_name = 'http_code';
                        }
                        else
                        {
                            $key_name = $key;
                        }
                        
                        $page[$key_name] = $obj;
                    }
                    
                    Return $page;
                }
            }
            $app = JFactory::getApplication();
            $e_message = JText::_('COM_MULTICACHE_HELPER_GETWEBPAGE_CURL_DOESNOTEXIST');
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
            CURLOPT_ENCODING => "gzip,deflate", // handle only gzip & defalte encodings Joomla3
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

    public static function writeToConfig(JRegistry $config)
    {

        $success = self::writeConfigFile($config);
        self::prepareMulticacheConfig($config);
        Return $success;
    
    }

    protected static function prepareMulticacheConfig(JRegistry $obj)
    {

        if (empty($obj))
        {
            Return false;
        }
        
        ob_start();
        echo "<?php
/**
 * @Multicache Loader Config
 * @version 1.0.1.5
 * @package com_multicache
 * @copyright Copyright (C) Multicache.org 2015. All rights reserved.
 * @licenseGNU GENERAL PUBLIC LICENSE  see LICENSE.txt - http://multicache.org/terms-conditions/end-user-license-agreement.html
 * @author Wayne DSouza <consulting@OnlineMarketingConsultants.in> - http://OnlineMarketingConsultants.in
 * @This class may be overwritten automatically - make your changeds in the control panel
 */

defined('_JEXEC') or die();;
class MulticacheConfig
 {
        ";
        $cl_buf = ob_get_clean();
        foreach ($obj as $config_keys => $config_val)
        {
            switch ($config_keys)
            {
                case 'MetaAuthor':
                case 'MetaDesc':
                case 'MetaKeys':
                case 'MetaRights':
                case 'MetaTitle':
                case 'MetaVersion':
                case 'access':
                case 'captcha':
                case 'db':
                case 'dbprefix':
                case 'dbtype':
                case 'debug_lang':
                case 'display_offline_message':
                case 'editor':
                case 'error_reporting':
                case 'feed_email':
                case 'feed_limit':
                case 'force_ssl':
                case 'fromname':
                case 'ftp_enable':
                case 'ftp_host':
                case 'ftp_pass':
                case 'ftp_port':
                case 'ftp_root':
                case 'ftp_user':
                case 'helpurl':
                case 'list_limit':
                case 'mailer':
                case 'mailfrom':
                case 'sitename_pagetitles':
                case 'smtpauth':
                case 'smtphost':
                case 'smtppass':
                case 'smtpport':
                case 'smtpsecure':
                case 'smtpuser':
                case 'unicodeslugs':
                case 'user':
                case 'mailonline':
                case 'proxy_enable':
                case 'proxy_host':
                case 'proxy_port':
                case 'proxy_user':
                case 'proxy_pass':
                case 'frontediting':
                case 'asset_id':
                case 'password':
                case 'language':
                case 'robots':
                case 'offset':
                case 'offset_user':
                case 'offline_message':
                    continue 2;
            }
            ob_start();
            echo "\npublic \$$config_keys = '$config_val';";
            $cl_buf .= ob_get_clean();
        }
        // 'cachebase' => $conf->get('cache_path', JPATH_CACHE),
        if (null === $obj->get('cachebase'))
        {
            if (defined('JPATH_SITE'))
            {
                ob_start();
                echo "\npublic \$cachebase = '" . JPATH_SITE . "/cache" . "';";
                $cl_buf .= ob_get_clean();
            }
            elseif (defined('JPATH_ROOT'))
            {
                ob_start();
                echo "\npublic \$cachebase = '" . JPATH_ROOT . "/cache" . "';";
                $cl_buf .= ob_get_clean();
            }
        }
        ob_start();
        echo "

 }";
        $cl_buf .= ob_get_clean();
        $cl_buf = str_ireplace("\x0D", "", $cl_buf);
        $cl_buf = serialize($cl_buf);
        $dir = JPATH_ADMINISTRATOR . '/components/com_multicache/lib';
        $filename = 'multicache_config.php';
        $success = self::writefileTolocation($dir, $filename, $cl_buf);
        Return $success;
    
    }

    public static function clean_cache($group = null, $id = null)
    {

        $id2 = substr($id, - 1) != '/' ? $id . '/' : null;
        $id3 = substr($id, - 1) == '/' ? substr_replace($id, '', - 1) : null;
        
        $cache = self::getCache();
        if (! empty($group))
        {
            $cache->clean($group, 'both');
            // $cache->clean($group . '_file_cache');
        }
        if (! empty($id))
        {
            $cache->remove($id, 'page');
            // two variants
            if (isset($id2))
            {
                $cache->remove($id2, 'page');
            }
            if (isset($id3))
            {
                $cache->remove($id3, 'page');
            }
        }
        $cache->clean('_system', 'both');
        $cache->clean('com_config', 'both');
        $cache = self::getCache(true);
        if (! empty($group))
        {
            $cache->clean($group, 'both');
            // $cache->clean($group . '_file_cache');
        }
        if (! empty($id))
        {
            $cache->remove($id, 'page');
            // two variants
            if (isset($id2))
            {
                $cache->remove($id2, 'page');
            }
            if (isset($id3))
            {
                $cache->remove($id3, 'page');
            }
        }
        $cache->clean('_system', 'both');
        $cache->clean('com_config', 'both');
    
    }

    public static function registerLOGnormal($u)
    {

        if (empty($u))
        {
            Return false;
        }
        $app = JFactory::getApplication();
        $u = preg_replace('/\s/', '', var_export($u, true));
        $u = str_replace(',)', ')', $u);
        ob_start();
        echo "<?php

 /**
 * @Multicache lognormal array handler
 * @version 1.0.1.5
 * @package com_multicache
 * @copyright Copyright (C) Multicache.org 2015. All rights reserved.
 * @licenseGNU GENERAL PUBLIC LICENSE  see LICENSE.txt - http://multicache.org/terms-conditions/end-user-license-agreement.html
 * @author Wayne DSouza <consulting@OnlineMarketingConsultants.in> - http://OnlineMarketingConsultants.in
 * @This class may be overwritten automatically - make your changeds in the control panel
 */



defined('_JEXEC') or die();
class MulticacheUrlArray{

public static \$urls = " . trim($u) . ";


}
?>";
        $cl_buf = serialize(ob_get_clean());
        $dir = JPATH_COMPONENT . '/lib';
        $filename = 'multicacheurlarray.php';
        $success = self::writefileTolocation($dir, $filename, $cl_buf);
        if ($success)
        {
            $app->enqueueMessage(JText::_('COM_MULTICACHE_REGISTERURLARRAY_SUCCESS_MESSAGE'), 'message');
        }
        else
        {
            $app->enqueueMessage(JText::_('COM_MULTICACHE_REGISTERURLARRAY_FAILED_MESSAGE'), 'warning');
        }
        Return $success;
    
    }

    public static function storePageCss($u = null, $v = null, $duplicates = null, $delayed = null)
    {

        $original_set = isset($u) ? true : null;
        
        if (empty($u))
        {
            if (! class_exists('MulticachePageCss'))
            {
                Return false;
            }
            if (property_exists('MulticachePageCss', 'original_css_array'))
            {
                $u = MulticachePageCss::$original_css_array;
            }
        }
        $u = var_export($u, true); // the original script array -> to be used only when the working script array is not present-> no variables should be changed from the time of scrping
        if (! $original_set)
        {
            $v = self::setinitialiseScriptpeice($v, 'working_css_array', 'MulticachePageCss');
            $duplicates = self::setinitialiseScriptpeice($duplicates, 'duplicates', 'MulticachePageCss');
            
            $async = self::setinitialiseScriptpeice($async, 'async', 'MulticachePageCss');
            $delayed = self::setinitialiseScriptpeice($delayed, 'delayed', 'MulticachePageCss');
        }
        
        ob_start();
        echo "<?php

 /**
 * @Multicache Css Templater
 * @version 1.0.1.5
 * @package com_multicache
 * @copyright Copyright (C) Multicache.org 2015. All rights reserved.
 * @licenseGNU GENERAL PUBLIC LICENSE  see LICENSE.txt - http://multicache.org/terms-conditions/end-user-license-agreement.html
 * @author Wayne DSouza <consulting@OnlineMarketingConsultants.in> - http://OnlineMarketingConsultants.in
 * @This class may be overwritten automatically - make your changes in the control panel
 */


defined('JPATH_PLATFORM') or die;
class MulticachePageCss{

public static \$original_css_array  = " . trim($u) . ";

";
        $cl_buf = ob_get_clean();
        if (! empty($v) && ! $original_set)
        {
            ob_start();
            echo "


public static \$working_css_array  = " . trim($v) . ";

";
            $cl_buf .= ob_get_clean();
        }
        // start duplicate writerender
        if (! empty($duplicates) && ! $original_set)
        {
            
            ob_start();
            echo "


public static \$duplicates  = " . trim($duplicates) . ";

";
            $cl_buf .= ob_get_clean();
        }
        // end duplicate writerender
        
        // start social writerender
        if (! empty($social) && ! $original_set)
        {
            
            ob_start();
            echo "


public static \$social  = " . trim($social) . ";

";
            $cl_buf .= ob_get_clean();
        }
        // end social writerender
        
        // start advertisements writerender
        if (! empty($advertisements) && ! $original_set)
        {
            
            ob_start();
            echo "


public static \$advertisements  = " . trim($advertisements) . ";

";
            $cl_buf .= ob_get_clean();
        }
        // end advertisements writerender
        
        // start async writerender
        if (! empty($async) && ! $original_set)
        {
            
            ob_start();
            echo "


public static \$async  = " . trim($async) . ";

";
            $cl_buf .= ob_get_clean();
        }
        // end async writerender
        
        // start delayed writerender
        if (! empty($delayed) && ! $original_set)
        {
            
            ob_start();
            echo "


public static \$delayed  = " . trim($delayed) . ";

";
            $cl_buf .= ob_get_clean();
        }
        // end delayed writerender
        
        // closing tags
        ob_start();
        echo "
        }
?> ";
        $cl_buf .= ob_get_clean();
        $cl_buf = serialize($cl_buf);
        $dir = JPATH_COMPONENT . '/lib';
        $filename = 'pagecss.php';
        $success = self::writefileTolocation($dir, $filename, $cl_buf);
        // $success = self::writePageScripts(serialize($cl_buf));
        Return $success;
    
    }

    public static function storePageScripts($u = null, $v = null, $duplicates = null, $social = null, $advertisements = null, $async = null, $delayed = null, $dontmove = null)
    {

        $original_set = isset($u) ? true : null;
        
        if (empty($u))
        {
            if (! class_exists('MulticachePageScripts'))
            {
                Return false;
            }
            if (property_exists('MulticachePageScripts', 'original_script_array'))
            {
                $u = MulticachePageScripts::$original_script_array;
            }
        }
        $u = var_export($u, true); // the original script array -> to be used only when the working script array is not present-> no variables should be changed from the time of scrping
        if (! $original_set)
        {
            $v = self::setinitialiseScriptpeice($v, 'working_script_array');
            $duplicates = self::setinitialiseScriptpeice($duplicates, 'duplicates');
            $social = self::setinitialiseScriptpeice($social, 'social');
            $advertisements = self::setinitialiseScriptpeice($advertisements, 'advertisements');
            $async = self::setinitialiseScriptpeice($async, 'async');
            $delayed = self::setinitialiseScriptpeice($delayed, 'delayed');
            $dontmove = self::setinitialiseScriptpeice($dontmove, 'dontmove');
        }
        
        ob_start();
        echo "<?php

 /**
 * @Multicache Page Templater
 * @version 1.0.1.5
 * @package com_multicache
 * @copyright Copyright (C) Multicache.org 2015. All rights reserved.
 * @licenseGNU GENERAL PUBLIC LICENSE  see LICENSE.txt - http://multicache.org/terms-conditions/end-user-license-agreement.html
 * @author Wayne DSouza <consulting@OnlineMarketingConsultants.in> - http://OnlineMarketingConsultants.in
 * @This class may be overwritten automatically - make your changes in the control panel
 */


defined('JPATH_PLATFORM') or die;
class MulticachePageScripts{

public static \$original_script_array  = " . trim($u) . ";

";
        $cl_buf = ob_get_clean();
        if (! empty($v) && ! $original_set)
        {
            ob_start();
            echo "


public static \$working_script_array  = " . trim($v) . ";

";
            $cl_buf .= ob_get_clean();
        }
        // start duplicate writerender
        if (! empty($duplicates) && ! $original_set)
        {
            
            ob_start();
            echo "


public static \$duplicates  = " . trim($duplicates) . ";

";
            $cl_buf .= ob_get_clean();
        }
        // end duplicate writerender
        
        // start social writerender
        if (! empty($social) && ! $original_set)
        {
            
            ob_start();
            echo "


public static \$social  = " . trim($social) . ";

";
            $cl_buf .= ob_get_clean();
        }
        // end social writerender
        
        // start advertisements writerender
        if (! empty($advertisements) && ! $original_set)
        {
            
            ob_start();
            echo "


public static \$advertisements  = " . trim($advertisements) . ";

";
            $cl_buf .= ob_get_clean();
        }
        // end advertisements writerender
        
        // start async writerender
        if (! empty($async) && ! $original_set)
        {
            
            ob_start();
            echo "


public static \$async  = " . trim($async) . ";

";
            $cl_buf .= ob_get_clean();
        }
        // end async writerender
        
        // start delayed writerender
        if (! empty($delayed) && ! $original_set)
        {
            
            ob_start();
            echo "


public static \$delayed  = " . trim($delayed) . ";

";
            $cl_buf .= ob_get_clean();
        }
        // end delayed writerender
        
        // start dontmove writerender
        if (! empty($dontmove) && ! $original_set)
        {
            
            ob_start();
            echo "


public static \$dontmove  = " . trim($dontmove) . ";

";
            $cl_buf .= ob_get_clean();
        }
        // end delayed writerender
        
        // closing tags
        ob_start();
        echo "
        }
?> ";
        $cl_buf .= ob_get_clean();
        $cl_buf = serialize($cl_buf);
        $dir = JPATH_COMPONENT . '/lib';
        $filename = 'pagescripts.php';
        $success = self::writefileTolocation($dir, $filename, $cl_buf);
        // $success = self::writePageScripts(serialize($cl_buf));
        Return $success;
    
    }

    public static function checkPositionalUrls($array)
    {

        if (empty($array))
        {
            Return false;
        }
        
        foreach ($array as $key => $arr_bit)
        {
            $array[$key] = str_replace(array(
                'https',
                'http',
                '://',
                '//',
                'www.'
            ), '', $arr_bit);
        }
        
        Return $array;
    
    }

    public static function writeJsCache($obj, $filename, $tblswitch = 1)
    {

        $app = JFactory::getApplication();
        $dir = JPATH_SITE . '/media/com_multicache/assets/js/jscache'; // JPATH_ADMINISTRATOR .
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
        
        $link = JURI::root() . 'media/com_multicache/assets/js/jscache/' . $filename . '?mediaFormat=' . self::getMediaFormat();
        
        $alink = '<a href="' . $link . '" target="_blank" class="btn btn-mini btn-success" title="click to open in new window">' . $filename . '</a>';
        
        $cl_buf = serialize($obj);
        $success = self::writefileTolocation($dir, $filename, $cl_buf);
        if ($success && $tblswitch)
        {
            $app->enqueueMessage(JText::_('COM_MULTICACHE_JS_CACHE_WRITE_SUCCESS_MESSAGE') . '  	' . $alink, 'message');
            return true;
        }
        Return false;
    
    }

    public static function writeCssCache($obj, $filename, $tblswitch = 1)
    {

        $app = JFactory::getApplication();
        $dir = JPATH_SITE . '/media/com_multicache/assets/css/csscache'; // JPATH_ADMINISTRATOR .
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
        
        $link = JURI::root() . 'media/com_multicache/assets/css/csscache/' . $filename . '?mediaFormat=' . self::getMediaFormat();
        
        $alink = '<a href="' . $link . '" target="_blank" class="btn btn-mini btn-success" title="click to open in new window">' . $filename . '</a>';
        
        $cl_buf = serialize($obj);
        $success = self::writefileTolocation($dir, $filename, $cl_buf);
        if ($success && $tblswitch)
        {
            $app->enqueueMessage(JText::_('COM_MULTICACHE_CSS_CACHE_WRITE_SUCCESS_MESSAGE') . '  	' . $alink, 'message');
            return true;
        }
        Return false;
    
    }

    public static function writeJsCacheStrategy($signature_hash, $loadsection, $switch = null, $stubs = null, $JSTexclude = null, $signature_hash_css, $loadsection_css, $switch_css, $CSSexclude = null, $switch_img = null, $IMGexclude = null, $lazy_load_params = null, $dontmove_hash = null, $dontmove_urls = null, $allow_multiple_orphaned = null , $params_pagespeed = null)
    {
/*
 * input params
 * $signature_hash,
 * $loadsection,
 * $switch = null,
 * $stubs = null, 
 * $JSTexclude = null, 
 * $signature_hash_css, 
 * $loadsection_css, 
 * $switch_css, 
 * $CSSexclude = null, 
 * $switch_img = null, 
 * $IMGexclude = null, 
 * $lazy_load_params = null, 
 * $dontmove_hash = null, 
 * $dontmove_urls = null, 
 * $allow_multiple_orphaned = null , 
 * $params_pagespeed = null
 */
        $app = JFactory::getApplication();
        if (empty($signature_hash))
        {
            
            if (class_exists('JsStrategy') && method_exists('JsStrategy', 'getJsSignature'))
            {
                $signature_hash = JsStrategy::getJsSignature();
            }
            else
            {
                $signature_hash = null;
            }
        }
        if (empty($signature_hash_css))
        {
            
            if (property_exists('JsStrategy', 'cssSignaturehash'))
            {
                $signature_hash_css = JsStrategy::$cssSignaturehash;
            }
            else
            {
                $signature_hash_css = null;
            }
        }
        if (empty($signature_hash) && empty($signature_hash_css) && empty($switch_img))
        {
            $app->enqueueMessage('COM_MULTICACHE_ADMIN_HELPER_COULDNOTCREATESTRATEGY_SIGNATURES_EMPTY', 'notice');
            Return false;
        }
        if (empty($loadsection))
        {
            if (class_exists('JsStrategy') &&  method_exists('JsStrategy', 'getLoadSection'))
            {
                $loadsection = JsStrategy::getLoadSection();
            }
            else
            {
                $loadsection = null;
            }
        }
        if (empty($loadsection_css))
        {
            if (property_exists('JsStrategy', 'loadSectionCss'))
            {
                $loadsection_css = JsStrategy::$loadSectionCss;
            }
            else
            {
                $loadsection_css = null;
            }
        }
        if (empty($loadsection) && empty($loadsection_css) && empty($switch_img))
        {
            $app->enqueueMessage('COM_MULTICACHE_ADMIN_HELPER_COULDNOTCREATESTRATEGY_LOADSECTIONS_EMPTY', 'notice');
            Return false;
        }
        $pagespeed_strategy = array();
        if(isset($params_pagespeed['resultant_async']))
        {
        $pagespeed_strategy['resultant_async'] = $params_pagespeed['resultant_async'];
        }
        if(isset($params_pagespeed['resultant_defer']))
        {
        	$pagespeed_strategy['resultant_defer'] = $params_pagespeed['resultant_defer'];
        }
        $pagespeed_strategy = preg_replace('/\s/', '', var_export($pagespeed_strategy, true));
        $signature_hash = preg_replace('/\s/', '', var_export($signature_hash, true));
        $signature_hash = str_replace(',)', ')', $signature_hash);
        $signature_hash_css = preg_replace('/\s/', '', var_export($signature_hash_css, true));
        $signature_hash_css = str_replace(',)', ')', $signature_hash_css);
        $loadsection = var_export($loadsection, true);
        $loadsection_css = var_export($loadsection_css, true);
        $switch = var_export($switch, true);
        $switch_css = var_export($switch_css, true);
        $switch_img = var_export($switch_img, true);
        $lazy_load_params = ! empty($switch_img) ? var_export(unserialize($lazy_load_params), true) : null;
        $dontmove_hash = preg_replace('/\s/', '', var_export($dontmove_hash, true));
        $dontmove_hash = str_replace(',)', ')', $dontmove_hash);
        $dontmove_urls = preg_replace('/\s/', '', var_export($dontmove_urls, true));
        $dontmove_urls = str_replace(',)', ')', $dontmove_urls);
        if ($allow_multiple_orphaned != - 1)
        {
            $allow_multiple_orphaned = preg_replace('/\s/', '', var_export($allow_multiple_orphaned, true));
            $allow_multiple_orphaned = str_replace(',)', ')', $allow_multiple_orphaned);
        }
        
        // $lazy_load_params = isset($lazy_load_params) ? preg_replace('/\s/', '', $lazy_load_params) : null;
        // the last step removes blanks from inbetween serilized params causing it to fail
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
        // css excludes
        if (! empty($CSSexclude->url))
        {
            $CSSurl = preg_replace('/\s/', '', var_export($CSSexclude->url, true));
            $CSSurl = str_replace(',)', ')', $CSSurl);
        }
        if (! empty($CSSexclude->query))
        {
            $CSSquery = preg_replace('/\s/', '', var_export($CSSexclude->query, true));
            $CSSquery = str_replace(',)', ')', $CSSquery);
        }
        if (! empty($CSSexclude->settings))
        {
            $CSSsettings = var_export($CSSexclude->settings, true);
        }
        if (! empty($CSSexclude->component))
        {
            $CSScomponents = preg_replace('/\s/', '', var_export($CSSexclude->component, true));
            $CSScomponents = str_replace(',)', ')', $CSScomponents);
        }
        if (! empty($CSSexclude->url_strings))
        {
            $CSSurlstrings = preg_replace('/\s/', '', var_export($CSSexclude->url_strings, true));
            $CSSurlstrings = str_replace(',)', ')', $CSSurlstrings);
        }
        // end css excludes
        // img excludes
        if (! empty($IMGexclude->url))
        {
            $IMGurl = preg_replace('/\s/', '', var_export($IMGexclude->url, true));
            $IMGurl = str_replace(',)', ')', $IMGurl);
        }
        if (! empty($IMGexclude->query))
        {
            $IMGquery = preg_replace('/\s/', '', var_export($IMGexclude->query, true));
            $IMGquery = str_replace(',)', ')', $IMGquery);
        }
        if (! empty($IMGexclude->settings))
        {
            $IMGsettings = var_export($IMGexclude->settings, true);
        }
        if (! empty($IMGexclude->component))
        {
            $IMGcomponents = preg_replace('/\s/', '', var_export($IMGexclude->component, true));
            $IMGcomponents = str_replace(',)', ')', $IMGcomponents);
        }
        if (! empty($IMGexclude->url_strings))
        {
            $IMGurlstrings = preg_replace('/\s/', '', var_export($IMGexclude->url_strings, true));
            $IMGurlstrings = str_replace(',)', ')', $IMGurlstrings);
        }
        // end img excludes
        ob_start();
        echo "<?php

 /**
 * @Multicache javascript|css strategy handler
 * @version 1.0.1.5
 * @package com_multicache
 * @copyright Copyright (C) Multicache.org 2015. All rights reserved.
 * @licenseGNU GENERAL PUBLIC LICENSE  see LICENSE.txt - http://multicache.org/terms-conditions/end-user-license-agreement.html
 * @author Wayne DSouza <consulting@OnlineMarketingConsultants.in> - http://OnlineMarketingConsultants.in
 * @This class may be overwritten automatically - make your changes in the control panel
 */

defined('JPATH_PLATFORM') or die;
class JsStrategy{
            ";
        $cl_buf = ob_get_clean();
        if (! empty($switch))
        {
            ob_start();
            echo "
public static \$js_switch = " . $switch . "	;
    ";
            $cl_buf .= ob_get_clean();
        }
        if (! empty($switch_css))
        {
            ob_start();
            echo "
public static \$css_switch = " . $switch_css . "	;
    ";
            $cl_buf .= ob_get_clean();
        }
        if (! empty($switch_img))
        {
            ob_start();
            echo "
public static \$img_switch = " . $switch_img . "	;
    ";
            $cl_buf .= ob_get_clean();
        }
        //pagespeed
        if (! empty($pagespeed_strategy))
        {
        	ob_start();
        	echo "
public static \$pagespeed_strategy = " . $pagespeed_strategy . "	;
    ";
        	$cl_buf .= ob_get_clean();
        }
        //end pagespeed
        if (! empty($stubs))
        {
            ob_start();
            echo "
public static \$stubs = " . $stubs . " ;
 ";
            $cl_buf .= ob_get_clean();
        }
        
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
        // startcssexcludes
        if (! empty($CSSexclude->settings) && (! empty($CSSexclude->url) || ! empty($CSSexclude->query)))
        {
            ob_start();
            echo "
public static \$CSSsetting = " . $CSSsettings . ";
  ";
            $cl_buf .= ob_get_clean();
        }
        if (! empty($CSSexclude->url))
        {
            ob_start();
            echo "
public static \$CSSCludeUrl = " . $CSSurl . ";
  ";
            $cl_buf .= ob_get_clean();
        }
        
        if (! empty($CSSexclude->query))
        {
            
            ob_start();
            echo "
public static \$CSSCludeQuery = " . $CSSquery . ";
  ";
            $cl_buf .= ob_get_clean();
        }
        if (! empty($CSSexclude->component))
        {
            
            ob_start();
            echo "
public static \$CSSexcluded_components = " . $CSScomponents . ";
  ";
            $cl_buf .= ob_get_clean();
        }
        if (! empty($CSSexclude->url_strings))
        {
            
            ob_start();
            echo "
public static \$CSSurl_strings = " . $CSSurlstrings . ";
  ";
            $cl_buf .= ob_get_clean();
        }
        
        // endcssexcludes
        // startimgexcludes
        if (! empty($IMGexclude->settings) && (! empty($IMGexclude->url) || ! empty($IMGexclude->query)))
        {
            ob_start();
            echo "
public static \$IMGsetting = " . $IMGsettings . ";
  ";
            $cl_buf .= ob_get_clean();
        }
        if (! empty($IMGexclude->url))
        {
            ob_start();
            echo "
public static \$IMGCludeUrl = " . $IMGurl . ";
  ";
            $cl_buf .= ob_get_clean();
        }
        
        if (! empty($IMGexclude->query))
        {
            
            ob_start();
            echo "
public static \$IMGCludeQuery = " . $IMGquery . ";
  ";
            $cl_buf .= ob_get_clean();
        }
        if (! empty($IMGexclude->component))
        {
            
            ob_start();
            echo "
public static \$IMGexcluded_components = " . $IMGcomponents . ";
  ";
            $cl_buf .= ob_get_clean();
        }
        if (! empty($IMGexclude->url_strings))
        {
            
            ob_start();
            echo "
public static \$IMGurl_strings = " . $IMGurlstrings . ";
  ";
            $cl_buf .= ob_get_clean();
        }
        
        if (isset($lazy_load_params))
        {
            ob_start();
            echo "
public static \$img_tweak_params = " . $lazy_load_params . "	;
    ";
            $cl_buf .= ob_get_clean();
        }
        // endimgexcludes
        
        if (! empty($signature_hash))
        {
            
            ob_start();
            echo "
public static function getJsSignature(){
\$sigss = " . trim($signature_hash) . ";
Return \$sigss;
}";
            $cl_buf .= ob_get_clean();
        }
        if (! empty($loadsection))
        {
            
            ob_start();
            echo "
public static function getLoadSection(){
\$loadsec = " . trim($loadsection) . ";
Return \$loadsec;
}";
            $cl_buf .= ob_get_clean();
        }
        if (! empty($signature_hash_css))
        {
            
            ob_start();
            echo "
public static \$sig_css = " . trim($signature_hash_css) . ";";
            
            $cl_buf .= ob_get_clean();
        }
        // start
        if (! empty($dontmove_hash))
        {
            
            ob_start();
            echo "
public static \$dontmove_js = " . trim($dontmove_hash) . ";";
            
            $cl_buf .= ob_get_clean();
        }
        if (! empty($dontmove_urls))
        {
            ob_start();
            echo "
public static \$dontmove_urls_js = " . trim($dontmove_urls) . ";";
            
            $cl_buf .= ob_get_clean();
        }
        if (! empty($allow_multiple_orphaned))
        {
            ob_start();
            echo "
public static \$allow_multiple_orphaned = " . trim($allow_multiple_orphaned) . ";";
            
            $cl_buf .= ob_get_clean();
        }
        // stop
        if (! empty($loadsection_css))
        {
            
            ob_start();
            echo "
public static \$loadsec_css = " . trim($loadsection_css) . ";";
            
            $cl_buf .= ob_get_clean();
        }
        ob_start();
        echo "
}
?>";
        $cl_buf .= ob_get_clean();
        
        $cl_buf = serialize($cl_buf);
        $dir = JPATH_COMPONENT . '/lib';
        $filename = 'jscachestrategy.php';
        $success = self::writefileTolocation($dir, $filename, $cl_buf);
        Return $success;
    
    }

    public static function getJScodeUrl($key, $type = null, $jquery_scope = "jQuery", $media = "default", $params = null)
    {
        
        // $base_url = '//' . str_replace("http://", "", strtolower(substr(JURI::root(), 0, - 1)) . '/media/com_multicache/assets/js/jscache/');
        $base_url = JURI::root() . 'media/com_multicache/assets/js/jscache/';
        if (isset($type) && $type == "raw_url")
        {
            Return $base_url . $key . ".js?mediaVersion=" . $media;
        }
        // script_url
        
        if (isset($type) && $type == "script_url")
        {
            $script = '<script src="' . $base_url . $key . '.js?mediaVersion=' . $media . '"   type="text/javascript" ';
            if (isset($params) && ! empty($params['resultant_async']))
            {
                $script .= ' async ';
            }
            if (isset($params) && ! empty($params['resultant_defer']))
            {
                $script .= ' defer ';
            }
            $script .= '></script>';
            Return serialize($script);
        }
        $url = $jquery_scope . '.getScript(' . '"' . $base_url . $key . '.js?mediaVersion=' . $media . '"' . ');';
        
        Return serialize($url);
    
    }

    public static function getCsscodeUrl($key, $type = null, $jquery_scope = "jQuery", $media = "default")
    {

        /*
         * $base_url = '//' . str_replace(array(
         * "http://",
         * "https://"
         * ), array(
         * "",
         * ""
         * ), strtolower(substr(JURI::root(), 0, - 1)) . '/media/com_multicache/assets/css/csscache/');
         */
        $base_url = JURI::root() . 'media/com_multicache/assets/css/csscache/';
        if (isset($type) && $type == "raw_url")
        {
            Return $base_url . $key . ".css?mediaVersion=" . $media;
        }
        // link_url
        
        if (isset($type) && $type == "link_url")
        {
            $css_link = '<link href="' . $base_url . $key . '.css?mediaVersion=' . $media . '" rel="stylesheet" type="text/css" />';
            Return serialize($css_link);
        }
        if (isset($type) && $type == "html_url")
        {
            $html_link = $base_url . $key . ".html?mediaVersion=" . $media;
            Return $html_link;
        }
        $url = $jquery_scope . '.getScript(' . '"' . $base_url . $key . '.css?mediaVersion=' . $media . '"' . ');';
        
        Return serialize($url);
    
    }

    public static function noscriptWrap($link , $non_serialized = false)
    {
if(false === $non_serialized)
{
        Return '<noscript>' . unserialize($link) . '</noscript>';
}
else {
	Return '<noscript>' . $link . '</noscript>';
}
    
    }

    public static function getCsslinkUrl($urlname, $type = 'link_url', $media = "default")
    {
    	if(strpos($urlname , '//') === 0 || (strpos($urlname , 'font') !== false && strpos($urlname , 'family=') !== false ))
    	{
    		$type = "plain_url";
    	}
        // link_url
        // well need to ensure that were adding the query format right here
        if (isset($type) && $type == "link_url")
        {
            $uri = JURI::getInstance($urlname);
            $uri->setVar('mediaVersion', $media);
            $css_link = '<link href="' . $uri->toString() . '" rel="stylesheet" type="text/css"/>';
        }
        elseif (isset($type) && $type == "plain_url")
        {
            $css_link = '<link href="' . $urlname . '" rel="stylesheet" type="text/css"/>';
        }
        Return $css_link;
    
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

    public static function getloadableSourceScript($script_bit, $async = false, $params = false)
    {

        if (empty($script_bit))
        {
            Return false;
        }
        $tag = '<script src="' . $script_bit . '"  type="text/javascript"';
        
        if ($async || ! empty($params['resultant_async']))
        {
            $tag .= ' async ';
        }
        if (! empty($params['resultant_defer']))
        {
            $tag .= ' defer ';
        }
        $tag .= '></script>';
        Return $tag;
    
    }

    public static function wrapDelay($code , $jquery_scope = "jQuery")
    {
    	$delay_code = 'function MulticacheCallDelay(){'.$code.'}function asyncMulticacheDelay(e,n,i,l){if(i="undefined"==typeof i?1:++i,l="undefined"==typeof l?1e4:l,n="undefined"==typeof n?10:n,e="undefined"==typeof e?"MulticacheCallDelay":e,"undefined"==typeof window.'.  $jquery_scope  .'&&"undefined"==typeof window.MULTICACHEDELAYCALLED&&l>=i)setTimeout(function(){console.log("delayrouteBinit run - "+i+" time -"+n),asyncMulticacheDelay(e,n,i)},n);else{if("undefined"==typeof window.MULTICACHEDELAYCALLED){var d="undefined"==typeof window.MULTICACHEDELAYCALLED?"undefined":"defined",o="undefined"==typeof window.MULTICACHEDELAYCALLED?"undefined":"defined";console.log("calling delay..... "+typeof window.MULTICACHEDELAYCALLED+" a- "+d+" b- "+o),MulticacheCallDelay(),window.MULTICACHEDELAYCALLED=!0}console.log("rolling in else delay")}}asyncMulticacheDelay("MulticacheCallDelay",1);';
    	Return $delay_code;
    }
    
    public static function getMAU()
    {
    	$debug = true;
    
    	$mau = <<<MAU
var multicache_MAU = function (zfunc_a, zfunc_b, check_func, time , count , max)
    {
	count =  typeof count === 'undefined'? 1: ++count;
	max = typeof max === 'undefined'? 30: max;
	time = typeof time === 'undefined' ? 30 :time;
  zfunc_a = typeof zfunc_a === 'undefined'? reject(Error('resolve func not defined')) : zfunc_a;
  zfunc_b = typeof zfunc_b === 'undefined'? reject(Error('reject func not defined ')) : zfunc_b;
MAU;
    	if($debug)
    	{
    		$mau .= <<<MAU
console.log('checktype mau' + check_func.checkType() + ' ' + count);
MAU;
    	}
    	$mau .= <<<MAU
	if(  'undefined' === check_func.checkType() && count<= max){
		setTimeout(function(){
MAU;
    	if($debug)
    	{
    		$mau .= <<<MAU
console.log('routeB run - ' + count + ' time -' + time );
MAU;
    	}
    	$mau .= <<<MAU
multicache_MAU(zfunc_a, zfunc_b,check_func, time , count);
			}, time);
    
	}
	else if(count<= max)
		{
MAU;
    	if($debug)
    	{
    		$mau .= <<<MAU
console.log('mau passsed typeof '+check_func.name + '  ' +  check_func.checkType());
MAU;
    	}
    	$mau .= <<<MAU
 zfunc_a(10);
		}
    else{
MAU;
    	if($debug)
    	{
    		$mau .= <<<MAU
console.log('mau failed reject alert');
MAU;
    	}
    	$mau .= <<<MAU
zfunc_b();
  
    }
	}
MAU;
    	//$mau = 'var multicache_MAU=function(e,n,f,d,t,c){t="undefined"==typeof t?1:++t,c="undefined"==typeof c?6:c,d="undefined"==typeof d?30:d,e="undefined"==typeof e?reject(Error("resolve func not defined")):e,n="undefined"==typeof n?reject(Error("reject func not defined ")):n,"undefined"===f.checkType()&&c>=t?setTimeout(function(){multicache_MAU(e,n,f,d,t)},d):c>=t?e(10):n()};';
        	$mau = 'var multicache_MAU=function(e,n,o,c,t,f){t="undefined"==typeof t?1:++t,f="undefined"==typeof f?30:f,c="undefined"==typeof c?30:c,e="undefined"==typeof e?reject(Error("resolve func not defined")):e,n="undefined"==typeof n?reject(Error("reject func not defined ")):n,console.log("checktype mau"+o.checkType()+" "+t),"undefined"===o.checkType()&&f>=t?setTimeout(function(){console.log("routeB run - "+t+" time -"+c),multicache_MAU(e,n,o,c,t)},c):f>=t?(console.log("mau passsed typeof "+o.name+"  "+o.checkType()),e(10)):(console.log("mau failed reject alert"),n())};';
    	Return $mau;
    }
    
    public static function getloadableCodeScript($code_bit, $async = false, $unserialized = null, $params = null)
    {

        if (empty($code_bit))
        {
            Return false;
        }
        $tag = '<script type="text/javascript"';
        if (isset($params) && ! empty($params['resultant_async']) || $async)
        {
            $tag .= ' async';
        }
        if (isset($params) && ! empty($params['resultant_defer']))
        {
            $tag .= ' defer';
        }
        $tag .= '>';
        if (! isset($unserialized))
        {
            $tag .= unserialize($code_bit);
        }
        elseif (isset($unserialized))
        {
            $tag .= $code_bit;
        }
        
        $tag .= '</script>';
        
        Return $tag;
    
    }

    public static function getloadableCodeCss($code_bit, $media = null, $scoped = null, $serialized = null)
    {

        if (empty($code_bit))
        {
            Return false;
        }
        
        if (isset($media) && ! isset($scoped))
        {
            Return '<style   type="text/css" media="' . $media . '">' . unserialize($code_bit) . '</style>';
        }
        elseif (isset($media) && isset($scoped))
        {
            Return '<style  scoped  type="text/css" media="' . $media . '">' . unserialize($code_bit) . '</style>';
        }
        elseif (! isset($media) && isset($scoped))
        {
            Return '<style  scoped  type="text/css" >' . unserialize($code_bit) . '</style>';
        }
        
        if (isset($serialized))
        {
            
            Return '<style  type="text/css">' . $code_bit . '</style>';
        }
        Return '<style  type="text/css">' . unserialize($code_bit) . '</style>';
    
    }

    public static function checkComponentParams()
    {

        $app = JFactory::getApplication();
        $params = JComponentHelper::getParams('com_multicache');
        $params_d = (array) json_decode($params);
        if (! empty($params_d))
        {
            Return;
        }
        $extensionTable = JTable::getInstance('extension');
        $componentId = $extensionTable->find(array(
            'element' => 'com_multicache',
            'type' => 'component'
        ));
        $params->set('tolerance_highlighting', 1);
        $params->set('danger_tolerance_factor', 3);
        $params->set('danger_tolerance_color', '#a94442');
        $params->set('warning_tolerance_factor', 2.5);
        $params->set('warning_tolerance_color', '#8a6d3b');
        $params->set('success_tolerance_color', '#468847');
        $extensionTable->load($componentId);
        $extensionTable->bind(array(
            'params' => $params->toString()
        ));
        if (! $extensionTable->check())
        {
            $app->enqueueMessage(JText::_('COM_MULTICACHE_HELPER_CHECKCOMPPARAM_FAILED_TABLECHECK') . '  ' . $extensionTable->getError(), 'error');
            return false;
        }
        if (! $extensionTable->store())
        {
            $app->enqueueMessage(JText::_('COM_MULTICACHE_HELPER_CHECKCOMPPARAM_FAILED_TABLESTORE') . '  ' . $extensionTable->getError(), 'error');
            return false;
        }
    
    }

    protected static function extractDelayType($delay)
    {

        $type = null;
        foreach ($delay as $key => $value)
        {
            if (! empty($value["delay_type"]))
            {
                $type = $value["delay_type"];
                break;
            }
        }
        Return $type;
    
    }

    protected static function getGenericYesNo()
    {

        $options = array();
        $options[] = JHtml::_('select.option', 0, JText::_('JNo'));
        $options[] = JHtml::_('select.option', 1, JText::_('JYes'));
        return $options;
    
    }

    protected static function getLoadSectionOptions()
    {

        $options = array();
        $options[] = JHtml::_('select.option', 0, JText::_('COM_MULTICACHE_LOADSECTION_0_DEFAULT_LABEL'));
        $options[] = JHtml::_('select.option', 1, JText::_('COM_MULTICACHE_LOADSECTION_1_PREHEAD_LABEL'));
        $options[] = JHtml::_('select.option', 2, JText::_('COM_MULTICACHE_LOADSECTION_2_HEAD_LABEL'));
        $options[] = JHtml::_('select.option', 3, JText::_('COM_MULTICACHE_LOADSECTION_3_BODY_LABEL'));
        $options[] = JHtml::_('select.option', 4, JText::_('COM_MULTICACHE_LOADSECTION_4_FOOTER_LABEL'));
        $options[] = JHtml::_('select.option', 5, JText::_('COM_MULTICACHE_LOADSECTION_5_DONTLOAD_LABEL'));
        $options[] = JHtml::_('select.option', 6, JText::_('COM_MULTICACHE_LOADSECTION_6_DONTMOVE_LABEL'));
        return $options;
    
    }

    public static function cacheStatus()
    {

        Return self::getCache();
    
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

    public static function Checkurl($url, $mediaVersion)
    {

        if (preg_match('/[^a-zA-Z0-9\/\:\?\#\.]/', $url))
        {
            if (strpos($url, '//') === 0)
            {
                $url = 'http:' . $url;
            }
        }
        else
        {
            $c_uri = JURI::getInstance($url);
            $c_uri->setVar('mediaFormat', $mediaVersion);
            $url = $c_uri->toString();
        }
        
        Return $url;
    
    }

    protected static function getCssDelayTypes()
    {

        $options = array();
        $options[] = JHtml::_('select.option', 'async', JText::_('COM_MULTICACHE_CSS_DELAY_TYPE_OPTION_ASYNC'));
        $options[] = JHtml::_('select.option', 'mousemove', JText::_('COM_MULTICACHE_CSS_DELAY_TYPE_OPTION_MOUSEMOVE'));
        $options[] = JHtml::_('select.option', 'scroll', JText::_('COM_MULTICACHE_CSS_DELAY_TYPE_OPTION_SCROLL'));
        return $options;
    
    }

    public static function getGroupAsyncSrcbits($group , $params = false)
    {
    	if(empty($group))
    	{
    		Return false;
    	}
    	$inline_code = '';
    	$link_code = '';
    	$excluded_code = '';
    	$x_group = !empty($params['groups_async_exclude'])? $params['groups_async_exclude'] : false;
    	$d_group = !empty($params['css_groupsasync_delay'])? $params['css_groupsasync_delay'] : false;
    	foreach($group As $key => $grp)
    	{
    		$excluded_flag = false;
    		if(false !== $x_group)
    		{
    			foreach($x_group As $k => $name)
    			{
    				
    				if((string)trim($name) === (string)$key)
    				{
    					$excluded_flag = true;
    					break;
    				}
    			}
    		}
    		if($grp['success'] === true && false === $excluded_flag)
    		{
    			if(isset($d_group[$key])){
    			$inline_code .= 'loadCSS( "' . $grp['url']  . '" ,'.$d_group[$key].' );';
    			}
    			else {
    				$inline_code .= 'loadCSS( "' . $grp['url'] . '" );';
    			}
    			$link_code   .= '<link href="' . $grp['url'] . '" rel="stylesheet" type="text/css" />';
    		}
    		else{
    			$excluded_code .= '<link href="' . $grp['url'] . '" rel="stylesheet" type="text/css" />';
    		}
    	}
    	if("1" === $params['css_groupsasync'])
    	{
    	$inline_code .= 'window.MULTICACHEASYNCNONOLEND = 1;loadStackMulticache(window.MULTICACHEASYNCOLSTACK);';
    	}
    	$return = array();
    	$return['inline_code'] =$inline_code;
    	$return['noscript'] = $link_code;
    	$return['excluded_code'] = $excluded_code;
    	Return $return;
    }
    
    public static function getAsyncSrcbits($async_delay)
    {

        if (empty($async_delay["items"]))
        {
            Return false;
        }
        $src_bits_async = '';
        foreach ($async_delay["items"] as $key => $item)
        {
        	$c_alias = !empty($item['cdnalias']) && !empty($item['cdn_url_css']);
           /* if ((! empty($item["serialized_code"]) && !$c_alias )|| (empty($item["href_clean"]) && !$c_alias))*/
            if(!$c_alias && (! empty($item["serialized_code"]) || empty($item["href_clean"] )))
            {
                continue; // we should have already integrated inline code to async.css by now
            }
            if(!empty($item['cdnalias']) && !empty($item['cdn_url_css']))
            {
            	$src_bit = $item['cdn_url_css'];
            }
            elseif (preg_match('/[^a-zA-Z0-9\/\:\?\#\.]/', $item["href"]) && empty($item["internal"]))
            {
            	//please comment on above match
                $src_bit = $item["href"];
            }
            else
            {
                $src_bit = ! empty($item["absolute_src"]) ? $item["absolute_src"] : (! empty($item["href_clean"]) ? $item["href_clean"] : $item["href"]);
            }
            $src_bit_inline = 'loadCSS( "' . $src_bit . '" );';
            $src_bits_async .= $src_bit_inline;
        }
        if (isset($async_delay["inline_async"]) && $async_delay["inline_async"] == true)
        {
            $base_url = '//' . str_replace("http://", "", strtolower(substr(JURI::root(), 0, - 1)) . '/media/com_multicache/assets/css/csscache/');
            $inline_async = $base_url . $async_delay["delay_callable_url"];
            $inline_async = 'loadCSS( "' . $inline_async . '" );';
            $src_bits_async .= $inline_async;
        }
        Return $src_bits_async;
    
    }

    protected static function getDelayTypes()
    {

        $options = array();
        
        $options[] = JHtml::_('select.option', 'mousemove', JText::_('COM_MULTICACHE_DELAY_TYPE_OPTION_MOUSEMOVE'));
        $options[] = JHtml::_('select.option', 'scroll', JText::_('COM_MULTICACHE_DELAY_TYPE_OPTION_SCROLL'));
        $options[] = JHtml::_('select.option', 'onload', JText::_('COM_MULTICACHE_DELAY_TYPE_OPTION_ONLOAD'));
        return $options;
    
    }

    protected static function getCssGroupNumber()
    {

        $options = array();
        
        $options[] = JHtml::_('select.option', '0', JText::_('COM_MULTICACHE_CSSGROUPNUMBER_OPTION_GROUPDEFAULT_LABEL'));
        $options[] = JHtml::_('select.option', '1', JText::_('COM_MULTICACHE_CSSGROUPNUMBER_OPTION_GROUPONE_LABEL'));
        $options[] = JHtml::_('select.option', '2', JText::_('COM_MULTICACHE_CSSGROUPNUMBER_OPTION_GROUPTWO_LABEL'));
        $options[] = JHtml::_('select.option', '3', JText::_('COM_MULTICACHE_CSSGROUPNUMBER_OPTION_GROUPTHREE_LABEL'));
        $options[] = JHtml::_('select.option', '4', JText::_('COM_MULTICACHE_CSSGROUPNUMBER_OPTION_GROUPFOUR_LABEL'));
        $options[] = JHtml::_('select.option', '5', JText::_('COM_MULTICACHE_CSSGROUPNUMBER_OPTION_GROUPFIVE_LABEL'));
        $options[] = JHtml::_('select.option', '6', JText::_('COM_MULTICACHE_CSSGROUPNUMBER_OPTION_GROUPSIX_LABEL'));
        $options[] = JHtml::_('select.option', '7', JText::_('COM_MULTICACHE_CSSGROUPNUMBER_OPTION_GROUPSEVEN_LABEL'));
        $options[] = JHtml::_('select.option', '8', JText::_('COM_MULTICACHE_CSSGROUPNUMBER_OPTION_GROUPEIGHT_LABEL'));
        $options[] = JHtml::_('select.option', '9', JText::_('COM_MULTICACHE_CSSGROUPNUMBER_OPTION_GROUPNINE_LABEL'));
        $options[] = JHtml::_('select.option', '10', JText::_('COM_MULTICACHE_CSSGROUPNUMBER_OPTION_GROUPTEN_LABEL'));
        return $options;
    
    }

    protected static function setinitialiseScriptpeice($name, $property, $classname = "MulticachePageScripts")
    {
        // $name-> $advertisements
        // $property -> advertisements
        // initialise the advertisements array
        if (isset($name))
        {
            $name = var_export($name, true);
        }
        else
        {
            // set it in the specific case it exists ie..everything is set except social
            if (! class_exists($classname))
            {
                Return false;
            }
            
            if (property_exists($classname, $property))
            {
                
                $name = $classname::$$property;
                $name = var_export($name, true);
            }
        }
        // end initialise the advertisements array
        Return $name;
    
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
            $app->enqueueMessage(JText::_('COM_CONFIG_ERROR_CONFIGURATION_PHP_NOTWRITABLE'), 'notice');
        }
        
        // Attempt to write the configuration file as a PHP class named JConfig.
        $configuration = $config->toString('PHP', array(
            'class' => 'JConfig',
            'closingtag' => false
        ));
        
        if (! JFile::write($file, $configuration))
        {
            throw new RuntimeException(JText::_('COM_CONFIG_ERROR_WRITE_FAILED'));
        }
        
        // Attempt to make the file unwriteable if using FTP.
        if (! $ftp['enabled'] && JPath::isOwner($file) && ! JPath::setPermissions($file, '0444'))
        {
            $app->enqueueMessage(JText::_('COM_MULTICACHE_ERROR_CONFIGURATION_PHP_NOTUNWRITABLE'), 'notice');
        }
        
        return true;
    
    }
    public static function writeExtStrategy($dir, $filename, $contents)
    {
    	self::writefileTolocation($dir, $filename, $contents);
    }
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
            $app->enqueueMessage(JText::_('COM_MULTICACHE_HELPER_ERROR_WRITEFILETOLOCATION_FILELOC_NOTWRITABLE'), 'warning');
            $emessage = "COM_MULTICACHE_HELPER_ERROR_WRITEFILETOLOCATION_FILELOC_NOTWRITABLE";
            JLog::add(JText::_($emessage) . '	' . $file, JLog::WARNING);
        }
        $class_path = unserialize($contents);
        $class_path = str_ireplace("\x0D", "", $class_path);
        if (! JFile::write($file, $class_path))
        {
            throw new RuntimeException(JText::_('COM_MULTICACHE_ERROR_WRITE_FILETOLOCATION_FAILED') . '	' . $file);
            $emessage = "COM_MULTICACHE_ERROR_WRITE_FILETOLOCATION_FAILED";
            JLog::add(JText::_($emessage) . '	' . $file, JLog::ERROR);
        }
        
        // Attempt to make the file unwriteable if using FTP.
        if (! $ftp['enabled'] && JPath::isOwner($file) && ! JPath::setPermissions($file, '0444'))
        {
            $app->enqueueMessage(JText::_('COM_MULTICACHE_WRITEFILETOLOCATION_ERROR_NOTUNWRITABLE') . '	' . $file, 'warning');
            $emessage = "COM_MULTICACHE_WRITEFILETOLOCATION_ERROR_NOTUNWRITABLE";
            JLog::add(JText::_($emessage) . '	' . $file, JLog::WARNING);
        }
        
        return true;
    
    }

}
