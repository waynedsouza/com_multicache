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

jimport('joomla.application.component.modellist');

/**
 * Methods supporting a list of Multicache records.
 */
class MulticacheModelUrls extends JModelList
{

    /**
     * Constructor.
     *
     * @param
     *        array An optional associative array of configuration settings.
     * @see JController
     * @since 1.6
     */
    public function __construct($config = array())
    {

        if (empty($config['filter_fields']))
        {
            $config['filter_fields'] = array(
                'id',
                'a.id',
                'url',
                'a.url',
                'cache_id',
                'a.cache_id',
                'views',
                'a.views',
                'f_dist',
                'a.f_dist',
                'ln_dist',
                'a.ln_dist',
                'type',
                'a.type',
                'created',
                'a.created'
            );
        }
        
        parent::__construct($config);
    
    }

    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     */
    protected function populateState($ordering = null, $direction = null)
    {
        // Initialise variables.
        $app = JFactory::getApplication('administrator');
        
        // Load the filter state.
        $search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);
        
        $published = $app->getUserStateFromRequest($this->context . '.filter.state', 'filter_published', '', 'string');
        $this->setState('filter.state', $published);
        
        $type_flag = $app->getUserStateFromRequest($this->context . '.filter.typeflag', 'filter_typeflag', '', 'string');
        $this->setState('filter.typeflag', $type_flag);
        
        // Load the parameters.
        $params = JComponentHelper::getParams('com_multicache');
        $this->setState('params', $params);
        
        // List state information.
        parent::populateState('a.views', 'desc');
    
    }

    public function getForm($data = array(), $loadData = true)
    {
        // Initialise variables.
        $app = JFactory::getApplication();
        
        // Get the form.
        $form = $this->loadForm('com_multicache.memcaches', 'memcaches', array(
            'control' => 'jform',
            'load_data' => $loadData
        ));
        
        if (empty($form))
        {
            return false;
        }
        
        return $form;
    
    }

    public function getUrlStats()
    {

        $db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->Select('Count(' . $db->quoteName('type') . '  ) As count');
        $query->Select($db->quoteName('type'));
        $query->from($db->quoteName('#__multicache_urlarray'));
        $query->group($db->quoteName('type'));
        $db->setQuery($query);
        $result = $db->loadObjectlist();
        $typecount = array();
        foreach ($result as $obj)
        {
            $typecount[$obj->type] = $obj->count;
        }
        return $typecount;
    
    }

    public function delete($pks)
    {

        $app = JFactory::getApplication();
        
        $user = JFactory::getUser();
        if (! $user->authorise('core.delete', $this->option))
        {
            $app->enqueueMessage('COM_MULTICACHE_URLS_USER_NOT_AUTHORISED_TO_DELETE');
            Return false;
        }
        $db = JFactory::getDBO();
        
        foreach ($pks as $i => $pk)
        {
            
            $query = $db->getQuery('true');
            $conditions = array(
                $db->quoteName('id') . ' = ' . $pk
            );
            $query->delete($db->quoteName('#__multicache_urlarray'));
            $query->where($conditions);
            
            $db->setQuery($query);
            
            $result = $db->execute();
        }
    
    }

    public function getItems()
    {

        $items = parent::getItems();
        
        return $items;
    
    }

    public function makeRegisterlnclass()
    {

        $lnparams = $this->getlnparams();
        $audit_string = 'audit-multicachedistribution-' . JFactory::getConfig()->get('secret');
        $debug_string = 'fastcache-debug';
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('DISTINCT ' . $db->quoteName('url'));
        $query->from($db->quoteName('#__multicache_urlarray'));
        $query->order($db->quoteName('views') . ' DESC');
        $db->setQuery($query);
        $uobj = $db->loadColumn();
        if (empty($uobj))
        {
            Return false;
        }
        $urlarray = array();
        foreach ($uobj as $url_key)
        {
            // in hammered mode we allow all variants of a particular url
            if ($lnparams->multicachedistribution == '3')
            {
                $urlarray[strtolower($url_key)] = 1;
            }
            else
            {
                $urlarray[$url_key] = 1;
            }
        }
        $urlarray[$audit_string] = $lnparams->multicachedistribution . '-' . date('Y-m-d');
        $urlarray[$debug_string] = ! empty($lnparams->debug_mode) ? 1 : null;
        $success = MulticacheHelper::registerLOGnormal($urlarray);
        Return $success;
    
    }

    protected function getStoreId($id = '')
    {
        // Compile the store id.
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.state');
        
        return parent::getStoreId($id);
    
    }

    protected function getListQuery()
    {

        $type_flag = $this->getState('filter.typeflag');
        
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->Select($this->getState('list.select', 'a.*'));
        $query->from($db->quoteName('#__multicache_urlarray') . ' As a');
        if ($type_flag)
        {
            $type_flag = ($type_flag == 'google') ? 'google' : 'manual';
            $query->where($db->quoteName('type') . ' = ' . $db->quote($type_flag));
        }
        
        $orderCol = $this->state->get('list.ordering');
        $orderDirn = $this->state->get('list.direction');
        $query->order($db->escape($orderCol . '  ' . $orderDirn));
        
        return $query;
    
    }

    protected function getlnparams()
    {

        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('*');
        $query->from($db->quoteName('#__multicache_config'));
        $query->where($db->quoteName('id') . ' = ' . $db->quote('1'));
        $db->setQuery($query);
        $res = $db->loadObject();
        Return $res;
    
    }

}