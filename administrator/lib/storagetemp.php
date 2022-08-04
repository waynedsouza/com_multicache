<?php

/*
 * @version 1.0.1.5
 * @package com_multicache
 * @copyright Copyright (C) Multicache.org 2015. All rights reserved.
 * @licenseEULA see LICENSE.txt - http://multicache.org/terms-conditions/end-user-license-agreement.html
 * @author Wayne DSouza <consulting@OnlineMarketingConsultants.in> - http://OnlineMarketingConsultants.in
 */
// No direct access
defined('JPATH_PLATFORM') or die();
use Joomla\Registry\Registry;
JLoader::register('Waynesdebug', JPATH_ROOT . '/administrator/components/com_multicache/lib/debug.php');
JLoader::register('CartMulticache', JPATH_ROOT . '/administrator/components/com_multicache/lib/cartmulticache.php');

class JCacheStoragetemp extends JCacheStorage
{

    public $_cacheid = NULL;

    public function __construct($options = array())
    {

        parent::__construct($options);
    
    }

    public function getCacheid($id, $group)
    {

        $this->_cacheid = $this->_getCacheid($id, $group);
        Return $this->_cacheid;
    
    }

    public function getCacheidAlternate($id, $group)
    {

        $this->_cacheid = $this->_getAlternateCacheId($id, $group);
        Return $this->_cacheid;
    
    }

    public function getSecret()
    {

        $config = JFactory::getConfig();
        $this->_hash = md5($config->get('secret'));
        Return $this->_hash;
    
    }

    protected function _getCacheId($id, $group)
    {

        $name = md5($this->_application . '-' . $id . '-' . $this->_language);
        
        $this->rawname = $this->_hash . '-' . $name;
        return $this->_hash . '-cache-' . $group . '-' . $name;
    
    }

    protected function _getCacheIdb($id, $group)
    {

        if ($group != 'page')
        {
            $name = md5($this->_application . '-' . $id . '-' . $this->_language);
            $this->rawname = $this->_hash . '-' . $name;
            return $this->_hash . '-cache-' . $group . '-' . $name;
        }
        if (property_exists('CartMulticache', 'vars'))
        {
            $cartobj = CartMulticache::$vars;
        }
        
        if ($cartobj[cart_mode] == 0 || ($cartobj[cart_mode] == 1 && isset($cartobj['urls'][JURI::current()])) || ($cartobj[cart_mode] == 2 && ! isset($cartobj['urls'][JURI::current()])))
        {
            $session = JFactory::getApplication()->getSession();
            $cart_diff_obj = null;
            // set session registry & cat_vars
            
            if (isset($cartobj["cart_diff_vars"]))
            {
                $registry_vars = $session->get('registry');
                $cart_diffs = $cartobj["cart_diff_vars"];
                
                foreach ($cart_diffs as $key => $value)
                {
                    
                    $cart_diff_obj[] = $registry_vars->get($key);
                }
            }
            
            if (isset($cartobj['session_vars']))
            {
                $cartsessions = $cartobj['session_vars'];
                
                foreach ($cartsessions as $key => $namespace)
                {
                    $cart_diff_obj[] = $session->get($key, null, $namespace);
                }
            }
            
            // end of setting session & registry & cat vars
            
            if (! empty($cart_diff_obj))
            {
                $name = md5($this->_application . '-' . serialize($cart_diff_obj) . '-' . $id . '-' . $this->_language);
            }
            else
            {
                $name = md5($this->_application . '-' . $id . '-' . $this->_language);
            }
        }
        else
        {
            $name = md5($this->_application . '-' . $id . '-' . $this->_language);
        }
        $this->rawname = $this->_hash . '-' . $name;
        
        return $this->_hash . '-cache-' . $group . '-' . $name;
    
    }

    protected function _getAlternateCacheId($id, $group)
    {

        $cache_idarray = array();
        if ($group == 'page')
        {
            
            $session = JFactory::getApplication()->getSession();
            $registry = $session->get('registry');
            if (property_exists('CartMulticache', 'vars'))
            {
                $cartobj = CartMulticache::$vars;
                $cart_diff_obj = null;
                if (isset($cartobj["cart_diff_vars"]))
                {
                    $registry_vars = $session->get('registry');
                    $cart_diffs = $cartobj["cart_diff_vars"];
                    
                    foreach ($cart_diffs as $key => $value)
                    {
                        
                        $cart_diff_obj[] = $registry_vars->get($key);
                    }
                }
                
                if (isset($cartobj['session_vars']))
                {
                    $cartsessions = $cartobj['session_vars'];
                    
                    foreach ($cartsessions as $key => $namespace)
                    {
                        $cart_diff_obj[] = $session->get($key, null, $namespace);
                    }
                }
                if (! empty($cart_diff_obj))
                {
                    $name = md5($this->_application . '-' . serialize($cart_diff_obj) . '-' . $id . '-' . $this->_language);
                    $cache_idarray['alternate'] = $this->_hash . '-cache-' . $group . '-' . $name;
                }
            }
            
            $name = md5($this->_application . '-' . $id . '-' . $this->_language);
            $cache_idarray['original'] = $this->_hash . '-cache-' . $group . '-' . $name;
            Return $cache_idarray;
        }
        else
        {
            $name = md5($this->_application . '-' . $id . '-' . $this->_language);
            $cache_idarray['original'] = $this->_hash . '-cache-' . $group . '-' . $name;
        }
        
        Return $cache_idarray;
    
    }

}

?>