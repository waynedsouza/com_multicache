<?php

/*
 * @version 1.0.0.6
 * @package com_multicache
 * @copyright Copyright (C) Multicache.org 2015. All rights reserved.
 * @licenseEULA see LICENSE.txt - http://multicache.org/terms-conditions/end-user-license-agreement.html
 * @author Wayne DSouza <consulting@OnlineMarketingConsultants.in> - http://OnlineMarketingConsultants.in
 */
// No direct access
defined('_JEXEC') or die();

class MulticacheStorage
{

    protected $rawname;

    public $_now;

    public $_lifetime;

    public $_locking;

    public $_language;

    public $_application;

    public $_hash;

    public function __construct($options = array())
    {

        $config = MulticacheFactory::getConfig();
        $this->_hash = md5($config->getC('secret'));
        $this->_application = (isset($options['application'])) ? $options['application'] : null;
        $this->_language = (isset($options['language'])) ? $options['language'] : 'en-GB';
        $this->_locking = (isset($options['locking'])) ? $options['locking'] : false;
        $this->_lifetime = (isset($options['lifetime'])) ? $options['lifetime'] * 60 : $config->getC('cachetime') * 60;
        $this->_now = (isset($options['now'])) ? $options['now'] : time();
        
        if (empty($this->_lifetime))
        {
            $this->_threshold = $this->_now - 60;
            $this->_lifetime = 60;
        }
        else
        {
            $this->_threshold = $this->_now - $this->_lifetime;
        }
    
    }

    public static function getInstance($handler = null, $options = array())
    {

        static $now = null;
        
        if (! isset($handler))
        {
            $conf = MulticacheFactory::getConfig();
            $handler = $conf->getC('storage');
            if (empty($handler))
            {
                // throw new UnexpectedValueException('Cache Storage Handler not set.');
            }
        }
        if (is_null($now))
        {
            $now = time();
        }
        $options['now'] = $now;
        $handler = strtolower(preg_replace('/[^A-Z0-9_\.-]/i', '', $handler));
        $class = 'MulticacheStorage' . ucfirst($handler);
        if (! class_exists($class))
        {
            $path = dirname(__FILE__) . '/multicache_storage_' . strtolower($handler) . '.php';
            if (file_exists($path))
            {
                include_once $path;
            }
            else
            {
                // throw new RuntimeException(sprintf('Unable to load Cache Storage: %s', $handler));
            }
        }
        return new $class($options);
    
    }

    public function getAll()
    {

        if (! class_exists('MulticacheStorageHelper', false))
        {
            include_once dirname(__FILE__) . '/multicachestoragehelper.php';
        }
        return;
    
    }

    protected function _getCacheId($id, $group, $user = 0, $subgroup = null)
    {

        $name = md5($this->_application . '-' . $id . '-' . $this->_language);
        $this->rawname = $this->_hash . '-' . $name;
        return $this->_hash . '-cache-' . $group . '-' . $name;
    
    }
    
    // temporary placement
    /*
     * protected function _getCacheIdb($id, $group, $user = 0, $subgroup = null)
     * {
     *
     * $name = md5($this->_application . '-' . $id . '-' . $user . '-' . $subgroup . '-' . $this->_language);
     * $this->rawname = $this->_hash . '-' . $name;
     * $cache_id = $cache_id = $this->_hash . '-cache-' . $group . '-' . $name;
     * return $cache_id;
     *
     * }
     */
}