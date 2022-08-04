<?php
/**
 *
 * @version 1.0.1.5
 * @package com_multicache
 * @copyright Copyright (C) Multicache.org 2015. All rights reserved.
 * @licenseEULA see LICENSE.txt - http://multicache.org/terms-conditions/end-user-license-agreement.html
 * @author Wayne DSouza <consulting@OnlineMarketingConsultants.in> - http://OnlineMarketingConsultants.in
 */
defined('_JEXEC') or die();

/**
 * Page Cache Inspector Model
 *
 * @telnet putty eliminator for memcache
 *
 * @subpackage com_multicache
 *             @ not recommended for very hight traffic web pages. In case server visits exceed 25K visits. Stipulated splice on array for every
 *             20 objects.
 */
JLoader::import('stat', JPATH_ADMINISTRATOR . '/components/com_multicache/models');

class MulticacheModelPagecache extends JModelList
{

    protected $_data = array();

    /**
     * Group total
     *
     * @var integer
     */
    protected $_total = null;

    /**
     * Pagination object
     *
     * @var object
     */
    protected $_pagination = null;

    protected static $_page_key_detail_exception = null;

    protected static $_page_key_detail_ne = null;

    protected static $_ordering = null;

    protected static $_direction = null;

    public function __construct($config = array())
    {

        if (empty($config['filter_fields']))
        {
            $config['filter_fields'] = array(
                'id',
                'a.id',
                'url',
                'a.url',
                'views',
                'a.views',
                'cache_id',
                'a.cache_id',
                'type',
                'a.type'
            );
        }
        
        parent::__construct($config);
    
    }

    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @since 1.6
     */
    protected function populateState($ordering = null, $direction = null)
    {

        $cacheStandard = $this->getUserStateFromRequest($this->context . '.filter.cache_standard', 'filter_cache_standard', 0, 'int');
        $this->setState('cacheStandard', $cacheStandard);
        
        parent::populateState('views', 'desc');
    
    }

    /**
     * Method to get cache data
     *
     * @return array
     */
    public function getData()
    {

        jimport('joomla.utilities.arrayhelper');
        self::$_ordering = $ordering = $this->getState('list.ordering');
        self::$_direction = $direction = ($this->getState('list.direction') == 'asc') ? 1 : - 1;
        $cacheStandard = $this->getState('cacheStandard');
        
        $comp = JModelLegacy::getInstance('stat', 'MulticacheModel');
        $comp->prepareStat();
        $Allkeys = $comp->getAllKeys();
        $page_keys = array();
        foreach ($Allkeys as $key)
        {
            if (stristr($key, '-page-'))
            {
                $page_keys[] = $key;
            }
        }
        
        $pages = $this->getPagesfromkeys($page_keys);
        
        if ($cacheStandard == 1)
        {
            foreach ($pages as $key => $page)
            {
                if ($page['type'] == 'standard')
                {
                    $obj[$key] = $page;
                }
            }
            if (empty($obj))
            {
                Return false;
            }
            $pages = $obj;
        }
        elseif ($cacheStandard == 2)
        {
            foreach ($pages as $key => $page)
            {
                if ($page['type'] == 'nonstandard')
                {
                    $obj[$key] = $page;
                }
            }
            if (empty($obj))
            {
                Return false;
            }
            $pages = $obj;
        }
        
        $pages = $this->assignPageid($pages);
        if (empty($pages))
        {
            Return false;
        }
        usort($pages, 'self::cmp');
        $this->_total = count($pages);
        if ($this->_total > $this->getState('list.limit') && $this->getState('list.limit'))
        {
            $pages = array_slice($pages, $this->getState('list.start'), $this->getState('list.limit'));
        }
        
        Return $pages;
    
    }

    public function getHitStats()
    {

        $db = JFactory::getDBO();
        $query = $db->getQuery('true');
        $query->select('*');
        $query->from($db->quoteName('#__multicache_items_stats'));
        $query->order($db->quotename('timestamp') . ' DESC');
        $db->setQuery($query);
        $quick_stat = $db->LoadObject();
        if (empty($quick_stat))
        {
            Return false;
        }
        if (($quick_stat->get_hits + $quick_stat->get_misses) > 0)
        {
            $quick_stat->getrate = $quick_stat->get_hits / ($quick_stat->get_hits + $quick_stat->get_misses);
        }
        else
        {
            $quick_stat->getrate = 0;
        }
        if (($quick_stat->delete_hits + $quick_stat->delete_misses) > 0)
        {
            $quick_stat->deleterate = $quick_stat->delete_hits / ($quick_stat->delete_hits + $quick_stat->delete_misses);
        }
        else
        {
            $quick_stat->deleterate = 0;
        }
        
        Return $quick_stat;
    
    }

    /**
     * Method to get cache instance
     *
     * @return object
     */
    public function getCache()
    {

        $conf = JFactory::getConfig();
        
        $options = array(
            'defaultgroup' => '',
            'storage' => $conf->get('cache_handler', ''),
            'caching' => true,
            'cachebase' => ($this->getState('clientId') == 1) ? JPATH_ADMINISTRATOR . '/cache' : $conf->get('cache_path', JPATH_SITE . '/cache')
        );
        
        $cache = JCache::getInstance('', $options);
        
        return $cache;
    
    }

    /**
     * Method to get client data
     *
     * @return array
     */
    public function getClient()
    {

        return $this->getState('client');
    
    }

    /**
     * Get the number of current Cache Groups
     *
     * @return int
     */
    public function delete()
    {

        $delete_cache_keys = JFactory::getApplication()->input->post->get('cid', array(), 'array');
        $comp = JModelLegacy::getInstance('stat', 'MulticacheModel');
        $comp->deleteKeys($delete_cache_keys);
    
    }

    public function getTotal()
    {

        if (! empty($this->_total))
        {
            return $this->_total;
        }
        
        Return false;
    
    }

    /**
     * Method to get a pagination object for the cache
     *
     * @return integer
     */
    public function getPagination()
    {

        if (empty($this->_pagination))
        {
            $this->_pagination = new JPagination($this->getTotal(), $this->getState('list.start'), $this->getState('list.limit'));
        }
        
        return $this->_pagination;
    
    }

    /**
     * Clean out a cache group as named by param.
     * If no param is passed clean all cache groups.
     *
     * @param String $group        
     */
    public function clean($group = '')
    {

        $cache = $this->getCache();
        $cache->clean($group);
    
    }

    public function cleanlist($array)
    {

        foreach ($array as $group)
        {
            $this->clean($group);
        }
    
    }

    public function purge()
    {

        $cache = JFactory::getCache('');
        return $cache->gc();
    
    }

    public function getCacheCode()
    {

        $comp = JModelLegacy::getInstance('stat', 'MulticacheModel');
        $cache_id = JFactory::getApplication()->input->get('id');
        Return $comp->getPageByKeyRenderPage($cache_id);
    
    }

    protected function getPagesfromkeys($keys)
    {

        $keys = array_flip($keys);
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        
        $query->select($db->quoteName('url'));
        $query->select($db->quoteName('cache_id'));
        $query->select($db->quoteName('cache_id_alt'));
        $query->select($db->quoteName('cache_id_alt_ext'));
        $query->select($db->quoteName('views'));
        $query->from($db->quoteName('#__multicache_urlarray'));
        $db->setQuery($query);
        $keys_on_record = $db->loadAssocList();
        
        $page_key_detail = array();
        $page_url_detail = array();
        
        foreach ($keys_on_record as $key => $value)
        {
            $cache_id_check = $value[cache_id];
            $cache_id_alt_check = $value[cache_id_alt];
            $cache_id_alt_ext_check = $value[cache_id_alt_ext];
            if (isset($keys[$cache_id_check]) || isset($keys[$cache_id_alt_check]) || isset($keys[$cache_id_alt_ext_check]))
            {
                
                $page_key_detail[$cache_id_check] = array(
                    "url" => $value[url],
                    "views" => $value[views],
                    "cache_id" => $value[cache_id],
                    "type" => "standard"
                );
            }
            $page_url_detail[$value[url]] = array(
                "url" => $value[url],
                "views" => $value[views],
                "cache_id" => $value[cache_id]
            );
        }
        
        $key_nonexistent = array_diff_key($keys, $page_key_detail);
        $array2 = $this->getPagedetailsfromcache($key_nonexistent, $page_url_detail);
        $array3 = ! empty(self::$_page_key_detail_exception) ? self::$_page_key_detail_exception : null;
        if (is_array($array2))
        {
            $page_details = array_merge($page_key_detail, $array2);
        }
        else
        {
            $page_details = $page_key_detail;
        }
        if (isset($array3) && is_array($array3))
        {
            $page_details = array_merge($page_details, $array3);
        }
        Return $page_details;
    
    }

    protected function getPagedetailsfromcache($keys, $url_details)
    {

        JLoader::import('stat', JPATH_ADMINISTRATOR . '/components/com_multicache/models');
        $comp = JModelLegacy::getInstance('stat', 'MulticacheModel');
        $page_key_detail_ne = array();
        foreach ($keys as $key => $value)
        {
            $page = $comp->getPageByKey($key);
            if (isset($url_details[strtolower($page)]))
            {
                self::$_page_key_detail_ne[$key] = array(
                    "url" => $page,
                    "views" => $url_details[strtolower($page)]['views'],
                    "cache_id" => $key,
                    "type" => "nonstandard"
                );
            }
            elseif ($page)
            {
                self::$_page_key_detail_exception[$key] = array(
                    "url" => $page,
                    "views" => null,
                    "cache_id" => $key,
                    "type" => "exception"
                );
            }
        }
        
        Return self::$_page_key_detail_ne;
    
    }

    protected static function cmp($a, $b)
    {

        $direction = self::$_direction;
        $ordering = self::$_ordering;
        
        if ($direction == 1)
        {
            
            if ($a[$ordering] == $b[$ordering])
            {
                Return 0;
            }
            return ($a[$ordering] < $b[$ordering]) ? - 1 : 1;
        }
        
        if ($a[$ordering] == $b[$ordering])
        {
            Return 0;
        }
        return ($a[$ordering] > $b[$ordering]) ? - 1 : 1;
    
    }

    protected function assignPageid($pages)
    {

        if (empty($pages))
        {
            Return false;
        }
        $page_id = 1;
        foreach ($pages as $key => $value)
        {
            $value['id'] = $page_id ++;
            $pages[$key] = $value;
        }
        Return $pages;
    
    }

}