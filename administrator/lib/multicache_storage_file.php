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
/*
if (file_exists(dirname(__FILE__) . '/multicacheurlarray.php'))
{
    require_once dirname(__FILE__) . '/multicacheurlarray.php';
}
*/
class MulticacheStorageFile extends MulticacheStorage
{

    protected $_root;

   // protected static $_db = null;

   // protected static $_dbadmin = null;

   // protected $_persistent = false;

   // protected $_compress = 0;

  //  protected $_multicacheurlarray = null;

   // protected $_lz_factor = 0;

    protected $_config = null;

    public function __construct($options = array())
    {

        parent::__construct($options);
        
        if (null === $this->_config)
        {
            $this->_config = MulticacheFactory::getConfig();
        }
        /*
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
        */
        $this->_root = $options['cachebase'];
    
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
Return true;

    }

    public function _isSupported()
    {

        Return true;
    
    }
    public function lock($id, $group, $locktime)
    {
    	$returning = new stdClass;
    	$returning->locklooped = false;
    	$looptime  = $locktime * 10;
    	$path      = $this->_getFilePath($id, $group);
    	$_fileopen = @fopen($path, "r+b");
    	if ($_fileopen)
    	{
    		$data_lock = @flock($_fileopen, LOCK_EX);
    	}
    	else
    	{
    		$data_lock = false;
    	}
    	if ($data_lock === false)
    	{
    		$lock_counter = 0;
    		// Loop until you find that the lock has been released.
    		// That implies that data get from other thread has finished
    		while ($data_lock === false)
    		{
    			if ($lock_counter > $looptime)
    			{
    				$returning->locked     = false;
    				$returning->locklooped = true;
    				break;
    			}
    			usleep(100);
    			$data_lock = @flock($_fileopen, LOCK_EX);
    			$lock_counter++;
    		}
    	}
    	$returning->locked = $data_lock;
    	return $returning;
    }
  
    public function unlock($id, $group = null)
    {
    	$path      = $this->_getFilePath($id, $group);
    	$_fileopen = @fopen($path, "r+b");
    	if ($_fileopen)
    	{
    		$ret = @flock($_fileopen, LOCK_UN);
    		@fclose($_fileopen);
    	}
    	else
    	{
    		// Expect true if $_fileopen is false. Ref: http://issues.joomla.org/tracker/joomla-cms/2535
    		$ret = true;
    	}
    	return $ret;
    }
  

    protected function _getFilePath($id, $group, $user = 0, $subgroup = null)
    {

        $name = $this->_getCacheId($id, $group);//wer're not interested in cacheidb as cart mode does not pass through here
        
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

    public function get($id, $group, $checkTime = true)
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