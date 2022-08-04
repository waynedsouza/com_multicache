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
defined('_JEXEC') or die();

class MulticacheController extends JControllerLegacy
{

    public function display($cachable = false, $urlparams = false)
    {

        require_once JPATH_COMPONENT . '/helpers/multicache.php';
        
        $document = JFactory::getDocument();
        
        $vName = $this->input->get('view', 'multicache');
        $vFormat = $document->getType();
        $lName = $this->input->get('layout', 'default', 'string');
        
        // Get and render the view.
        if ($view = $this->getView($vName, $vFormat))
        {
            
            switch ($vName)
            {
                case 'purge':
                    break;
                case 'cache':
                default:
                    $model = $this->getModel($vName);
                    $view->setModel($model, true);
                    break;
            }
            
            $view->setLayout($lName);
            
            // Push document object into the view.
            $view->document = $document;
            
            // Load the submenu.
            
            MulticacheHelper::addSubmenu($this->input->get('view', 'multicache'));
            
            $view->display();
        }
    
    }

    public function delete()
    {
        
        // Check for request forgeries
        JSession::checkToken() or jexit(JText::_('JInvalid_Token'));
        
        $cid = $this->input->post->get('cid', array(), 'array');
        
        $model = $this->getModel('multicache');
        
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