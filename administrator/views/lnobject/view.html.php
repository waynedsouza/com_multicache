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

jimport('joomla.application.component.view');

/**
 * View class for a list of Multicache.
 */
class MulticacheViewLnobject extends JViewLegacy
{

    protected $items;

    protected $pagination;

    protected $state;

    /**
     * This view does not get displayed as were using redirect automatic 301 is set .
     *
     *
     *
     *
     *
     *
     * No issues with Google bot
     */
    public function display($tpl = null)
    {

        $result = $this->get('GoogleAuth');
        $message = JText::_("COM_MULTICACHE_GOOGLE_AUTHENTICATION_RETURN_FAILURE");
        $app->redirect('index.php?option=com_multicache&view=config&layout=edit&id=1#page-parta', $message, 'error');
        
        $app->close();
    
    }

}