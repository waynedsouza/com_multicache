<?php
/*
 * @version 1.0.1.5
 * @package com_multicache
 * @copyright Copyright (C) Multicache.org 2015. All rights reserved.
 * @licenseEULA see LICENSE.txt - http://multicache.org/terms-conditions/end-user-license-agreement.html
 * @author Wayne DSouza <consulting@OnlineMarketingConsultants.in> - http://OnlineMarketingConsultants.in
 */
defined('JPATH_PLATFORM') or die();

// JLoader::register('JCacheWorkaround', JPATH_ROOT . '/administrator/components/com_multicache/lib/cacheworkaround.php');
// require chosen over jloader as it is twice as fast. does not check whether file exists.
require_once (JPATH_ROOT . '/administrator/components/com_multicache/lib/cacheworkaround.php');
jimport('joomla.application.component.helper');
JLog::addLogger(array(
    'text_file' => 'JCacheworkaroundRendering.php'
), JLog::ALL, array(
    'error'
));

class JCacheControllerPage extends JCacheController
{

    /**
     *
     * @var integer ID property for the cache page object.
     * @since 11.1
     */
    protected $_id;

    /**
     *
     * @var string Cache group
     * @since 11.1
     */
    protected $_group;

    /**
     *
     * @var object Cache lock test
     * @since 11.1
     */
    protected $_locktest = null;

    protected $_force_locking_off = false;

    protected $_precache_factor = null;

    protected $_force_precache_off = null;

    protected static $_cloop = null;

    /**
     * Get the cached page data
     *
     * @param string $id
     *        The cache data id
     * @param string $group
     *        The cache data group
     *        
     * @return boolean True if the cache is hit (false else)
     *        
     * @since 11.1
     */
    public function get($id = false, $group = 'page')
    {

        /*
         * force locking off :cleaner variant would be to set in JCache;
         * however JCache cannot be overwritten unless JLoader introduced in index before JApplicationCMS which first calls
         * JFactory::getCache() through JComponentHelper; If you're reading this you can set the construct of Jcache to locking => false.
         * Some of the documentation to support locking off can be found on the memcached forums in particular that memcached is not atomic
         * in its state and can never assure you of a lock. The Joomla locking variant tends to malfunction in case of high load on
         * high page access sites. Particularly the wait threads for lock to unlock when there is no lock in the first place.
         */
        $config = JFactory::getConfig();
        /*
         * $jmulticachehash = $config->get('jmulticache_hash');
         * $cookieName = isset($jmulticachehash) ? '_jmulticache_' . $jmulticachehash : null;
         */
        $this->_force_locking_off = $config->get('force_locking_off', true);
        if (! empty($this->options[locking]) && $this->_force_locking_off)
        {
            $this->options[locking] = false;
        }
        $this->_precache_factor = $config->get('precache_factor', 6);
        
        $this->_force_precache_off = $config->get('multicacheprecacheswitch', null);
        
        // If an id is not given, generate it from the request
        if ($id == false)
        {
            $id = $this->_makeId();
        }
        $user = JFactory::getUser();
        // If the etag matches the page id ... set a no change header and exit : utilize browser cache
        // June 16 2015 added user get guest so that we get a fresh page on login and dont stay lgged out
        // we're not willing to return false as id and group are set at the end
        if (! headers_sent() && isset($_SERVER['HTTP_IF_NONE_MATCH']) /*&& isset($cookieName) && isset($_COOKIE[$cookieName]) */&& $user->get('guest'))
        {
            
            $etag = stripslashes($_SERVER['HTTP_IF_NONE_MATCH']);
            if ($etag == $id)
            {
                $browserCache = isset($this->options['browsercache']) ? $this->options['browsercache'] : false;
                if ($browserCache)
                {
                    $this->_noChange();
                }
            }
        }
        
        // We got a cache hit... set the etag header and echo the page data
        $data = $this->cache->get($id, $group);
        if ($this->options[storage] == 'fastcache' && ! $this->options[locking])
        {
        }
        else
        {
            
            $this->_locktest = new stdClass();
            $this->_locktest->locked = null;
            $this->_locktest->locklooped = null;
            
            if ($data === false)
            {
                
                $this->_locktest = $this->cache->lock($id, $group);
                if ($this->_locktest->locked == true && $this->_locktest->locklooped == true)
                {
                    $data = $this->cache->get($id, $group);
                }
            }
        }
        if ($data !== false)
        {
            $data = unserialize(trim($data));
            self::$_cloop = array(
                'id' => $id,
                'group' => $group,
                'locked' => $this->_locktest->locked,
                'cache_obj' => $this->cache
            );
            $data = JCacheWorkaround::getWorkarounds($data, array(
                'precache_factor' => $this->_precache_factor,
                'force_precache_off' => $this->_force_precache_off,
                'etag' => $id
            ));
            
            $this->_setEtag($id);
            if ($this->_locktest->locked == true)
            {
                $this->cache->unlock($id, $group);
            }
            return $data;
        }
        elseif (null !== JFactory::getApplication()->input->get('cachehit', null))
        {
            
            JLog::addLogger(array(
                'text_file' => 'multicache_simulation_cachepageworkaround.errors.php'
            ), JLog::ALL, array(
                'multicache_simulation_cachepageworkaround'
            ));
            $sim_id = JFactory::getApplication()->input->get('multicachesimulation', null);
            $emessage = "COM_MULTICACHE_SIMCONTROL_CLASS_JCACHECONTROLLERPAGE_EXTENDED_PAGENOTINCACHE";
            JLog::add(JText::_($emessage) . '	' . $sim_id, JLog::ERROR);
        }
        
        // Set id and group placeholders
        $this->_id = $id;
        $this->_group = $group;
        
        return false;
    
    }

    /**
     * Stop the cache buffer and store the cached data
     *
     * @param mixed $data
     *        The data to store
     * @param string $id
     *        The cache data id
     * @param string $group
     *        The cache data group
     * @param boolean $wrkarounds
     *        True to use wrkarounds
     *        
     * @return boolean True if cache stored
     *        
     * @since 11.1
     */
    public function store($data, $id, $group = null, $wrkarounds = true)
    {
        //defining interlock
        if(defined('MULTICACHEPAGESTORELOCK'))
        {
        	return false;
        }
    	if(defined('MULTICACHEEXTSTORE') && defined('MULTICACHEEXTSTORE') == true)
    	{
    	!defined('MULTICACHEPAGESTORELOCK') && define('MULTICACHEPAGESTORELOCK' , true);	
    	}
        // Get page data from the application object
        if (empty($data))
        {
            $data = JFactory::getApplication()->getBody();
        }
        //hack begin April 04 2016
        //Joomla seems to have suddenly decided to store in gzip 
        //but they have not taken care of form tokens
        /*
        if(defined('MULTICACHEJOOMLAVERSION') && MULTICACHEJOOMLAVERSION >= 3.5)
        {
        $temp = @gzdecode ($data);
        $data = false !==$temp ? $temp : $data;
        }
        */
        //hack end
        // Get id and group and reset the placeholders
        if (empty($id))
        {
            $id = $this->_id;
        }
        
        if (empty($group))
        {
            $group = $this->_group;
        }
        
        // Only attempt to store if page data exists
        
        if ($data)
        {
            if ($wrkarounds)
            {
                $data = JCacheWorkaround::setWorkarounds($data, array(
                    'nopathway' => 1,
                    'nohead' => 1,
                    'nomodules' => 1,
                    'headers' => true,
                    'makegzip' => 1,
                    'precache_factor' => isset($this->_precache_factor) ? $this->_precache_factor : JFactory::getConfig()->get('precache_factor', 6),
                    'id' => $id
                ));
            }
            
            if (isset($this->_locktest->locked) && $this->_locktest->locked == false)
            {
                $this->_locktest = $this->cache->lock($id, $group);
            }
            
            $sucess = $this->cache->store(serialize($data), $id, $group);
            
            if (isset($this->_locktest->locked) && $this->_locktest->locked == true)
            {
                $this->cache->unlock($id, $group);
            }
            
            return $sucess;
        }
        return false;
    
    }

    /**
     * Generate a page cache id
     *
     * @return string MD5 Hash : page cache id
     *        
     * @since 11.1
     *        hash ... perhaps hashed with a serialized request
     */
    protected function _makeId()
    {

        return JCache::makeId();
    
    }

    /**
     * There is no change in page data so send an
     * unmodified header and die gracefully
     *
     * @return void
     *
     * @since 11.1
     */
    protected function _noChange()
    {

        $app = JFactory::getApplication();
        
        // Send not modified header and exit gracefully
        header('HTTP/1.x 304 Not Modified', true);
        if (defined('MULTICACHE_STARTTIME_'))
        {
            $emessage = "304 Met in  JCacheWorkarounds";
            $time = microtime(true) - MULTICACHE_STARTTIME_;
            $emessage .= " Page loaded in $time seconds";
            $emessage .= "  " . JURI::getInstance()->toString() . "  " . $_SERVER['REQUEST_METHOD'];
            $emessage .= $_SERVER['REQUEST_METHOD'] == 'POST' ? "  " . var_export(array(
                "request" => $_REQUEST,
                "post" => $_POST
            ), true) : "";
            JLog::add($emessage, JLog::NOTICE);
        }
        $app->close();
    
    }

    /**
     * Set the ETag header in the response
     *
     * @param string $etag
     *        The entity tag (etag) to set
     *        
     * @return void
     *
     * @since 11.1
     */
    protected function _setEtag($etag)
    {

        JFactory::getApplication()->setHeader('ETag', $etag, true);
    
    }

    public static function closeLoop()
    {

        if (! isset(self::$_cloop))
        {
            Return false;
        }
        
        if (self::$_cloop['locked'] == true)
        {
            self::$_cloop['cache_obj']->unlock(self::$_cloop['id'], self::$_cloop['group']);
        }
    
    }

}