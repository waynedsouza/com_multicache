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
use Joomla\Registry\Registry;
jimport('joomla.application.component.controlleradmin');
require_once JPATH_COMPONENT . '/helpers/multicache.php';

class MulticacheControllerUrls extends JControllerAdmin
{

    public function getModel($name = 'urls', $prefix = 'MulticacheModel', $config = null)
    {

        $model = parent::getModel($name, $prefix, array(
            'ignore_request' => true
        ));
        return $model;
    
    }

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

    public function devolveConfig()
    {

        $model = $this->getModel();
        $success = $model->makeRegisterlnclass();
        $message = JText::_('COM_MULTICACHE_ERROR_URLARRAY_CLASS_WRITE_FAILED');
        
        if (! $success)
        {
            JError::raiseWarning(500, $message);
        }
        
        $this->setRedirect('index.php?option=com_multicache&view=urls');
    
    }

}