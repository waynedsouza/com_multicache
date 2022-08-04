<?php

/**
 *
 * @version 1.0.1.5
 * @package com_multicache
 * @copyright Copyright (C) Multicache.org 2015. All rights reserved.
 * @licenseEULA see LICENSE.txt - http://multicache.org/terms-conditions/end-user-license-agreement.html
 * @author Wayne DSouza <consulting@OnlineMarketingConsultants.in> - http://OnlineMarketingConsultants.in
 */
// No direct access
defined('_JEXEC') or die();

jimport('joomla.application.component.view');

/**
 * View class for a list of Multicache.
 */
class MulticacheViewConduit extends JViewLegacy
{

    protected $items;

    protected $pagination;

    protected $state;

    /**
     * Display the view
     */
    public function display($tpl = null)
    {

       // exit(0); // Open only when required
        $this->state = $this->get('State');
        
        header('Content-Type: application/json');
        header("X-Robots-Tag: noindex, nofollow", true);
        
        echo json_encode(array(
            'w' => 'byt56tyuiopoiuytvgbnhgftdrts54',
            'q' => md5(JSession::getFormToken()),
            't' => JSession::getFormToken()
        ));
        // Be sure that you're not storing any sensitive data in $_SESSION.
        // Better is to create an array with the data you need on client side:
        // 'session'=>array('user_id'=>$_SESSION['user_id'], /*etc.),
        
        exit(0);
    
    }

}