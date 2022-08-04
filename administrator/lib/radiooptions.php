<?php

/*
 * @version 1.0.1.5
 * @package com_multicache
 * @copyright Copyright (C) Multicache.org 2015. All rights reserved.
 * @licenseEULA see LICENSE.txt - http://multicache.org/terms-conditions/end-user-license-agreement.html
 * @author Wayne DSouza <consulting@OnlineMarketingConsultants.in> - http://OnlineMarketingConsultants.in
 */
// No direct access
defined('JPATH_PLATFORM') or die();

JLoader::register('JFormFieldRadio', JPATH_LIBRARIES . '/joomla/form/fields/radio.php');

class RadioOptions extends JFormFieldRadio
{

    public static function getOptionObj($xml_obj)
    {

        $options = array();
        $count = $xml_obj->element->option->count();
        foreach ($xml_obj->element->children() as $key => $options)
        {
        }
        Return $options;
    
    }

}

?>