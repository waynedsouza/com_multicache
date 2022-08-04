<?php

/**
 *
 * @version 1.0.1.5
 * @package com_multicache
 * @copyright Copyright (C) 2015. All rights reserved.
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 * @author Wayne <wayne.dsouza@onlinemarketingconsultants.in> - http://OnlineMarketingConsultants.in
 */
// No direct access
defined('_JEXEC') or die();

/**
 *
 * @param
 *        array A named array
 * @return array
 */
function MulticacheBuildRoute(&$query)
{

    $segments = array();
    
    if (isset($query['task']))
    {
        $segments[] = implode('/', explode('.', $query['task']));
        unset($query['task']);
    }
    if (isset($query['view']))
    {
        $segments[] = $query['view'];
        unset($query['view']);
    }
    if (isset($query['id']))
    {
        $segments[] = $query['id'];
        unset($query['id']);
    }
    
    return $segments;

}

/**
 *
 * @param
 *        array A named array
 * @param
 *        array
 *        
 *        Formats:
 *        
 *        index.php?/multicache/task/id/Itemid
 *        
 *        index.php?/multicache/id/Itemid
 */
function MulticacheParseRoute($segments)
{

    $vars = array();
    
    // view is always the first element of the array
    $vars['view'] = array_shift($segments);
    
    while (! empty($segments))
    {
        $segment = array_pop($segments);
        if (is_numeric($segment))
        {
            $vars['id'] = $segment;
        }
        else
        {
            $vars['task'] = $vars['view'] . '.' . $segment;
        }
    }
    
    return $vars;

}
