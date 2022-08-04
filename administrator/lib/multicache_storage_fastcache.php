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

require_once dirname(__FILE__) . '/multicache_storage.php';
if (file_exists(dirname(__FILE__) . '/multicacheurlarray.php'))
{
    require_once dirname(__FILE__) . '/multicacheurlarray.php';
}

class MulticacheStorageFastcache extends MulticacheStorage
{

    protected $_root;

    protected static $_db = null;

    protected static $_dbadmin = null;

    protected $_persistent = false;

    protected $_compress = 0;

    protected $_multicacheurlarray = null;

    protected $_lz_factor = 0;

    protected $_config = null;

    public function __construct($options = array())
    {

        parent::__construct($options);
        
        if (null === $this->_config)
        {
            $this->_config = MulticacheFactory::getConfig();
        }
        if (! defined('MEMCACHE_COMPRESSED'))
        {
            define('MEMCACHE_COMPRESSED', 2);
        }
        if ($this->_multicacheurlarray === null && class_exists('MulticacheUrlArray'))
        {
            
            $this->_multicacheurlarray = MulticacheUrlArray::$urls; // moving to static property to test time for load
        }
        if (! defined('FASTCACHEVAR_CACHEHANDLER_XTD'))
        {
            define('FASTCACHEVAR_CACHEHANDLER_XTD', $this->_config->getC('cache_handler', 'fastcache') === 'fastcache' ? true : false);
        }
        
        if (self::$_db === null)
        {
            $this->getConnection();
        }
        $this->_root = $options['cachebase'];
    
    }

    protected function getConnection()
    {

        if (! (extension_loaded('memcache') && class_exists('Memcache')) || ! FASTCACHEVAR_CACHEHANDLER_XTD)
        {
            if (! defined('MULTICACHE_MEMCACHE_READY_TESTED_XTD'))
            {
                define('MULTICACHE_MEMCACHE_READY_TESTED_XTD', false);
            }
            return false;
        }
        
        // $this->_persistent = $this->_config->get('multicache_persist', true);
        $this->_persistent = $this->_config->getC('multicache_persist', true);
        // $this->_compress = $this->_config->get('multicache_compress', false) == false ? 0 : MEMCACHE_COMPRESSED;
        $this->_compress = $this->_config->getC('multicache_compress', false) == false ? 0 : MEMCACHE_COMPRESSED;
        
        // $this->_lz_factor = $this->_config->get('gzip_factor', false);
        $this->_lz_factor = $this->_config->getC('gzip_factor', false);
        
        $server = array();
        // $server['host'] = $this->_config->get('multicache_server_host', 'localhost');
        $server['host'] = $this->_config->getC('multicache_server_host', 'localhost');
        // $server['port'] = $this->_config->get('multicache_server_port', 11211);
        $server['port'] = $this->_config->getC('multicache_server_port', 11211);
        
        self::$_db = new Memcache();
        self::$_db->addServer($server['host'], $server['port'], $this->_persistent);
        // compression on fastlz - default is 1.3 or 23% herein 20% and thres @ 2000:: in simulations 0 gzip factor is joomla default render
        if ($this->_lz_factor)
        {
            self::$_db->setCompressThreshold(2000, $this->_lz_factor);
        }
        
        $memcachetest = @self::$_db->connect($server['host'], $server['port']);
        
        if ($memcachetest == false)
        {
            // throw new RuntimeException('Could not connect to memcache server', 404);
            if (! defined('MULTICACHE_MEMCACHE_READY_TESTED_XTD'))
            {
                define('MULTICACHE_MEMCACHE_READY_TESTED_XTD', false);
            }
            return;
        }
        if (! defined('MULTICACHE_MEMCACHE_READY_TESTED_XTD'))
        {
            define('MULTICACHE_MEMCACHE_READY_TESTED_XTD', true);
        }
        
        return;
    
    }

    public function get($id, $group, $checkTime = true)
    {
        
        // $modemem = $this->_config->get('multicachedistribution', 0);
        $modemem = $this->_config->getC('multicachedistribution', 0);
        $c_h = FASTCACHEVAR_CACHEHANDLER_XTD && MULTICACHE_MEMCACHE_READY_TESTED_XTD;
        
        if ($modemem == 3)
        :
            // hammered pagespeed allows all variants for a particular url. strtolower
            if ($c_h && ($group != "page" || isset($this->_multicacheurlarray[strtolower(MulticacheUri::current())])))
            :
                // $cache_id = $this->_getCacheId($id, $group, $user, $subgroup);
                $cache_id = $this->_getCacheId($id, $group, $user);
                $back = self::$_db->get($cache_id);
                return $back;
            
            
            else
            :
                $back = $this->getfilecache($id, $group, $checkTime);
                Return $back;
            endif;
        
        
        elseif ($modemem == 2)
        :
            if ($c_h && $this->_multicacheurlarray[MulticacheUri::current()])
            :
                // $cache_id = $this->_getCacheId($id, $group, $user, $subgroup);
                $cache_id = $this->_getCacheId($id, $group, $user);
                $back = self::$_db->get($cache_id);
                return $back;
            
            
            else
            :
                $back = $this->getfilecache($id, $group, $checkTime);
                Return $back;
            endif;
        
        
        elseif ($modemem == 1)
        :
            Return false;
        
        
        // not handling admin here
        /*
         * if ($c_h && ! is_admin() && ($group != "page" || FASTCACHEVARMULTICACHEUAURLISSET))
         * :
         * // $cache_id = $this->_getCacheId($id, $group, $user, $subgroup);
         * $cache_id = $this->_getCacheId($id, $group, $user);
         * $back = self::$_db->get($cache_id);
         * return $back;
         *
         *
         * else
         * :
         * $back = $this->getfilecache($id, $group, $checkTime, $user, $subgroup);
         * Return $back;
         * endif;
         */
        
        else
        :
            Return false;
                // were not handling carts here
        /*
         * if ($c_h && ($group != "page" || FASTCACHEVARMULTICACHEUAURLISSET))
         * :
         * if (FASTCACHEVARMULTICACHESTORAGETEMP)
         * :
         * // $cache_id = $this->_getCacheIdb($id, $group, $user, $subgroup);
         * $cache_id = $this->_getCacheIdb($id, $group, $user);
         *
         *
         * else
         * :
         * // $cache_id = $this->_getCacheId($id, $group, $user, $subgroup);
         * $cache_id = $this->_getCacheId($id, $group, $user);
         * endif;
         *
         * $back = self::$_db->get($cache_id);
         * return $back;
         *
         *
         * else
         * :
         * $back = $this->getfilecache($id, $group, $checkTime, $user, $subgroup);
         * Return $back;
         * endif;
         */
        endif;
        //
        
        // $cache_id = $this->_getCacheId($id, $group, $user, $subgroup);
        $cache_id = $this->_getCacheId($id, $group, $user);
        $back = self::$_db->get($cache_id);
        return $back;
    
    }

    public function store($id, $group, $data)
    {
        
        // $modemem = $this->_config->get('multicachedistribution', 0);
        $modemem = $this->_config->getC('multicachedistribution', 0);
        $c_h = FASTCACHEVAR_CACHEHANDLER_XTD && MULTICACHE_MEMCACHE_READY_TESTED_XTD;
        if ($modemem == 3)
        :
            if ($c_h && ($group != "page" || isset($this->_multicacheurlarray[strtolower(MulticacheUri::current())])))
            :
            
            
            else
            :
                $status = $this->putinfilecache($id, $group, $data);
                Return $status;
            endif;
        
        
        elseif ($modemem == 2)
        :
            if ($c_h && $this->_multicacheurlarray[MulticacheUri::current()])
            :
            
            
            else
            :
                $status = $this->putinfilecache($id, $group, $data);
                Return $status;
            endif;
        
        
        elseif ($modemem == 1)
        :
            Return false;
        
        
        // not handling admin here
        /*
         * if ($c_h && ! is_admin() && ($group != "page" || FASTCACHEVARMULTICACHEUAURLISSET))
         * :
         *
         *
         * else
         * :
         * $this->_multicacheurlarray[MulticacheUri::current()]e($id, $group, $data, $user, $subgroup);
         * Return $status;
         * endif;
         */
        
        else
        :
            Return false;
                // not handling cart here
        /*
         * if ($c_h && $group != "page" || FASTCACHEVARMULTICACHEUAURLISSET)
         * :
         *
         *
         * else
         * :
         * $status = $this->putinfilecache($id, $group, $data, $user, $subgroup);
         * Return $status;
         * endif;
         */
        endif;
        /*
         * if ($modemem == 0 && FASTCACHEVARMULTICACHESTORAGETEMP)
         * {
         * // $cache_id = $this->_getCacheIdb($id, $group, $user, $subgroup);
         * $cache_id = $this->_getCacheIdb($id, $group, $user);
         * }
         * else
         * {
         */
        // $cache_id = $this->_getCacheId($id, $group, $user, $subgroup);
        $cache_id = $this->_getCacheId($id, $group, $user);
        /*
         * }
         */
        
        // $lifetime = (int) $this->_config->get('cachetime', 15);
        $lifetime = (int) $this->_config->getC('cachetime', 1440);
        if ($this->_lifetime == $lifetime)
        {
            $this->_lifetime = $lifetime * 60;
        }
        // $this->_compress = $this->_config->get('multicache_compress', false) == false ? 0 : MEMCACHE_COMPRESSED;
        
        $this->_compress = $this->_config->getC('multicache_compress', false) == false ? 0 : MEMCACHE_COMPRESSED;
        
        $result = self::$_db->add($cache_id, $data, $this->_compress, $this->_lifetime);
        
        if (! $result)
        {
            if ($this->_multicacheurlarray['fastcache-debug'])
            {
                // $errormessage = sprintf(__('LIB_FASTCACHE_COM_MULTICACHE_ADDFAILED_TRYING_RESET_STORE', 'multicache-plugin'), $id, $group, $cache_id, $this->_lifetime);
                // ////$this->loaderrorlogger($errormessage);
            }
            if (! self::$_db->replace($cache_id, $data, $this->_compress, $this->_lifetime))
            {
                $result = self::$_db->set($cache_id, $data, $this->_compress, $this->_lifetime);
                if ($this->_multicacheurlarray['fastcache-debug'] && ! $result)
                {
                    
                    // $errormessage = sprintf(__('LIB_FASTCACHE_COM_MULTICACHE_RESETANDSTORE_FAILEDASWELL', 'multicache-plugin'), $id, $group, $cache_id);
                    // ////$this->loaderrorlogger($errormessage);
                }
            }
        }
        
        return true;
    
    }

    /**
     * Test to see if the cache storage is available.
     *
     * @return boolean True on success, false otherwise.
     *        
     * @since 12.1
     */
    public static function isSupported()
    {

        if ((extension_loaded('memcache') && class_exists('Memcache')) != true)
        {
            return false;
        }
        
        $config = MulticacheFactory::getConfig();
        $host = $config->getC('multicache_server_host', 'localhost');
        $port = $config->getC('multicache_server_port', 11211);
        
        $memcache = new Memcache();
        $memcachetest = @$memcache->connect($host, $port);
        
        if (! $memcachetest)
        {
            return false;
        }
        else
        {
            return true;
        }
    
    }

    public function _isSupported()
    {

        Return self::isSupported();
    
    }

    public function lock($id, $group, $locktime)
    {

        if ($this->_config->getC('force_locking_off', true) /*$this->_config->get('force_locking_off',true)*/ )
        {
            Return false;
        }
        // Return false;
        $returning = new stdClass();
        $returning->locklooped = false;
        
        $looptime = $locktime * 10;
        
        // $cache_id = $this->_getCacheId($id, $group, $user, $subgroup);
        $cache_id = $this->_getCacheId($id, $group, $user);
        
        $data_lock = self::$_db->add($cache_id . '_lock', 1, false, $locktime);
        
        if ($data_lock === false)
        {
            
            $lock_counter = 0;
            
            // Loop until you find that the lock has been released.
            // That implies that data get from other thread has finished
            while ($data_lock === false)
            {
                
                if ($lock_counter > $looptime)
                {
                    $returning->locked = false;
                    $returning->locklooped = true;
                    break;
                }
                
                usleep(100);
                $data_lock = self::$_db->add($cache_id . '_lock', 1, false, $locktime);
                $lock_counter ++;
            }
        }
        $returning->locked = $data_lock;
        
        return $returning;
    
    }

    public function unlock($id, $group = null)
    {
        // Return false;
        // $cache_id = $this->_getCacheId($id, $group, $user, $subgroup) . '_lock';
        $cache_id = $this->_getCacheId($id, $group, $user) . '_lock';
        
        return self::$_db->delete($cache_id);
    
    }

    protected function lockindex()
    {

        Return false;
    
    }

    protected function unlockindex()
    {

        Return false;
    
    }

    protected function putinfilecache($id, $group, $data)
    {

        $written = false;
        
        $path = $this->_getFilePath($id, $group);
        if (file_exists($path))
        {
            
            Return true;
        }
        $die = '<?php die("Access Denied"); ?>#x#';
        
        $data = $die . $data;
        
        $_fileopen = @fopen($path, "wb");
        
        if ($_fileopen)
        {
            $len = strlen($data);
            @fwrite($_fileopen, $data, $len);
            $written = true;
        }
        
        if ($written && ($data == file_get_contents($path)))
        {
            return true;
        }
        else
        {
            return false;
        }
    
    }

    protected function _getFilePath($id, $group, $user = 0, $subgroup = null)
    {

        $name = $this->_getCacheId($id, $group);
        
        $dir = $this->_root . '/' . $group;
        
        // If the folder doesn't exist try to create it
        if (! is_dir($dir))
        {
            
            // Make sure the index file is there
            $indexFile = $dir . '/index.html';
            @mkdir($dir) && file_put_contents($indexFile, '<!DOCTYPE html><title></title>');
        }
        
        // Make sure the folder exists
        if (! is_dir($dir))
        {
            return false;
        }
        return $dir . '/' . $name . '.php';
    
    }

    protected function getfilecache($id, $group, $checkTime = true)
    {

        $data = false;
        
        // $path = $this->_getFilePath($id, $group, $user, $subgroup);
        $path = $this->_getFilePath($id, $group);
        
        if ($checkTime == false || ($checkTime == true && $this->_checkExpire($id, $group) === true))
        {
            if (file_exists($path))
            {
                $data = file_get_contents($path);
                if ($data)
                {
                    // Remove the initial die() statement
                    $data = str_replace('<?php die("Access Denied"); ?>#x#', '', $data);
                }
            }
            
            return $data;
        }
        else
        {
            return false;
        }
    
    }

    protected function _checkExpire($id, $group)
    {

        $path = $this->_getFilePath($id, $group);
        
        // Check prune period
        if (file_exists($path))
        {
            $time = @filemtime($path);
            if (($time + $this->_lifetime) < $this->_now || empty($time))
            {
                @unlink($path);
                return false;
            }
            return true;
        }
        return false;
    
    }

    protected function loaderrorlogger($emessage = null)
    {

        error_log($emessage);
    
    }

}