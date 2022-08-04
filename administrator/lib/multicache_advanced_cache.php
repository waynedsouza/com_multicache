<?php

/*
 * @version 1.0.1.5
 * @package com_multicache
 * @copyright Copyright (C) Multicache.org 2015. All rights reserved.
 * @licenseEULA see LICENSE.txt - http://multicache.org/terms-conditions/end-user-license-agreement.html
 * @author Wayne DSouza <consulting@OnlineMarketingConsultants.in> - http://OnlineMarketingConsultants.in
 */
// to include
// add in index
// include_once $_SERVER['DOCUMENT_ROOT'] .'/administrator/components/com_multicache/lib/multicache_advanced_cache.php';
// No direct access
defined('_JEXEC') or die();
if (file_exists(dirname(__FILE__) . '/multicache_config.php'))
{
    include_once dirname(__FILE__) . '/multicache_factory.php';
    include_once dirname(__FILE__) . '/multicache_uri.php';
    include_once dirname(__FILE__) . '/multicache_application.php';
    include_once dirname(__FILE__) . '/multicache.php';
}

class Multicache_AdvancedCache
{

    public static function multicache_get_open_cached()
    {

        if (strtoupper($_SERVER['REQUEST_METHOD']) !== 'GET' || ! class_exists('MulticacheFactory'))
        {
            Return;
        }
        
        $config = MulticacheFactory::getConfig();
        $jmulticache_hash = $config->getC('jmulticache_hash');
        // $pass_cookieName = isset($jmulticache_hash) ? 'jmulticachep_' . $jmulticache_hash : null;
        $login_cookieName = isset($jmulticache_hash) ? 'jmulticache_logged_in_' . $jmulticache_hash : null;
        $c_handler = $config->getC('cache_handler');
        switch (true)
        {
           // case $config->getC('cache_handler') != 'fastcache':
        	case !($c_handler == 'fastcache' || $c_handler == 'file'):
            case $config->getC('caching') != 1:
            case $config->getC('debug') != 0:
            case $config->getC('offline') != 0:
            case ! isset($jmulticache_hash):
            case isset($_COOKIE[$login_cookieName]):
            // case isset($_COOKIE[$pass_cookieName]):
            case $config->getC('indexhack') != 1:
            case null === $config->getC('cachebase'):
            case strpos($config->getC('cachebase'), $_SERVER['DOCUMENT_ROOT']) === false:
            case $config->getC('multicachedistribution') === '0' || $config->getC('multicachedistribution') === '1':
            case defined('PHP_SAPI') && PHP_SAPI === 'cli':
                return;
        }
        
        $multicache_options = array(
            'defaultgroup' => 'page',
            'browsercache' => 1, // need to get this from admin
            'caching' => $config->getC('caching') == 1 ? true : false
        );
        
        $id = MulticacheUri::getInstance()->toString();
        $id = preg_replace('~[^a-zA-Z0-9\.\:\;\\\@\^\%\!\$\+\*\',\~\(\){}\|\[\]\`\"&\=\/\?\#_-]~', '', $id);
        // these are not supposed to come here if htaccess is configured right. Just in case
        // N.B: strpos in a foreach is usually faster. However we are not expecting these vars
        // hence str
        $folder = array(
            '/administrator/',
            '/cache/',
            '/images/',
            '/cli/',
            '/components/',
            '/downloads/',
            '/includes/',
            '/language/',
            '/layouts/',
            '/libraries/',
            '/logs/',
            '/media/',
            '/modules/',
            '/plugins/',
            '/templates/',
            '/tmp/'
        );
        
        str_ireplace($folder, '', $id, $replacement_count);
        if ($replacement_count >= 1)
        {
            Return;
        }
        $multicache_page_cache = Multicache::getInstance('page', $multicache_options);
        $c_obj = $multicache_page_cache->get($id, 'page');
        
        if ($c_obj !== false)
        {
            
            $app = MulticacheFactory::getApplication();
            
            $app->setBody($c_obj);
            
            echo $app->toString($app->get('gzip', true));
            if (defined('MULTICACHE_STARTTIME_'))
            {
                $message = "Page loaded from advanced cache LOOP3 Outer";
                $time = microtime(true) - MULTICACHE_STARTTIME_;
                $message .= ' took -' . $time . ' seconds to render';
                $error_file = 'multicache_advancedcache_optimization.log';
                MulticacheFactory::loadErrorLogger($message, '', '', $error_file);
            }
            
            exit();
        }
    
    }

}
Multicache_AdvancedCache::multicache_get_open_cached();