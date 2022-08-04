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

jimport('joomla.application.component.controllerform');

/**
 * Multicache_config controller class.
 */
class MulticacheControllerConfig extends JControllerForm
{

    function __construct()
    {

        $this->view_list = 'advancedsimulation';
        parent::__construct();
    
    }

    public function getconfig()
    {

        $this->setRedirect(JRoute::_('index.php?option=com_multicache&view=config&layout=edit&id=1', false));
    
    }

    public function authenticate()
    {

        $model = $this->getModel('config');
        $model->getConfigSetUp();
        $saved = parent::save(null, null);
        
        $model = $this->getModel('lnobject');
        try
        {
            $googobj = $model->getGoogleAuth();
        }
        catch (Exception $e)
        {
            $code = $e->getCode();
            $emessage = $e->getMessage();
            $message = JText::_('COM_MULTICACHE_GOOGLE_CREDENTIAL_ERROR_CHECK_CREDENTIALS') . '  <br>' . 'Google response :' . $emessage;
            $othermessage = "error unknown";
            if ($code == 0)
            {
                JError::raiseWarning(500, $message);
            }
            else
            {
                JError::raiseWarning(500, $othermessage);
            }
            $r_uri = $this->checkHeadersSent() ? 'index.php?option=com_multicache&view=config&layout=edit&id=1' : 'index.php?option=com_multicache&view=config&layout=edit&id=1#page-parta';
            $this->setRedirect( $r_uri );
        }
    
    }

    public function save($key = NULL, $urlVar = NULL)
    {

        $model = $this->getModel('config');
        $model->checkConfigParams();
        $model->getConfigSetUp();
        parent::save($key, $urlVar);
        // registering the lognormal here implies that at the time of authentication save the class urlArray is not made or updated
        $model = $this->getModel('urls');
        $model->makeRegisterlnclass();
    
    }

    public function scrapetemplate()
    {

        $app = JFactory::getApplication();
        
        $model = $this->getModel();
        
        $flag = $model->makeTemplatePage();
        //issue detected if js redirect then fragment will have to go
        $r_uri = $this->checkHeadersSent() ? 'index.php?option=com_multicache&view=config&layout=edit&id=1' : 'index.php?option=com_multicache&view=config&layout=edit&id=1#page-js-tweaks';
        if ($flag)
        {
            $app->enqueueMessage(JText::_('COM_MULTICACHE_SCRAPE_TEMPLATE_POST_MESSAGE_LABEL'), 'message');
            $this->setRedirect($r_uri);
        }
        else
        {
            $message = JText::_('COM_MULTICACHE_SCRAPE_TEMPLATE_ERROR_FAILED');
            JError::raiseWarning(500, $message);
            $this->setRedirect($r_uri);
        }
    
    }

    public function scrapecsstemplate()
    {

        $app = JFactory::getApplication();
        
        $model = $this->getModel();
        
        $flag = $model->makeCssPage();
        $r_uri = $this->checkHeadersSent() ? 'index.php?option=com_multicache&view=config&layout=edit&id=1' : 'index.php?option=com_multicache&view=config&layout=edit&id=1#page-css-tweaks';
        if ($flag)
        {
            $app->enqueueMessage(JText::_('COM_MULTICACHE_SCRAPE_CSS_POST_MESSAGE_LABEL'), 'message');
            $this->setRedirect($r_uri);
        }
        else
        {
            $message = JText::_('COM_MULTICACHE_SCRAPE_CSS_ERROR_FAILED');
            JError::raiseWarning(500, $message);
            $this->setRedirect($r_uri);
        }
    
    }

    public function reset()
    {

        $app = JFactory::getApplication();
        $model = $this->getModel();
        $model->performFactoryReset();
        $this->setRedirect('index.php?option=com_multicache&view=config&layout=edit&id=1');
    
    }
    protected function checkHeadersSent()
    {
    	return headers_sent();
    }

}