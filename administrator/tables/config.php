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
// No direct access
defined('JPATH_PLATFORM') or die();

/**
 * Config table
 *
 * @package Multicache
 *         
 *         
 */
class MulticacheTableConfig extends JTable
{

    public function __construct(&$_db)
    {

        parent::__construct('#__multicache_config', 'id', $_db);
        
        JTableObserverContenthistory::createObserver($this, array(
            'typeAlias' => 'com_multicache.multicache_config'
        ));
        
        $date = JFactory::getDate();
        $this->created = $date->toSql();
    
    }

    public function bind($array, $ignore = array())
    {

        return parent::bind($array, $ignore);
    
    }

    public function store($updateNulls = false)
    {

        $jinput = JFactory::getApplication()->input;
        
        $this->id = 1;
        
        $oldrow = JTable::getInstance('Config', 'MulticacheTable');
        
        if (! $oldrow->load($this->id) && $oldrow->getError())
        {
            $this->setError($oldrow->getError());
        }
        
        // Verify that the alias is unique
        $table = JTable::getInstance('Config', 'MulticacheTable');
        
        if ($table->load(array(
            'id' => $this->id
        )) && ($table->id != $this->id || $this->id == 0))
        {
            
            return false;
        }
        
        return parent::store($updateNulls);
    
    }

}