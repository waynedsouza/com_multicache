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
require_once dirname(__FILE__) . '/multicache.php';

class MulticacheAdvCacheController
{

    public $cache;

    public $options;

    public function __construct($options)
    {

        $this->cache = new Multicache($options);
        $this->options = & $this->cache->_options;
        // Overwrite default options with given options
        foreach ($options as $option => $value)
        {
            if (isset($options[$option]))
            {
                $this->options[$option] = $options[$option];
            }
        }
    
    }

    public static function getInstance($type = 'page', $options = array())
    {

        $type = strtolower(preg_replace('/[^A-Z0-9_\.-]/i', '', $type));
        //augmented : we cannot have to MulticacheController Classes
        $class = $type == '' ?  'MulticacheAdvCacheController' :   'Multicache' . ucfirst($type) . 'Controller';
        if (! class_exists($class))
        {
            // Search for the class file in the JCache include paths.
            $path = dirname(__FILE__) . '/multicache_' . strtolower($type) . 'cache_controller.php';
            if (file_exists($path))
            {
                include_once $path;
            }
            else
            {
                // throw new RuntimeException('Unable to load Cache Controller: ' . $type, 500);
            }
        }
        return new $class($options);
    
    }

}