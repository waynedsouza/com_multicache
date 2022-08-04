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
require_once dirname(__FILE__) . '/multicache_controller.php';
require_once dirname(__FILE__) . '/multicache_storage.php';

class Multicache
{

    protected $_id;

    protected $_group;

    protected $_precache_factor = null;

    protected static $_cloop = null;

    public $options;

    public static $_handler = array();

    /*
     * NOTE: Cache handler and storage are used interchangebly. As there is only
     * one handler fastcache which handles both file & fastcache, for all practival purposes
     * the term storage refers specifically to storage. Config-> cache handler comes into
     * being in the fastcache method. *
     */
    public function __construct($options = null)
    {

        $conf = MulticacheFactory::getConfig();
        
        $this->_options = array(
            'cachebase' => $conf->getC('cachebase'),
            'lifetime' => (int) $conf->getC('cachetime'),
            'language' => $conf->getC('language', 'en-GB'),
            'handler' => $conf->getC('cache_handler', 'fastcache'), // file /fastcache
            'browsercache' => $conf->getC('browsercache', 1),
            /*'storage' => (null !== $conf->getC('storage'))? $conf->getC('storage'):$conf->getC('cache_handler', 'fastcache') , // only fastcache*/
        	'storage' => (null !== $conf->getC('storage'))? $conf->getC('storage'):$conf->getC('cache_handler', 'fastcache') , //  fastcache and file
            'defaultgroup' => 'default',
            'locking' => false,
            'locktime' => 15,
            'checkTime' => true,
            'caching' => ($conf->getC('caching') >= 1) ? true : false
        );
        // Overwrite default options with given options
        if (isset($options))
        {
            foreach ($options as $option => $value)
            {
                if (isset($options[$option]) && $options[$option] !== '')
                {
                    $this->_options[$option] = $options[$option];
                }
            }
        }
        if (empty($this->_options['storage']))
        {
            $this->_options['caching'] = false;
        }
    
    }

    public static function getInstance($type = 'page', $options = array())
    {

        return MulticacheAdvCacheController::getInstance($type, $options);
    
    }

    public function get($id, $group = null)
    {
        // Get the default group
        $group = ($group) ? $group : $this->_options['defaultgroup'];
        // Get the storage
        $handler = $this->_getStorage();
        if (! ($handler instanceof Exception) && $this->_options['caching'])
        {
            return $handler->get($id, $group, $this->_options['checkTime']);
        }
        return false;
    
    }

    public function store($data, $id, $group = null)
    {
        // Get the default group
        $group = ($group) ? $group : $this->_options['defaultgroup'];
        // Get the storage and store the cached data
        $handler = $this->_getStorage();
        if (! ($handler instanceof Exception) && $this->_options['caching'])
        {
            $handler->_lifetime = $this->_options['lifetime'];
            return $handler->store($id, $group, $data);
        }
        return false;
    
    }

    public function lock($id, $group = null, $locktime = null)
    {

        $returning = new stdClass();
        $returning->locklooped = false;
        $group = ($group) ? $group : $this->_options['defaultgroup'];
        $locktime = ($locktime) ? $locktime : $this->_options['locktime'];
        $handler = $this->_getStorage();
        // restawhile
        if (! ($handler instanceof Exception) && $this->_options['locking'] == true && $this->_options['caching'] == true)
        {
            $locked = $handler->lock($id, $group, $locktime);
            if ($locked !== false)
            {
                return $locked;
            }
        }
        // Fallback
        $curentlifetime = $this->_options['lifetime'];
        // Set lifetime to locktime for storing in children
        $this->_options['lifetime'] = $locktime;
        $looptime = $locktime * 10;
        $id2 = $id . '_lock';
        if ($this->_options['locking'] == true && $this->_options['caching'] == true)
        {
            $data_lock = $this->get($id2, $group);
        }
        else
        {
            $data_lock = false;
            $returning->locked = false;
        }
        if ($data_lock !== false)
        {
            $lock_counter = 0;
            // Loop until you find that the lock has been released.
            // That implies that data get from other thread has finished
            while ($data_lock !== false)
            {
                if ($lock_counter > $looptime)
                {
                    $returning->locked = false;
                    $returning->locklooped = true;
                    break;
                }
                usleep(100);
                $data_lock = $this->get($id2, $group);
                $lock_counter ++;
            }
        }
        if ($this->_options['locking'] == true && $this->_options['caching'] == true)
        {
            $returning->locked = $this->store(1, $id2, $group);
        }
        // Revert lifetime to previous one
        $this->_options['lifetime'] = $curentlifetime;
        return $returning;
    
    }

    public function unlock($id, $group = null)
    {

        $unlock = false;
        // Get the default group
        $group = ($group) ? $group : $this->_options['defaultgroup'];
        // Allow handlers to perform unlocking on their own
        $handler = $this->_getStorage();
        if (! ($handler instanceof Exception) && $this->_options['caching'])
        {
            $unlocked = $handler->unlock($id, $group);
            if ($unlocked !== false)
            {
                return $unlocked;
            }
        }
        // Fallback
        if ($this->_options['caching'])
        {
            $unlock = $this->remove($id . '_lock', $group);
        }
        return $unlock;
    
    }

    public function &_getStorage()
    {

        $hash = md5(serialize($this->_options));
        if (isset(self::$_handler[$hash]))
        {
            return self::$_handler[$hash];
        }
        self::$_handler[$hash] = MulticacheStorage::getInstance($this->_options['storage'], $this->_options);
        return self::$_handler[$hash];
    
    }

    public function setCaching($enabled)
    {

        $this->_options['caching'] = $enabled;
    
    }

    public function getCaching()
    {

        return $this->_options['caching'];
    
    }

    public function getAll()
    {
        // Get the storage
        $handler = $this->_getStorage();
        if (! ($handler instanceof Exception) && $this->_options['caching'])
        {
            return $handler->getAll();
        }
        return false;
    
    }

    public function remove($id, $group = null, $user = 0, $subgroup = null)
    {
        // Get the default group
        $group = ($group) ? $group : $this->_options['defaultgroup'];
        // Get the storage
        $handler = $this->_getStorage();
        if (! ($handler instanceof Exception))
        {
            return $handler->remove($id, $group, $user, $subgroup);
        }
        return false;
    
    }

    public function clean($group = null, $mode = 'group')
    {
        // Get the default group
        $group = ($group) ? $group : $this->_options['defaultgroup'];
        // Get the storage handler
        $handler = $this->_getStorage();
        if (! ($handler instanceof Exception))
        {
            return $handler->clean($group, $mode);
        }
        return false;
    
    }

    public function gc()
    {
        // Get the storage handler
        $handler = $this->_getStorage();
        if (! ($handler instanceof Exception))
        {
            return $handler->gc();
        }
        return false;
    
    }

}