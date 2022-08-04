<?php
/**
 *
 * @version 1.0.1.5
 * @package com_multicache
 * @copyright Copyright (C) Multicache.org 2015. All rights reserved.
 * @licenseEULA see LICENSE.txt - http://multicache.org/terms-conditions/end-user-license-agreement.html
 * @author Wayne DSouza <consulting@OnlineMarketingConsultants.in> - http://OnlineMarketingConsultants.in
 */

// No direct access.
defined('_JEXEC') or die();

// JLoader::register('JCache', JPATH_ROOT . '/libraries/joomla/cache/cache.php');
JLoader::register('JCacheController', JPATH_ROOT . '/administrator/components/com_multicache/lib/controller.php', true);
require_once (JPATH_ROOT . '/libraries/joomla/cache/cache.php');

/*
 * JLog::addLogger(array(
 * 'text_file' => 'JCacheworkaroundRendering.php'
 * ), JLog::ALL, array(
 * 'error'
 * ));
 */
class JCacheWorkaround extends JCache
{

    /*
     * protected static $_signatures = null;
     * protected static $_loadsections = null;
     */
    protected static $_site_signature = null;

    public function __construct($options)
    {
        // the workarounds of Joomla Cache are actually not part of the JCache construct. This is kept for maintaining structure.
        parent::__construct($options);
    
    }

    /**
     * Perform workarounds on retrieved cached data
     *
     * @param string $data
     *        Cached data
     * @param array $options
     *        Array of options
     *        
     * @return string Body of cached data
     *        
     * @since 11.1
     */
    public static function getWorkarounds($data, $options = array())
    {

        /*
         * if (class_exists('JsStrategy') )
         * {
         * $data["body"] = self::performJstweaks($data["body"]);
         * //ambigous latter stages -- still in formation
         * }
         */
        $app = JFactory::getApplication();
        $document = JFactory::getDocument();
        $body = null;
        // $excluded_options = $app->input->get('option', null);//unreliable
        $precache_off = $options["force_precache_off"];
        
        // Get the document head out of the cache.
        if (isset($options['mergehead']) && $options['mergehead'] == 1 && isset($data['head']) && ! empty($data['head']))
        {
            $document->mergeHeadData($data['head']);
        }
        elseif (isset($data['head']) && method_exists($document, 'setHeadData'))
        {
            $document->setHeadData($data['head']);
        }
        
        // Get the document MIME encoding out of the cache
        if (isset($data['mime_encoding']))
        {
            $document->setMimeEncoding($data['mime_encoding'], true);
        }
        
        // If the pathway buffer is set in the cache data, get it.
        if (isset($data['pathway']) && is_array($data['pathway']))
        {
            // Push the pathway data into the pathway object.
            $pathway = $app->getPathWay();
            $pathway->setPathway($data['pathway']);
        }
        
        // If a module buffer is set in the cache data, get it.
        if (isset($data['module']) && is_array($data['module']))
        {
            // Iterate through the module positions and push them into the document buffer.
            foreach ($data['module'] as $name => $contents)
            {
                $document->setBuffer($contents, 'module', $name);
            }
        }
        
        // Set cached headers.
        if (isset($data['headers']) && $data['headers'])
        {
            foreach ($data['headers'] as $header)
            {
                $app->setHeader($header['name'], $header['value']);
            }
        }
        // hack #1 - gzipbodyhack
        
        $client_encodings_string = $app->client->acceptEncoding;
        $common_checks = ! headers_sent() && $app->get('gzip');
        /*
         * setting the formtokencookie
         * N.B: Setting form token cookies is an alternative to setting form tokens on login pages
         * They represent the context of the session in a md5 hash.
         */
        /*
         * NB : failed CSRF test
         */
        /*
         * $jmulticache_hash = $options['jmulticache_hash'];
         * $pass_cookie = 'jmulticachep_' . $jmulticache_hash;
         * //under audit
         * $no_precache = isset($_COOKIE[$pass_cookie]) || defined('JMULTICACHE_PASS');
         */
        /*
        if(defined('MULTICACHEJOOMLAVERSION') && MULTICACHEJOOMLAVERSION >= 3.5)
        {
                //temp hack to curtail Joomla goof up
       if( ! isset($data['multicache_meta']))
       {
       	//april 2016
       	error_reporting(0);
       	//this check produces a warning but we have no choice right now
       	$temp = @gzdecode ($data['body']);
       	
       	
       	if(false !== $temp)
       	{
       		$data['body'] = $temp;
       	}
       	$token = JSession::getFormToken();
       	// $search = '#<input type="hidden" name="[0-9a-f]{32}" value="1" />#';
       	$search = '#<input\s+type="hidden"\s+name="([0-9a-f]{32})"\s+value="1"([^/>]*)/?>#';
       	$replacement = '<input type="hidden" name="' . $token . '" value="1"\2/>';
       	
       	$data['body'] = preg_replace($search, $replacement, $data['body']);
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
       			$app->setHeader('ETag', $options["etag"], true);
       			echo $app->toString(false);
       			JCacheControllerPage::closeLoop();
       			if (defined('MULTICACHE_STARTTIME_'))
       			{
       				$emessage = "Page loading from TEMP HACK JCACHEWORKAROUNDS LOOP0";
       				$time = microtime(true) - MULTICACHE_STARTTIME_;
       				$emessage .= isset($time) ? " Page loaded in $time seconds" : "";
       				$emessage .= "  " . JURI::getInstance()->toString() . "  " . $_SERVER['REQUEST_METHOD'];
       				$emessage .= " UA - " . $_SERVER['HTTP_USER_AGENT'] . ' - remote address - ' . $_SERVER['REMOTE_ADDR'] . ' remote host - ' . $_SERVER['REMOTE_HOST'] . ' Port - ' . $_SERVER['REMOTE_PORT'] . ' - HTTP Origin' . $_SERVER["HTTP_ORIGIN"] . '- Referrer - ' . $_SERVER['HTTP_REFERER'];
       				$emessage .= $_SERVER['REQUEST_METHOD'] == 'POST' || 1 ? " request is " . var_export($_REQUEST, true) : "";
       				$emessage .= $_SERVER['REQUEST_METHOD'] == 'POST' || 1 ? " post is " . var_export($_POST, true) : "";
       				JLog::add($emessage, JLog::NOTICE);
       			}
       			$app->close();
       		}
       	}
       	return $body;
       	
       }
        //end temp hack
        }
        */
        
        $no_precache = ! isset($data['multicache_meta']) || isset($data['multicache_meta']['forms']) ? true : false;
        /*
         * NB : failed CSRF test
         */
        /*
         * $cookieName = $options['cookieName'];
         * if (isset($cookieName))
         * {
         * $form_token_cookie = $app->input->cookie->get($cookieName);
         * if (! isset($form_token_cookie))
         * {
         * $token = JSession::getFormToken();
         * $lifetime = $app->get('lifetime') * 60;
         * $app->input->cookie->set($cookieName, $token, time() + $lifetime, $app->get('cookie_path', '/'), $app->get('cookie_domain'), $app->isSSLConnection());
         * }
         * }
         */
        
        // rendering begin
        if (! empty($data['body_gzip']) && stripos($client_encodings_string, 'gzip') !== false && $common_checks && empty($precache_off) && ! $no_precache /*&& isset($cookieName)*/)
        {
            $app->setBody($data['body_gzip']); //
            $app->setHeader('Content-Encoding', 'gzip');
            $app->setHeader('ETag', $options["etag"], true);
            echo $app->toString(false);
            JCacheControllerPage::closeLoop();
            //
            if (defined('MULTICACHE_STARTTIME_'))
            {
                $emessage = "Page loading from precache JCacheWorkarounds LOOP1";
                $time = microtime(true) - MULTICACHE_STARTTIME_;
                $emessage .= isset($time) ? " Page loaded in $time seconds" : "";
                $emessage .= "  " . JURI::getInstance()->toString() . "  " . $_SERVER['REQUEST_METHOD'];
                $emessage .= " UA - " . $_SERVER['HTTP_USER_AGENT'] . ' - remote address - ' . $_SERVER['REMOTE_ADDR'] . ' remote host - ' . $_SERVER['REMOTE_HOST'] . ' Port - ' . $_SERVER['REMOTE_PORT'] . ' - HTTP Origin' . $_SERVER["HTTP_ORIGIN"] . '- Referrer - ' . $_SERVER['HTTP_REFERER'];
                $emessage .= $_SERVER['REQUEST_METHOD'] == 'POST' || 1 ? '  ' . serialize($_REQUEST) : "";
                $emessage .= $_SERVER['REQUEST_METHOD'] == 'POST' || 1 ? '  ' . serialize($_POST) : "";
                JLog::add($emessage, JLog::NOTICE);
            }
            //
            $app->close();
        }
        // The following code searches for a token in the cached page and replaces it with the
        // proper token.
        elseif (isset($data['body']))
        {
            
            $token = JSession::getFormToken();
            // $search = '#<input type="hidden" name="[0-9a-f]{32}" value="1" />#';
            $search = '#<input\s+type="hidden"\s+name="([0-9a-f]{32})"\s+value="1"([^/>]*)/?>#';
            $replacement = '<input type="hidden" name="' . $token . '" value="1"\2/>';
            
            $data['body'] = preg_replace($search, $replacement, $data['body']);
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
                    $app->setHeader('ETag', $options["etag"], true);
                    echo $app->toString(false);
                    JCacheControllerPage::closeLoop();
                    if (defined('MULTICACHE_STARTTIME_'))
                    {
                        $emessage = "Page loading from ELSE JCacheWorkarounds LOOP2";
                        $time = microtime(true) - MULTICACHE_STARTTIME_;
                        $emessage .= isset($time) ? " Page loaded in $time seconds" : "";
                        $emessage .= "  " . JURI::getInstance()->toString() . "  " . $_SERVER['REQUEST_METHOD'];
                        $emessage .= " UA - " . $_SERVER['HTTP_USER_AGENT'] . ' - remote address - ' . $_SERVER['REMOTE_ADDR'] . ' remote host - ' . $_SERVER['REMOTE_HOST'] . ' Port - ' . $_SERVER['REMOTE_PORT'] . ' - HTTP Origin' . $_SERVER["HTTP_ORIGIN"] . '- Referrer - ' . $_SERVER['HTTP_REFERER'];
                        $emessage .= $_SERVER['REQUEST_METHOD'] == 'POST' || 1 ? " request is " . var_export($_REQUEST, true) : "";
                        $emessage .= $_SERVER['REQUEST_METHOD'] == 'POST' || 1 ? " post is " . var_export($_POST, true) : "";
                        JLog::add($emessage, JLog::NOTICE);
                    }
                    $app->close();
                }
            }
            return $body;
        }
        
        // Get the document body out of the cache.
        
        return false;
    
    }

    protected static function tokenReplace($matches)
    {

        $replacement = '<input type="hidden" name="' . $matches['1'] . '" value="1" ' . trim($matches['2']) . '/><input type="hidden" name="' . self::$_site_signature . '" value="1" />';
        
        Return $replacement;
    
    }

    /**
     * Create workarounded data to be cached
     *
     * @param string $data
     *        Cached data
     * @param array $options
     *        Array of options
     *        
     * @return string Data to be cached
     *        
     * @since 11.1
     */
    public static function setWorkarounds($data, $options = array())
    {

        $loptions = array(
            'nopathway' => 0,
            'nohead' => 0,
            'nomodules' => 0,
            'modulemode' => 0
        );
        
        if (isset($options['nopathway']))
        {
            $loptions['nopathway'] = $options['nopathway'];
        }
        
        if (isset($options['nohead']))
        {
            $loptions['nohead'] = $options['nohead'];
        }
        
        if (isset($options['nomodules']))
        {
            $loptions['nomodules'] = $options['nomodules'];
        }
        
        if (isset($options['modulemode']))
        {
            $loptions['modulemode'] = $options['modulemode'];
        }
        
        $app = JFactory::getApplication();
        $document = JFactory::getDocument();
        
        if ($loptions['nomodules'] != 1)
        {
            // Get the modules buffer before component execution.
            $buffer1 = $document->getBuffer();
            if (! is_array($buffer1))
            {
                $buffer1 = array();
            }
            
            // Make sure the module buffer is an array.
            if (! isset($buffer1['module']) || ! is_array($buffer1['module']))
            {
                $buffer1['module'] = array();
            }
        }
        
        // View body data
        /*
         * if (class_exists('JsStrategy') )
         * {
         * $data = self::performJstweaks($data);
         *
         *
         * }
         */
        if (! isset(self::$_site_signature))
        {
            self::$_site_signature = substr(md5(md5($app->get('secret') . '-multicache-' . $app->get('cookie_domain') . '-' . JURI::getInstance()->getHost())), 2, 25);
        }
        // $search = '#<input type="hidden" name="([0-9a-f]{32})" value="1" />#';
        $search = '#<input\s+type="hidden"\s+name="([0-9a-f]{32})"\s+value="1"([^/>]*)/?>#';
        $form_count = 0;
        $cached['body'] = preg_replace_callback($search, 'self::tokenReplace', $data, - 1, $form_count);
        $options['forms'] = ! empty($form_count) ? $form_count : null;
        $cached['multicache_meta'] = $options;
        if (isset($options['makegzip']))
        {
            
            if (isset($options['precache_factor']))
            {
                $precache_factor = $options['precache_factor'];
            }
            else
            {
                $precache_factor = 7;
            }
            $cached['body_gzip'] = gzencode($cached['body'], $precache_factor, FORCE_GZIP);
        }
        
        // Document head data
        if ($loptions['nohead'] != 1 && method_exists($document, 'getHeadData'))
        {
            
            if ($loptions['modulemode'] == 1)
            {
                $headnow = $document->getHeadData();
                $unset = array(
                    'title',
                    'description',
                    'link',
                    'links',
                    'metaTags'
                );
                
                foreach ($unset as $un)
                {
                    unset($headnow[$un]);
                    unset($options['headerbefore'][$un]);
                }
                
                $cached['head'] = array();
                
                // Only store what this module has added
                foreach ($headnow as $now => $value)
                {
                    if (isset($options['headerbefore'][$now]))
                    {
                        // We have to serialize the content of the arrays because the may contain other arrays which is a notice in PHP 5.4 and newer
                        $nowvalue = array_map('serialize', $headnow[$now]);
                        $beforevalue = array_map('serialize', $options['headerbefore'][$now]);
                        
                        $newvalue = array_diff_assoc($nowvalue, $beforevalue);
                        $newvalue = array_map('unserialize', $newvalue);
                        
                        // Special treatment for script and style declarations.
                        if (($now == 'script' || $now == 'style') && is_array($newvalue) && is_array($options['headerbefore'][$now]))
                        {
                            foreach ($newvalue as $type => $currentScriptStr)
                            {
                                if (isset($options['headerbefore'][$now][strtolower($type)]))
                                {
                                    $oldScriptStr = $options['headerbefore'][$now][strtolower($type)];
                                    if ($oldScriptStr != $currentScriptStr)
                                    {
                                        // Save only the appended declaration.
                                        $newvalue[strtolower($type)] = JString::substr($currentScriptStr, JString::strlen($oldScriptStr));
                                    }
                                }
                            }
                        }
                    }
                    else
                    {
                        $newvalue = $headnow[$now];
                    }
                    
                    if (! empty($newvalue))
                    {
                        $cached['head'][$now] = $newvalue;
                    }
                }
            }
            else
            {
                $cached['head'] = $document->getHeadData();
            }
        }
        
        // Document MIME encoding
        $cached['mime_encoding'] = $document->getMimeEncoding();
        
        // Pathway data
        if ($app->isSite() && $loptions['nopathway'] != 1)
        {
            $pathway = $app->getPathWay();
            $cached['pathway'] = is_array($data) && isset($data['pathway']) ? $data['pathway'] : $pathway->getPathway();
        }
        
        if ($loptions['nomodules'] != 1)
        {
            
            // Get the module buffer after component execution.
            $buffer2 = $document->getBuffer();
            if (! is_array($buffer2))
            {
                $buffer2 = array();
            }
            
            // Make sure the module buffer is an array.
            if (! isset($buffer2['module']) || ! is_array($buffer2['module']))
            {
                $buffer2['module'] = array();
            }
            
            // Compare the second module buffer against the first buffer.
            $cached['module'] = array_diff_assoc($buffer2['module'], $buffer1['module']);
        }
        
        // Headers data
        if (isset($options['headers']) && $options['headers'])
        {
            $cached['headers'] = $app->getHeaders();
        }
        
        return $cached;
    
    }

}