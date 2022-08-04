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
defined('_JEXEC') or die();

class MulticacheViewPagecache extends JViewLegacy
{

    protected $client;

    protected $data;

    protected $pagination;

    protected $state;

    public function display($tpl = 'manifest')
    {

        $cache_code = $this->get('CacheCode');
        $page_segments = unserialize($cache_code);
        $this->page_body = $page_segments['body'];
        parent::display($tpl);
    
    }

}