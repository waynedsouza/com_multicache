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

class MulticacheModelMulticache extends JModelList
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

    protected $_file_count = null;

    protected $_file_size = null;

    protected static $_ordering = null;

    protected static $_direction = null;

    public function __construct($config = array())
    {

        if (empty($config['filter_fields']))
        {
            $config['filter_fields'] = array(
                'id',
                'a.id',
                'count',
                'a.count',
                'size',
                'a.size',
                'group',
                'a.group'
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

        $clientId = $this->getUserStateFromRequest($this->context . '.filter.client_id', 'filter_client_id', 0, 'int');
        $this->setState('clientId', $clientId == 1 ? 1 : 0);
        
        $cacheType = $this->getUserStateFromRequest($this->context . '.filter.cache_type', 'filter_cache_type', 0, 'int');
        $this->setState('cacheType', $cacheType);
        
        $client = JApplicationHelper::getClientInfo($clientId);
        $this->setState('client', $client);
        
        parent::populateState('group', 'asc');
    
    }

    /**
     * Method to get cache data
     *
     * @return array
     */
    public function getData()
    {

        $config = JFactory::getConfig();
        $cache_handler_flag = $config->get('cache_handler') == 'fastcache' ? 1 : 0;
        
        if (empty($this->_data))
        {
            $cache = $this->getCache();
            $data = $cache->getAll();
            $cachetypefilter = $this->getState('cacheType');
            
            if ($data != false)
            {
                if ($cachetypefilter == 2 && $cache_handler_flag)
                {
                    foreach ($data as $key => $value)
                    {
                        if (stristr($key, '_filecache'))
                        {
                        }
                        else
                        {
                            $temp[$key] = $value;
                        }
                    }
                    $this->_data = $data = $temp;
                }
                elseif ($cachetypefilter == 1 && $cache_handler_flag)
                {
                    
                    foreach ($data as $key => $value)
                    {
                        if (stristr($key, '_filecache'))
                        {
                            $temp[$key] = $value;
                        }
                        else
                        {
                        }
                    }
                    $this->_data = $data = $temp;
                }
                else
                {
                    $this->_data = $data;
                }
                $this->_total = count($data);
                
                if ($this->_total)
                {
                    
                    foreach ($data as $key => $value)
                    {
                        $this->_file_count += $value->count;
                        $this->_file_size += ($value->size * $this->_file_count);
                    }
                    
                    // Apply custom ordering
                    $ordering = $this->getState('list.ordering');
                    $direction = ($this->getState('list.direction') == 'asc') ? 1 : - 1;
                    self::$_ordering = $ordering;
                    self::$_direction = $direction;
                    
                    jimport('joomla.utilities.arrayhelper');
                    $this->_data = JArrayHelper::sortObjects($data, $ordering, $direction);
                    // usort($data, 'self::cmp');
                    $this->_data = $data;
                    // Apply custom pagination
                    if ($this->_total > $this->getState('list.limit') && $this->getState('list.limit'))
                    {
                        $this->_data = array_slice($this->_data, $this->getState('list.start'), $this->getState('list.limit'));
                    }
                }
            }
            else
            {
                $this->_data = array();
            }
        }
        return $this->_data;
    
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

    public function getPingStats()
    {

        $comp = JModelLegacy::getInstance('stat', 'MulticacheModel');
        $comp->prepareStat();
    
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
        if (($quick_stat->delete_hits + $quick_stat->delete_hits))
        {
            $quick_stat->deleterate = $quick_stat->delete_hits / ($quick_stat->delete_hits + $quick_stat->delete_misses);
        }
        else
        {
            $quick_stat->deleterate = 0;
        }
        $quick_stat->filesize = $this->_file_size;
        $quick_stat->filecount = $this->_file_count;
        
        Return $quick_stat;
    
    }

    /**
     * Get the number of current Cache Groups
     *
     * @return int
     */
    public function getTotal()
    {

        if (empty($this->_total))
        {
            $this->_total = count($this->getData());
        }
        
        return $this->_total;
    
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

    protected static function cmp($a, $b)
    {

        $direction = self::$_direction;
        $ordering = self::$_ordering;
        
        if ($direction == 1)
        {
            
            if ($a->$ordering == $b->$ordering)
            {
                Return 0;
            }
            return ($a->$ordering < $b->$ordering) ? - 1 : 1;
        }
        
        if ($a->$ordering == $b->$ordering)
        {
            Return 0;
        }
        return ($a->$ordering > $b->$ordering) ? - 1 : 1;
    
    }

}