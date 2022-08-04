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

/*
 * To Instantiate Place this in Index.php
 * define('MULTICACHE_STARTTIME_',microtime(true));
 * if(!defined('MULTICACHEPROFILERDEBUG')
 * && file_exists('/home/wayneds/public_html/administrator/components/com_multicache/lib/multicache_factory.php'))
 * {
 * if(!class_exists('MulticacheFactory'))
 * {
 * include_once '/home/wayneds/public_html/administrator/components/com_multicache/lib/multicache_factory.php';
 * }
 * $GLOBALS['multicache_profiler'] = MulticacheFactory::getProfiler();
 * $GLOBALS['multicache_profiler']->mark('startIndex');
 * define('MULTICACHEPROFILERDEBUG',true);
 * }
 * else
 * {
 * define('MULTICACHEPROFILERDEBUG' , false);
 * }
 *
 * To Place a mark
 * defined('MULTICACHEPROFILERDEBUG') ? $GLOBALS['multicache_profiler']->mark('unique_mark_name'):null;
 *
 * To Collect results IN ONAFTER RENDER OF SYSTEM CACHE
 * if (defined('JPATH_SITE') && file_exists(JPATH_SITE . '/administrator/components/com_multicache/lib/multicache_factory.php') && defined('MULTICACHE_STARTTIME_') && ((($endtime = microtime(true) - MULTICACHE_STARTTIME_) >= 5) || $_SERVER['REQUEST_METHOD'] != 'GET'))
 * {
 * if (! class_exists('MulticacheFactory'))
 * {
 * include_once JPATH_SITE . '/administrator/components/com_multicache/lib/multicache_factory.php';
 * }
 * $error_file = $error_dir . 'multicache_cacheplugin_optimization.log';
 * $error_message = "\n" . $date . ' ' . ' PAGE TOOK ' . $endtime . ' to render ';
 * MULTICACHEPROFILERDEBUG ? $GLOBALS['multicache_profiler']->mark('onafterRender'):null;
 * $extra_message = MULTICACHEPROFILERDEBUG? $GLOBALS['multicache_profiler']: '';
 *
 * MulticacheFactory::loadErrorLogger($error_message, $extra_message, '', $error_file);
 * }
 *
 */
class MulticacheProfiler
{

    protected $start = 0;

    protected $prefix = '';

    protected $buffer = null;

    protected $marks = null;

    protected $previousTime = 0.0;

    protected $previousMem = 0.0;

    protected static $instances = array();

    public function __construct($prefix = '')
    {

        $this->start = microtime(1);
        $this->prefix = $prefix;
        $this->marks = array();
        $this->buffer = array();
    
    }

    public static function getInstance($prefix = '')
    {

        if (empty(self::$instances[$prefix]))
        {
            self::$instances[$prefix] = new MulticacheProfiler($prefix);
        }
        return self::$instances[$prefix];
    
    }

    public function mark($label)
    {

        $timetrack = microtime(true);
        $current = $timetrack - $this->start;
        $currentMem = memory_get_usage() / 1048576;
        $m = (object) array(
            'prefix' => $this->prefix,
            'timetrack' => $timetrack,
            'time' => ($current > $this->previousTime ? '+' : '-') . (($current - $this->previousTime) * 1000),
            'totalTime' => ($current * 1000),
            'memory' => ($currentMem > $this->previousMem ? '+' : '-') . ($currentMem - $this->previousMem),
            'totalMemory' => $currentMem,
            'label' => $label
        );
        $this->marks[] = $m;
        $mark = sprintf('%s %.3f seconds (%.3f); %0.2f MB (%0.3f) - %s', $m->prefix, $m->totalTime / 1000, $m->time / 1000, $m->totalMemory, $m->memory, $m->label);
        $this->buffer[] = $mark;
        $this->previousTime = $current;
        $this->previousMem = $currentMem;
        return $mark;
    
    }

    public static function getmicrotime()
    {

        list ($usec, $sec) = explode(' ', microtime());
        return ((float) $usec + (float) $sec);
    
    }

    public function getMemory()
    {

        return memory_get_usage();
    
    }

    public function getMarks()
    {

        return $this->marks;
    
    }

    public function getBuffer()
    {

        return $this->buffer;
    
    }

}