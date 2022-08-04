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
class MulticacheViewSimcontrol extends JViewLegacy
{

    protected $items;

    protected $pagination;

    protected $state;

    /**
     * Display the view
     */
    public function display($tpl = null)
    {

        $this->state = $this->get('State');
        $model = $this->getModel();
        header("X-Robots-Tag: noindex, nofollow", true);
        
        $this->get('SimulatedIterationTest');
        $this->get('NonSimulatedTest');
        
        exit(0);
    
    }

}