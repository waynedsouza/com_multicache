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
// require_once dirname(__FILE__) . '/multicache_buffers.php';
class MulticachePageController extends MulticacheAdvCacheController
{

    protected $_id;

    protected $_group;

    protected $_locktest = null;

    protected $_force_locking_off = false;

    protected $_precache_factor = null;

    protected $_force_precache_off = null;

    protected static $_cloop = null;

    public $options;

    public function get($id = false, $group = 'page', $check_buffers = true)
    {

        if (empty($id))
        {
            Return false;
        }
        /*
         * force locking off :cleaner variant would be to set in JCache;
         * however JCache cannot be overwritten unless JLoader introduced in index before JApplicationCMS which first calls
         * JFactory::getCache() through JComponentHelper; If you're reading this you can set the construct of Jcache to locking => false.
         * Some of the documentation to support locking off can be found on the memcached forums in particular that memcached is not atomic
         * in its state and can never assure you of a lock. The Joomla locking variant tends to malfunction in case of high load on
         * high page access sites. Particularly the wait threads for lock to unlock when there is no lock in the first place.
         */
        $config = MulticacheFactory::getConfig();
        /*
         * $jmulticachehash = $config->getC('jmulticache_hash');
         * $cookieName = isset($jmulticachehash) ? '_jmulticache_' . $jmulticachehash : null;
         */
        $this->_force_locking_off = $config->getC('force_locking_off', true);
        $cachehit = isset($_REQUEST["cachehit"]) ? preg_replace('~[^a-z]~', '', $_REQUEST["cachehit"]) : false;
        if (! empty($this->options["locking"]) && $this->_force_locking_off)
        {
            $this->options["locking"] = false;
        }
        $this->_precache_factor = $config->getC('precache_factor', 6);
        $this->_precache_factor = empty($this->_precache_factor) && strcmp($this->_precache_factor, '') === 0 ? 2 : (int) $this->_precache_factor;
        
        $this->_force_precache_off = $config->getC('multicacheprecacheswitch', null);
        
        // If the etag matches the page id ... set a no change header and exit : utilize browser cache
        // June 16 2015 added user get guest so that we get a fresh page on login and dont stay lgged out
        // we're not willing to return false as id and group are set at the end
        // user should be looked up in cookies ideally user logged in should not pass in here
        if (! headers_sent() && isset($_SERVER['HTTP_IF_NONE_MATCH']) /*&& isset($cookieName) && isset($_COOKIE[$cookieName])/*&& ! $user*/)
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
        
        if ($this->options["storage"] == 'fastcache' && ! $this->options["locking"])
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
            /*
             * if (isset($data['group']) && $data['group'] === 'feed')
             * {
             *
             * $return = $this->getFeed($data);
             * Return $return;
             * }
             */
            self::$_cloop = array(
                'id' => $id,
                'group' => $group,
                'locked' => $this->_locktest->locked,
                'cache_obj' => $this->cache
            );
            $data = $this->getBuffers($data, array(
                'precache_factor' => $this->_precache_factor,
                'force_precache_off' => $this->_force_precache_off,
                'etag' => $id,
                'obj' => $this
            ));
            
            $this->_setEtag($id);
            if ($this->_locktest->locked == true)
            {
                $this->cache->unlock($id, $group);
            }
            return $data;
        }
        elseif (defined('DEBUG_LOG') && $cachehit === 'true')
        {
            $sim_id = preg_replace('~[^a-zA-Z0-9]~', '', $_REQUEST['multicachesimulation']);
            error_log('Multicache: Page not in cache' . $sim_id . ' ' . MulticacheUri::getInstance()->toString());
        }
        
        // Set id and group placeholders
        $this->_id = $id;
        $this->_group = $group;
        
        return false;
    
    }

    protected function getBuffers($data, $options = array())
    {

        $app = MulticacheFactory::getApplication();
        $app->allowCache(true);
        // $document = JFactory::getDocument();
        $body = null;
        // $excluded_options = $app->input->get('option', null);//unreliable
        $precache_off = $options["force_precache_off"];
        
        // Set cached headers.
        if (isset($data['headers']) && $data['headers'])
        {
            foreach ($data['headers'] as $header)
            {
                $app->setHeader($header['name'], $header['value']);
            }
        }
        /*
         * NB : failed CSRF test
         */
        /*
         * $jmulticache_hash = $options['jmulticache_hash'];
         * $cookieName = $options['cookieName'];
         * // use this only in dead ends
         * if (isset($cookieName) && ! isset($_COOKIE[$cookieName]))
         * {
         * $name = $cookieName;
         * $value = MulticacheFactory::setAppToken(true);
         * $lifetime = $app->get('lifetime', 15) * 60;
         * setcookie($name, $value, time() + $lifetime, $app->get('cookie_path', '/'), $app->get('cookie_domain'), $app->isSSLConnection(), false);
         * }
         */
        // hack #1 - gzipbodyhack
        $no_precache = ! isset($data['multicache_meta']) || isset($data['multicache_meta']['forms']) ? true : false;
        $client_encodings_string = $app->acceptEncoding;
        $common_checks = ! headers_sent() && $app->get('gzip', true);
        $oetag = $options["etag"];
        if (! empty($data['body_gzip']) && stripos($client_encodings_string, 'gzip') !== false && $common_checks && ! isset($precache_off) && ! $no_precache)
        {
            $app->setBody($data['body_gzip']); //
            $app->setHeader('Content-Encoding', 'gzip');
            $app->setHeader('ETag', $oetag, true);
            
            // $app->setHeader('Content-Length', $data['GContent_Length']);
            echo $app->toString(false);
            $this->closeLoop();
            if (defined('MULTICACHE_STARTTIME_'))
            {
                $message = "Page loaded from advanced cache LOOP1 Precache";
                $time = defined('MULTICACHE_STARTTIME_') ? microtime(true) - MULTICACHE_STARTTIME_ : 'na';
                $message .= ' took -' . $time . ' seconds to render';
                $error_file = 'multicache_advancedcache_optimization.log';
                MulticacheFactory::loadErrorLogger($message, '', '', $error_file);
            }
            $app->close();
        }
        // The following code searches for a token in the cached page and replaces it with the
        // proper token.
        elseif (isset($data['body']))
        {
            if (isset($data['multicache_meta']['forms']))
            {
                $token = MulticacheFactory::setAppToken(true);
                // $search = '#<input type="hidden" name="[0-9a-f]{32}" value="1" />#';
                $search = '#<input\s+type="hidden"\s+name="([0-9a-f]{32})"\s+value="1"([^/>]*)/?>#';
                // $replacement = '<input type="hidden" name="' . $token . '" value="1" />';
                $replacement = '<input type="hidden" name="' . $token . '" value="1"\2/>';
                $data['body'] = preg_replace($search, $replacement, $data['body']);
            }
            
            $body = $data['body'];
            $client_encodings = array_map('trim', explode(',', $client_encodings_string));
            $supported = array(
                'x-gzip' => 'gz',
                'gzip' => 'gz',
                'deflate' => 'deflate'
            );
            $encodings = array_intersect($client_encodings, array_keys($supported));
            if (! empty($encodings) && $common_checks && (connection_status() === CONNECTION_NORMAL))
            {
                foreach ($encodings as $encoding)
                {
                    $gzdata = gzencode($data['body'], $options["precache_factor"], ($supported[$encoding] == 'gz') ? FORCE_GZIP : FORCE_DEFLATE);
                    if ($gzdata === false)
                    {
                        continue;
                    }
                    
                    $app->setHeader('Content-Encoding', $encoding);
                    $app->setBody($gzdata);
                    $app->setHeader('ETag', $oetag, true);
                    /*
                     * if(isset($data['precache_factor']) && $data['precache_factor'] === $options["precache_factor"])
                     * {
                     * $app->setHeader('Content-Length', $data['GContent_Length']);
                     * }
                     */
                    
                    echo $app->toString(false);
                    $this->closeLoop();
                    if (defined('MULTICACHE_STARTTIME_'))
                    {
                        $message = "Page loaded from advanced cache LOOP2 normal";
                        $time = defined('MULTICACHE_STARTTIME_') ? microtime(true) - MULTICACHE_STARTTIME_ : 'na';
                        $message .= ' took -' . $time . ' seconds to render';
                        $error_file = 'multicache_advancedcache_optimization.log';
                        
                        MulticacheFactory::loadErrorLogger($message, '', '', $error_file);
                    }
                    
                    $app->close();
                }
            }
            
            /*
             * aligning the non zipped content length
             * if(isset($data['Content_Length']))
             * {
             * $app->setHeader('Content-Length', $data['Content_Length']);
             * }
             */
            
            return $body;
        }
        
        // Get the document body out of the cache.
        
        return false;
    
    }

    protected function _noChange()
    {
        
        // test how 304 works in the hooks scenario; issuing exit or do we structure a return?
        header('HTTP/1.x 304 Not Modified', true);
        if (defined('MULTICACHE_STARTTIME_'))
        {
            $error_file = 'multicache_advancedcache_optimization.log';
            $message = "304 Met in Advanced cache";
            $time = defined('MULTICACHE_STARTTIME_') ? microtime(true) - MULTICACHE_STARTTIME_ : 'na';
            $message .= ' took -' . $time . ' seconds to render';
            MulticacheFactory::loadErrorLogger($message, '', '', $error_file);
        }
        exit(0);
    
    }

    public function closeLoop()
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

    protected function _setEtag($etag)
    {

        MulticacheFactory::getApplication()->setHeader('ETag', $etag, true);
    
    }

}