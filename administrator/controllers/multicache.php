<?php
/*
 * @version 1.0.1.5
 * @package com_multicache
 * @copyright Copyright (C) Multicache.org 2015. All rights reserved.
 * @licenseEULA see LICENSE.txt - http://multicache.org/terms-conditions/end-user-license-agreement.html
 * @author Wayne DSouza <consulting@OnlineMarketingConsultants.in> - http://OnlineMarketingConsultants.in
 */

// No direct access.
defined('_JEXEC') or die();

jimport('joomla.application.component.controlleradmin');

class MulticacheControllerMulticache extends JControllerAdmin
{

    protected $client;

    /**
     * Proxy for getModel.
     *
     * @since 1.6
     */
    public function getModel($name = 'multicache', $prefix = 'MulticacheModel')
    {

        $model = parent::getModel($name, $prefix, array(
            'ignore_request' => true
        ));
        return $model;
    
    }

    /**
     * Method to save the submitted ordering values for records via AJAX.
     *
     * @return void
     *
     * @since 3.0
     */
    public function saveOrderAjax()
    {
        // Get the input
        $input = JFactory::getApplication()->input;
        $pks = $input->post->get('cid', array(), 'array');
        $order = $input->post->get('order', array(), 'array');
        
        // Sanitize the input
        JArrayHelper::toInteger($pks);
        JArrayHelper::toInteger($order);
        
        // Get the model
        $model = $this->getModel();
        
        // Save the ordering
        $return = $model->saveorder($pks, $order);
        
        // Close the application
        JFactory::getApplication()->close();
    
    }

    public function delete()
    {

        JSession::checkToken() or jexit(JText::_('JInvalid_Token'));
        $app = JFActory::getApplication();
        
        $cid = $this->input->post->get('cid', array(), 'array');
        
        $model = $this->getModel();
        
        if (empty($cid))
        {
            JError::raiseWarning(500, JText::_('JERROR_NO_ITEMS_SELECTED'));
        }
        else
        {
            $model->cleanlist($cid);
        }
        
        $this->setRedirect('index.php?option=com_multicache&client=' . $model->getClient()->id);
    
    }

}