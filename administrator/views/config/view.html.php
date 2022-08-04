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

jimport('joomla.application.component.view');
JLoader::register('MulticachePageScripts', JPATH_ROOT . '/administrator/components/com_multicache/lib/pagescripts.php');
JLoader::register('MulticachePageCss', JPATH_ROOT . '/administrator/components/com_multicache/lib/pagecss.php');

class MulticacheViewConfig extends JViewLegacy
{

    protected $state;

    protected $item;

    protected $form;

    protected static $_transpose_page_object = null;

    /**
     * Display the view
     */
    public function display($tpl = null)
    {
        //
        $app = JFactory::getApplication();
        $this->state = $this->get('State');
        $this->item = $this->get('Item');
        $this->form = $this->get('Form');
        
        $pre_excluded_components = ! empty($this->item->excluded_components) ? unserialize($this->item->excluded_components) : false;
        $css_excluded_components = ! empty($this->item->cssexcluded_components) ? unserialize($this->item->cssexcluded_components) : false;
        $img_excluded_components = ! empty($this->item->imgexcluded_components) ? unserialize($this->item->imgexcluded_components) : false;
        
        $this->componentexclusions = $this->prepareComponentExclusions($pre_excluded_components);
        $this->csscomponentexclusions = $this->prepareComponentExclusions($css_excluded_components, 'css');
        $this->imagescomponentexclusions = $this->prepareComponentExclusions($img_excluded_components, 'img');
        
        /*
         * A check for plugin
         * availability
         * activation
         */
        $this->get('CheckMulticachePlugin');
        $this->fastcachefailed = $this->get('fastCacheFailStatus');
        if (! empty($this->fastcachefailed))
        {
            $this->form->setValue('cache_handler', '', 'file');
        }
        
        $this->templatePage = $this->get('TemplatePage'); // the template page contains two object the pageobject & pagetransposeobject
        $this->cssPage = $this->get('CssPage');
        if (! empty($this->templatePage))
        {
            $this->stats = new stdClass();
            $this->stats->total_scripts = $this->get('TotalScripts');
            $this->stats->unique_scripts = $this->get('UniqueScripts');
            $this->stats->duplicate_scripts = (int) $this->stats->total_scripts - (int) $this->stats->unique_scripts;
            // self::$_transpose_page_object = $this->templatePage->pagetransposeobject;
            $this->segments = $this->getSegments($this->templatePage->pagetransposeobject);
            $this->segment_peices = count($this->get('TemplatePage')->pageobject); // stores count of scripts
            $this->unique_script_array = $this->get('UniqueScriptAsArray'); // at bay unique scripts ->dependencies on get(UniqueScrpts)
            
            $this->script_render = $this->makeSriptRenderable($this->templatePage->pageobject);
            if ($this->item->simulation_advanced && empty($this->item->advanced_simulation_lock) && ! empty(json_decode(JPluginHelper::getPlugin('system', 'multicache')->params)->lock_sim_control))
            {
                $app->enqueueMessage(JText::_('COM_MULTICACHE_ADVANCED_TEST_INPROGRESS_ADVANCED_SIM_LOCK_OFF_STATUS'), 'warning');
            }
            
            // get social
            $defered_scripts = new stdClass();
            if (class_exists('MulticachePageScripts') && property_exists('MulticachePageScripts', 'social'))
            {
                $social_post_segregation = MulticachePageScripts::$social;
                $defered_scripts->social_post_segregation = $social_post_segregation;
            }
            // get advertisements
            if (class_exists('MulticachePageScripts') && property_exists('MulticachePageScripts', 'advertisements'))
            {
                $advertisements_post_segregation = MulticachePageScripts::$advertisements;
                $defered_scripts->advertisements_post_segregation = $advertisements_post_segregation;
            }
            // get async
            if (class_exists('MulticachePageScripts') && property_exists('MulticachePageScripts', 'async'))
            {
                $async_post_segregation = MulticachePageScripts::$async;
                $defered_scripts->async_post_segregation = $async_post_segregation;
            }
            if (class_exists('MulticachePageScripts') && property_exists('MulticachePageScripts', 'delayed'))
            {
                $delayed_post_segregation = MulticachePageScripts::$delayed;
                $defered_scripts->delayed = $this->collateDelayed($delayed_post_segregation);
            }
            
            if (! empty($defered_scripts))
            {
                $this->script_defered_render = $this->makeDeferDelayRenderable($defered_scripts);
                $this->script_render .= $this->script_defered_render;
            }
        }
        
        if (! empty($this->cssPage) || property_exists('MulticachePageCss', 'delayed'))
        {
            $this->css_render = $this->makeCssRenderable($this->cssPage->cssobject);
            $this->css_stats = new stdClass();
            $this->css_stats->total_scripts = $this->get('CssTotalScripts');
            $this->css_stats->unique_scripts = $this->get('CssUniqueScripts');
            $this->css_stats->duplicate_scripts = (int) $this->css_stats->total_scripts - (int) $this->css_stats->unique_scripts;
            if (class_exists('MulticachePageCss') && property_exists('MulticachePageCss', 'delayed'))
            {
                
                $this->css_delayed = MulticachePageCss::$delayed;
                $this->css_delayed = $this->collateDelayed($this->css_delayed);
                
                $this->css_defered_render = $this->makeCssDelayRenderable($this->css_delayed);
                
                $this->css_render .= $this->css_defered_render;
            }
        }
        
        if (count($errors = $this->get('Errors')))
        {
            throw new Exception(implode("\n", $errors));
        }
        
        $this->addToolbar();
        // $this->get('AdvancedSettingCheck');
        
        parent::display($tpl);
    
    }

    protected function getLibraryKey($obj)
    {

        $library_key = null;
        foreach ($obj as $key => $object)
        {
            if (! empty($object["params"]["library"]))
            {
                $library_key = $key;
                break;
            }
        }
        Return $library_key;
    
    }

    protected function prepareComponentExclusions($previously_excluded = null, $type = '')
    {

        $all_components = MulticacheHelper::getAllComponents();
        $component_strand = '';
        $sno = 1;
        $attribs = 'style="width:120px;"';
        
        foreach ($all_components as $component)
        {
            $selected = isset($previously_excluded[$component]) ? 1 : 0;
            $c_name = str_ireplace('com_', '', $component);
            if ($type == 'css')
            {
                $component_strand .= '<div class="control-group" id="' . $type . $component . '_componentexclusions"><div class="control-label">' . ucfirst($c_name) . '</div><div class=" controls">' . JHTML::_('select.genericlist', MulticacheHelper::getComponentOptions(), 'com_multicache_css_component_exclusions_' . $component, $attribs, 'value', 'text', $selected, false, false) . '</div></div>';
            }
            elseif ($type == 'img')
            {
                $component_strand .= '<div class="control-group" id="' . $type . $component . '_componentexclusions"><div class="control-label">' . ucfirst($c_name) . '</div><div class=" controls">' . JHTML::_('select.genericlist', MulticacheHelper::getComponentOptions(), 'com_multicache_img_component_exclusions_' . $component, $attribs, 'value', 'text', $selected, false, false) . '</div></div>';
            }
            else
            {
                $component_strand .= '<div class="control-group" id="' . $component . '_componentexclusions"><div class="control-label">' . ucfirst($c_name) . '</div><div class=" controls">' . JHTML::_('select.genericlist', MulticacheHelper::getComponentOptions(), 'com_multicache_component_exclusions_' . $component, $attribs, 'value', 'text', $selected, false, false) . '</div></div>';
            }
        }
        Return $component_strand;
    
    }

    protected function getIsloadsectionClass($obj)
    {

        if (empty($obj))
        {
            Return false;
        }
        $loadsection_class = 'hidden';
        foreach ($obj as $key => $object)
        {
            
            if ($object["params"]["loadsection"] != 0)
            {
                $loadsection_class = '';
                break;
            }
        }
        Return $loadsection_class;
    
    }

    protected function collateDelayed($delayed)
    {

        if (empty($delayed))
        {
            Return false;
        }
        $delay = null;
        foreach ($delayed as $type => $obj)
        {
            
            foreach ($obj as $item => $del)
            {
                if ($item == "items")
                {
                    foreach ($del as $key => $value)
                    {
                        $delay[$key] = $value;
                    }
                }
            }
        }
        
        Return $delay;
    
    }

    protected function makeRenderable($script_objects, $type)
    {

        $Sno = 0;
        $clean_code = array(
            "'",
            '"',
            " ",
            ";"
        );
        foreach ($script_objects as $key => $obj)
        {
            $Sno ++;
            if ($type == "delayed")
            {
                $delay_type_indicator = '<div class="span3 offset2"><p class="text-left">' . ucfirst(JText::_("COM_MULTICACHE_SCRIPT_LAYOUT_SCRIPT_DELAY_TYPE_INDICATOR")) . ' : ' . $obj["delay_type"] . '</p></div>';
            }
            else
            {
                $delay_type_indicator = "";
            }
            $name = ! empty($obj["src"]) ? (string) substr($obj["src"], 0, 120) : (string) strip_tags(substr(str_replace($clean_code, '', $obj["code"]), 0, 120));
            
            $renderable .= '<div class="content-fluid paddle" id="' . $obj["signature"] . '_page_script" >
          <div class="row-fluid center-block margin-buffer">
                        <div class="span1">' . $Sno . '</div>
                        <div class="span3" style="word-wrap: break-word;"><p class="text-left">' . ucfirst(JText::_("COM_MULTICACHE_SCRIPT_LAYOUT_SCRIPT_NAME")) . ' : ' . $name . '</p></div>
      ' . $delay_type_indicator . '
          </div>

     </div>';
        }
        Return $renderable;
    
    }

    protected function makeDelayCssRenderable($css_objects, $type)
    {

        $Sno = 0;
        $clean_code = array(
            "'",
            '"',
            " ",
            ";"
        );
        foreach ($css_objects as $key => $obj)
        {
            $Sno ++;
            if ($type == "delayed")
            {
                $delay_type_indicator = '<div class="span3 offset2"><p class="text-left">' . ucfirst(JText::_("COM_MULTICACHE_SCRIPT_LAYOUT_SCRIPT_DELAY_TYPE_INDICATOR")) . ' : ' . $obj["delay_type"] . '</p></div>';
            }
            else
            {
                $delay_type_indicator = "";
            }
            $name = ! empty($obj["href"]) ? (string) $obj["href_clean"] : (string) strip_tags(substr(str_replace($clean_code, '', $obj["code"]), 0, 120));
            
            $renderable .= '<div class="content-fluid paddle" id="' . $obj["signature"] . '_page_css" >
      <div class="row-fluid center-block margin-buffer">
      <div class="span1">' . $Sno . '</div>
      <div class="span3" style="word-wrap: break-word;"><p class="text-left">' . ucfirst(JText::_("COM_MULTICACHE_SCRIPT_LAYOUT_SCRIPT_NAME")) . ' : ' . $name . '</p></div>
      ' . $delay_type_indicator . '
      </div>

      </div>';
        }
        Return $renderable;
    
    }

    protected function makeCssDelayRenderable($defered_object)
    {

        $defered_delayed_script = '';
        if (! empty($defered_object))
        {
            
            // make the delay object
            $delayed_script = '<div class="async_defered"><legend> ' . JText::_("COM_MULTICACHE_CSSDELAYED_POST_SEGREGATION_TITLE_DELAYED_LABEL") . '</legend>';
            $delayed_script .= $this->makeDelayCssRenderable($defered_object, 'delayed') . '</div>';
            $defered_delayed_script .= $delayed_script;
        }
        
        Return $defered_delayed_script;
    
    }

    protected function makeDeferDelayRenderable($defered_object)
    {

        $defered_delayed_script = '';
        if (! empty($defered_object->social_post_segregation))
        {
            // make the social object
            $social_script = '<div class="social_defered"><legend> ' . JText::_("COM_MULTICACHE_SOCIAL_POST_SEGREGATION_TITLE_SOCIAL_LABEL") . '</legend>';
            $social_script .= $this->makeRenderable($defered_object->social_post_segregation, 'social') . '</div>';
            $defered_delayed_script .= $social_script;
        }
        
        if (! empty($defered_object->advertisements_post_segregation))
        {
            // make the advertisement object
            $advertisement_script = '<div class="advertisement_defered"><legend> ' . JText::_("COM_MULTICACHE_ADVERTISEMENT_POST_SEGREGATION_TITLE_ADVERTISEMENTS_LABEL") . '</legend>';
            $advertisement_script .= $this->makeRenderable($defered_object->advertisements_post_segregation, 'advertisements') . '</div>';
            $defered_delayed_script .= $advertisement_script;
        }
        if (! empty($defered_object->async_post_segregation))
        {
            // make the async object
            $async_script = '<div class="async_defered"><legend> ' . JText::_("COM_MULTICACHE_ASYNC_POST_SEGREGATION_TITLE_ASYNC_LABEL") . '</legend>';
            $async_script .= $this->makeRenderable($defered_object->async_post_segregation, 'async') . '</div>';
            $defered_delayed_script .= $async_script;
        }
        if (! empty($defered_object->delayed))
        {
            
            // make the delay object
            $delayed_script = '<div class="async_defered"><legend> ' . JText::_("COM_MULTICACHE_DELAYED_POST_SEGREGATION_TITLE_DELAYED_LABEL") . '</legend>';
            $delayed_script .= $this->makeRenderable($defered_object->delayed, 'delayed') . '</div>';
            $defered_delayed_script .= $delayed_script;
        }
        
        Return $defered_delayed_script;
    
    }

    protected function makeSriptRenderable($script_object)
    {
    	if(empty($script_object))
    	{
    		Return false;
    	}

        $library_key = $this->getLibraryKey($script_object); // var_dump($library_class);exit;
                                                             // do we show the loadsection reset button-> only if any one loadsection is set
        $loadsection_class = $this->getIsloadsectionClass($script_object);
        
        $loadsection_reset_button = '<button class="btn btn-danger btn-mini offset10 ' . $loadsection_class . '" id="reset_loadsection" title="' . ucfirst(JText::_("COM_MULTICACHE_SCRIPT_LAYOUT_RESET_LOADSECTION_LABEL")) . '">reset loadsection</button>';
        
        $Sno = 0;
        foreach ($script_object as $key => $obj)
        {
            // var_dump( $obj);//exit;
            $Sno ++;
            $cdn_url_default = isset($obj["params"]["cdn_url"]) ? $obj["params"]["cdn_url"] : "";
            $cdn_url_class = isset($obj["params"]["cdn_url"]) ? "" : " hidden";
            //ver1.0.1.1
            $ident_class =   isset($obj["params"]["delay"]) && isset($obj["params"]["delay_type"]) && $obj["params"]["delay"] === '1' && $obj["params"]["delay_type"]=== 'onload' ? "block" : "none";
             
            $mau_display   = isset($obj["params"]["promises"])  && $obj["params"]["promises"] === '1'  ? "block" : "none";
            $mautime_display   = isset($obj["params"]["promises"])  && $obj["params"]["promises"] === '1' && isset($obj["params"]["mau"]) && $obj["params"]["mau"]==='1'  ? "block" : "none";
            $checktype_display = isset($obj["params"]["promises"])  && $obj["params"]["promises"] === '1'  ? "block" : "none";
            $thenBack_display  = isset($obj["params"]["promises"])  && $obj["params"]["promises"] === '1'  ? "block" : "none";
            $ident_default = isset($obj["params"]["ident"]) ? $obj["params"]["ident"] : "";
            $checktype_default = isset($obj["params"]["checktype"]) ? $obj["params"]["checktype"] : "";
            $thenBack_default = isset($obj["params"]["thenBack"]) ? $obj["params"]["thenBack"] : "";
            $mautime_default = isset($obj["params"]["mautime"]) ? $obj["params"]["mautime"] : "";
            if (isset($library_key))
            {
                $library_class = ($key == $library_key) ? '' : ' invisible';
            }
            else
            {
                $library_class = "";
            }
            
            $delay_type = (isset($obj["params"]["delay"])) ? "block" : "none";
            $o_name = ! empty($obj['name']) ? substr($obj['name'], 0, 120) : '';
            $script_layout .= '<div class="content-fluid paddle" id="' . $obj["signature"] . '_page_script" >

              <div class="row-fluid center-block margin-buffer" style="max-width:100%;">
                <div class="span1">' . $Sno . '</div>
    		<div class="span3" style="word-wrap: break-word;"><p class="text-left">' . ucfirst(JText::_("COM_MULTICACHE_SCRIPT_LAYOUT_SCRIPT_NAME")) . ' : ' . $o_name . '</p></div>

    		<!--library -->
    		<div class="span2 hasTooltip library_selector ' . $library_class . '" title="' . JText::_("COM_MULTICACHE_SCRIPT_LAYOUT_SCRIPT_IS_LIBRARY_DESC") . '">' . ucfirst(JText::_("COM_MULTICACHE_SCRIPT_LAYOUT_SCRIPT_IS_LIBRARY")) . ' : ' . $obj["library"] . '</div>

    		<!-- load Section -->
    		<div class="span3 hasTooltip loadsection_selector" title="' . JText::_("COM_MULTICACHE_SCRIPT_LAYOUT_SCRIPT_LOADSECTION_DESC") . '">' . ucfirst(JText::_("COM_MULTICACHE_SCRIPT_LAYOUT_SCRIPT_LOADSECTION_LABEL")) . ' : ' . $obj["loadsection"] . '</div>

    		<!-- Advertisement-->
    		<div class="span2 hasTooltip advertisement_selector" title="' . JText::_("COM_MULTICACHE_SCRIPT_LAYOUT_SCRIPT_ADVERTISEMENT_DESC") . '">' . ucfirst(JText::_("COM_MULTICACHE_SCRIPT_LAYOUT_SCRIPT_ADVERTISEMENT_LABEL")) . ' : ' . $obj["advertisement"] . '</div>
</div><div class="row-fluid center-block margin-buffer">
    		<!-- Social -->
    		<div class="span2 hasTooltip social_selector" title="' . JText::_("COM_MULTICACHE_SCRIPT_LAYOUT_SCRIPT_SOCIAL_DESC") . '">' . ucfirst(JText::_("COM_MULTICACHE_SCRIPT_LAYOUT_SCRIPT_SOCIAL_LABEL")) . ' : ' . $obj["social"] . '</div>

    		
    		 
    		<!-- Delay -->
    		<div class="span2 hasTooltip delay_selector " title="' . JText::_("COM_MULTICACHE_SCRIPT_LAYOUT_SCRIPT_DELAY_DESC") . '">' . ucfirst(JText::_("COM_MULTICACHE_SCRIPT_LAYOUT_SCRIPT_DELAY_LABEL")) . ' : ' . $obj["delay"] . '</div>

    		<!-- Delay Type -->
    		<div class="span3 hasTooltip delaytype_selector" style="display:' . $delay_type . ';" title="' . JText::_("COM_MULTICACHE_SCRIPT_LAYOUT_SCRIPT_DELAYTYPE_DESC") . '">' . ucfirst(JText::_("COM_MULTICACHE_SCRIPT_LAYOUT_SCRIPT_DELAYTYPE_LABEL")) . ' : ' . $obj["delay_type"] . '</div>

    				<!--ident-->
    		<div id="multicache_ident_' . $key . '" class="span2 hasTooltip ident_selector center-block " style="display:' . $ident_class . ';"  title="' . ucfirst(JText::_("COM_MULTICACHE_SCRIPT_LAYOUT_SCRIPT_IDENT_DESC")) . '">
    				<div class="span7 ">' . ucfirst(JText::_("COM_MULTICACHE_SCRIPT_LAYOUT_SCRIPT_IDENT_LABEL")) . ' :
    	</div><input aria-invalid="false" name="ident_' . $key . '"	 value="' . $ident_default . '" class="input-sm span5" type="text" pattern="[\w:\/\.\?\=-]+">
    	</div><!--closes multicache_ident -->
           <!-- PROMISES -->
    			
    		<div class="span2 hasTooltip promises_selector offset2" title="'. JText::_("COM_MULTICACHE_SCRIPT_LAYOUT_SCRIPT_PROMISE_DESC"). '">' .ucfirst(JText::_("COM_MULTICACHE_SCRIPT_LAYOUT_SCRIPT_PROMISE_LABEL")) . ' : ' . $obj["promises"] . '</div>	
    		<div class="span2 hasTooltip mau_selector " title="' . JText::_("COM_MULTICACHE_SCRIPT_LAYOUT_SCRIPT_MAU_DESC") . '" style="display:' . $mau_display . ';">' . ucfirst(JText::_("COM_MULTICACHE_SCRIPT_LAYOUT_SCRIPT_MAU_LABEL")) . ' : ' . $obj["mau"] . '</div>
    		<!--mau time-->
    		<div id="multicache_mautime_' . $key . '" class="span2 hasTooltip mautime_selector center-block " style="display:' . $mautime_display . ';"  title="'.JText::_("COM_MULTICACHE_SCRIPT_LAYOUT_SCRIPT_MAUTIME_DESC") . '">
    				<div class="span4 offset3 ">' .ucfirst(JText::_("COM_MULTICACHE_SCRIPT_LAYOUT_SCRIPT_MAUTIME_LABEL")). ' :
    	</div><input aria-invalid="false" name="mautime_' . $key . '"	 value="' . $mautime_default . '" class="input-sm span4" type="text" pattern="[0-9]{2,3}">
    	</div>
    				<!--checktype-->
    				<div id="multicache_checktype_' . $key . '" class="span2 hasTooltip checktype_selector center-block " style="display:' . $checktype_display . ';"  title="' .JText::_("COM_MULTICACHE_SCRIPT_LAYOUT_SCRIPT_CHECKTYPE_DESC").  '">
    				<div class="span5  ">' .ucfirst(JText::_("COM_MULTICACHE_SCRIPT_LAYOUT_SCRIPT_CHECKTYPE_LABEL")).  ' :
    	</div><input aria-invalid="false" name="checkType_' . $key . '"	 value="' . $checktype_default . '" class="input-sm span6" type="text" pattern="[\w:\/\.\?\=-]+">
    	</div><!--closes multicache_checkType -->
    			<!--thenBack-->
    				<div id="multicache_thenBack_' . $key . '" class="span4 hasTooltip thenBack_selector center-block " style="display:' . $thenBack_display . ';"  title="' . JText::_("COM_MULTICACHE_SCRIPT_LAYOUT_SCRIPT_THENBACK_DESC"). '">
    				<div class="span5 ">' . ucfirst(JText::_("COM_MULTICACHE_SCRIPT_LAYOUT_SCRIPT_THENBACK_LABEL")). ' :
    	</div><input aria-invalid="false"  name="thenBack_' . $key . '"	 value="' .htmlentities($thenBack_default)  . '" class="input-lg " type="text" ">
    	</div><!--closes multicache_checkType -->
    	   <!-- END OF PROMISES -->
    		<!-- CDN ALIAS -->
    		<div class="span2 hasTooltip cdnalias_selector" title="' . JText::_("COM_MULTICACHE_SCRIPT_LAYOUT_SCRIPT_CDN_ALIAS_DESC") . '">' . ucfirst(JText::_("COM_MULTICACHE_SCRIPT_LAYOUT_SCRIPT_CDN_ALIAS_LABEL")) . ' : ' . $obj["cdnAlias"] . '</div>

    		<!--IGNORE -->
    		<div class="span2 hasTooltip " title="' . JText::_("COM_MULTICACHE_SCRIPT_LAYOUT_SCRIPT_IGNORE_DESC") . '">' . ucfirst(JText::_("COM_MULTICACHE_SCRIPT_LAYOUT_SCRIPT_IGNORE_LABEL")) . ' : ' . $obj["ignore"] . '</div>
    		</div>
    		<div class="row-fluid center-block ' . $cdn_url_class . '">

    	<!-- CDN URL-->

    	<div id="cdn_urld_' . $key . '" class="span12 hasTooltip cdnurl_selector center-block ' . $cdn_url_class . '" title="' . JText::_("COM_MULTICACHE_SCRIPT_LAYOUT_CDN_URL_ENTRY_DESC") . '">' . ucfirst(JText::_("COM_MULTICACHE_SCRIPT_LAYOUT_SCRIPT_CDN_URL_ENTRY_LABEL")) . ' :
    	<input aria-invalid="false" name="cdn_url_' . $key . '" id="cdn_url_' . $key . '" value="' . $cdn_url_default . '" class="span8 " type="url">
    	</div>
</div>
    </div>';
        }
        $script_layout = $loadsection_reset_button . $script_layout;
        Return $script_layout;
    
    }

    protected function makeCssRenderable($css_object)
    {

        if (empty($css_object))
        {
            Return false;
        }
        $loadsection_class = $this->getIsloadsectionClass($css_object);
        
        $loadsection_reset_button = '<button class="btn btn-danger btn-mini offset10 ' . $loadsection_class . '" id="reset_cssloadsection" title="' . ucfirst(JText::_("COM_MULTICACHE_SCRIPT_LAYOUT_RESET_LOADSECTION_LABEL")) . '">reset loadsection</button>';
        
        $Sno = 0;
        foreach ($css_object as $key => $obj)
        {
            // var_dump( $obj);//exit;
            $Sno ++;
            $cdn_url_default = isset($obj["params"]["cdn_url_css"]) ? $obj["params"]["cdn_url_css"] : "";
            $cdn_url_class = isset($obj["params"]["cdn_url_css"]) ? "" : " hidden";
            if (isset($library_key))
            {
                $library_class = ($key == $library_key) ? '' : ' invisible';
            }
            else
            {
                $library_class = "";
            }
            
            $delay_type_style = (isset($obj["params"]["delay"])) ? "block" : "none";
            $css_layout .= '<div class="content-fluid paddle" id="' . $obj["signature"] . '_page_css" >

              <div class="row-fluid center-block margin-buffer">
                <div class="span1">' . $Sno . '</div>
    		<div class="span3" style="word-wrap: break-word;"><p class="text-left">' . ucfirst(JText::_("COM_MULTICACHE_CSS_LAYOUT_CSS_NAME")) . ' : ' . $obj["name"] . '</p></div>

    		<!-- load Section -->
    		<div class="span2 hasTooltip loadsection_selector" title="' . JText::_("COM_MULTICACHE_CSS_LAYOUT_CSS_LOADSECTION_DESC") . '">' . ucfirst(JText::_("COM_MULTICACHE_CSS_LAYOUT_CSS_LOADSECTION_LABEL")) . ' : ' . $obj["loadsection"] . '</div>

    		<!-- grouping -->
    		<div class="span2 hasTooltip grouping_selector" title="' . JText::_("COM_MULTICACHE_CSS_LAYOUT_CSS_GROUPING_DESC") . '">' . ucfirst(JText::_("COM_MULTICACHE_CSS_LAYOUT_CSS_GROUPING_LABEL")) . ' : ' . $obj["grouping"] . '</div>

    		<!-- group_number -->
    		<div class="span2 hasTooltip group_number_selector "  title="' . JText::_("COM_MULTICACHE_CSS_LAYOUT_CSS_GROUPNUMBER_DESC") . '">' . ucfirst(JText::_("COM_MULTICACHE_CSS_LAYOUT_CSS_GROUPNUMBER_LABEL")) . ' : ' . $obj["group_number"] . '</div>

    		</div>
    		 <div class="row-fluid center-block margin-buffer">
    		<!-- Delay -->
    		<div class="span2 hasTooltip delay_selector_css offset2" title="' . JText::_("COM_MULTICACHE_CSS_LAYOUT_CSS_DELAY_DESC") . '">' . ucfirst(JText::_("COM_MULTICACHE_CSS_LAYOUT_CSS_DELAY_LABEL")) . ' : ' . $obj["delay"] . '</div>

    		<!-- Delay Type -->
    		<div class="span2 hasTooltip delaytype_selector_css" style="display:' . $delay_type_style . ';" title="' . JText::_("COM_MULTICACHE_CSS_LAYOUT_CSS_DELAYTYPE_DESC") . '">' . ucfirst(JText::_("COM_MULTICACHE_CSS_LAYOUT_CSS_DELAYTYPE_LABEL")) . ' : ' . $obj["delay_type"] . '</div>

    		<!-- CDN ALIAS -->
    		<div class="span2 hasTooltip cdnalias_selector_css" title="' . JText::_("COM_MULTICACHE_CSS_LAYOUT_CSS_CDN_ALIAS_DESC") . '">' . ucfirst(JText::_("COM_MULTICACHE_CSS_LAYOUT_CSS_CDN_ALIAS_LABEL")) . ' : ' . $obj["cdnAlias"] . '</div>

    		<!--IGNORE -->
    		<div class="span2 hasTooltip " title="' . JText::_("COM_MULTICACHE_CSS_LAYOUT_CSS_IGNORE_DESC") . '">' . ucfirst(JText::_("COM_MULTICACHE_CSS_LAYOUT_CSS_IGNORE_LABEL")) . ' : ' . $obj["ignore"] . '</div>
    		</div>
    		<div class="row-fluid center-block ' . $cdn_url_class . '">

    	<!-- CDN URL-->

    	<div id="cdn_urld_css' . $key . '" class="span12 hasTooltip cdnurl_selector_css center-block ' . $cdn_url_class . '" title="' . JText::_("COM_MULTICACHE_CSS_LAYOUT_CDN_URL_ENTRY_DESC") . '">' . ucfirst(JText::_("COM_MULTICACHE_CSS_LAYOUT_CSS_CDN_URL_ENTRY_LABEL")) . ' :
    	<input aria-invalid="false" name="cdn_url_css_' . $key . '" id="cdn_url_css_' . $key . '" value="' . $cdn_url_default . '" class="span8 " type="url">
    	</div>
</div>
    </div>';
        }
        $css_layout = $loadsection_reset_button . $css_layout;
        Return $css_layout;
    
    }

    protected function getSegments($obj)
    {

        $segments = array();
        foreach ($obj as $key => $segment)
        {
            $segments[] = $key;
        }
        Return $segments;
    
    }

    protected function addToolbar()
    {

        JFactory::getApplication()->input->set('hidemainmenu', true);
        
        $user = JFactory::getUser();
        $isNew = ($this->item->id == 0);
        if (isset($this->item->checked_out))
        {
            $checkedOut = ! ($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
        }
        else
        {
            $checkedOut = false;
        }
        $canDo = MulticacheHelper::getActions();
        
        JToolBarHelper::title(JText::_('COM_MULTICACHE_TITLE_MULTICACHE_CONFIG'), 'config.png');
        
        // If not checked out, can save the item.
        if (! $checkedOut && ($canDo->get('core.edit') || ($canDo->get('core.create'))))
        {
            
            JToolBarHelper::apply('config.apply', 'JTOOLBAR_APPLY');
            JToolBarHelper::save('config.save', 'JTOOLBAR_SAVE');
        }
        if (! $checkedOut && ($canDo->get('core.create')))
        {
            // JToolBarHelper::custom('config.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
        }
        // If an existing item, can save to a copy.
        if (! $isNew && $canDo->get('core.create'))
        {
            // JToolBarHelper::custom('config.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
        }
        if (empty($this->item->id))
        {
            JToolBarHelper::cancel('config.cancel', 'JTOOLBAR_CANCEL');
        }
        else
        {
            JToolBarHelper::cancel('config.cancel', 'JTOOLBAR_CLOSE');
        }
        if (! $checkedOut && ($canDo->get('core.edit') || ($canDo->get('core.create'))))
        {
            
            JToolBarHelper::custom('config.authenticate', 'apply', 'authenticate_f2.png', 'COM_MULTICACHE_AUTHENTICATE_GOOGLE_BUTTON', false);
            if ($this->item->js_switch)
            {
                JToolBarHelper::custom('config.scrapetemplate', 'apply', 'scrapetemplate_f2.png', 'COM_MULTICACHE_SCRAPE_TEMPLATE_BUTTON', false);
            }
            if ($this->item->css_switch)
            {
                JToolBarHelper::custom('config.scrapecsstemplate', 'apply', 'scrapecsstemplate_f2.png', 'COM_MULTICACHE_SCRAPE_CSS_TEMPLATE_BUTTON', false);
            }
            if (JDEBUG)
            {
                JToolBarHelper::custom('config.reset', 'apply btn-danger', 'reset_f2.png', 'COM_MULTICACHE_RESET_FACTORY_SETTINGS', false);
            }
            
            if (JFactory::getUser()->authorise('core.admin', 'com_multicache'))
            {
                JToolbarHelper::preferences('com_multicache');
            }
            
            JToolbarHelper::divider();
            $help_url = "//multicache.org/table/documentation/multicache-config/";
            JToolbarHelper::help('COM_MULTICACHE_VIEW_CONFIG_HELP', false, $help_url);
        }
    
    }

}