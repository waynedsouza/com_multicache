<?php
/*
 * @version 1.0.1.5
 * @package com_multicache
 * @copyright Copyright (C) Multicache.org 2015. All rights reserved.
 * @licenseEULA see LICENSE.txt - http://multicache.org/terms-conditions/end-user-license-agreement.html
 * @author Wayne DSouza <consulting@OnlineMarketingConsultants.in> - http://OnlineMarketingConsultants.in
 */
define('_JEXEC', 1);

if (file_exists(dirname(dirname(dirname(__FILE__))) . '/defines.php'))
{
    include_once dirname(dirname(dirname(__FILE__))) . '/defines.php';
}

if (! defined('_JDEFINES'))
{
    define('JPATH_BASE', dirname(dirname(dirname(__FILE__))));
    require_once JPATH_BASE . '/includes/defines.php';
}

require_once JPATH_BASE . '/includes/framework.php';

$app = JFactory::getApplication('site');
$config = JFactory::getConfig();
$cache_switch = $config->get('caching', 0);

// test this on php 5.3
$sub_precache_factor = null !== $config->get('sub_precache_factor') ? $config->get('sub_precache_factor') : $config->get('precache_factor', 4);
$bodydata = null;
$u = JURI::getInstance();
$u->setVar('cbypass', 'true');
$a_uri = $u->toString();
if (empty($cache_switch))
{
    header("Location: $a_uri", true, 307);
    exit(0);
}
$cache = JFactory::getCache('multicache_assets', '');
$id = $app->input->get('i') . '.' . $app->input->get('j');
$key = $config->get('secret') . '_multicache_assets_' . $id;
$data = $cache->get($key);
// cache Primer
if (empty($data))
{
    $file_class = new JHTTP();
    $file = $file_class->get($a_uri);
    
    if ($file->code == 200)
    {
        $multicache_asset_obj = new stdClass();
        
        $multicache_asset_obj->date = $file->headers["Date"];
        $multicache_asset_obj->ETag = $file->headers["ETag"];
        $multicache_asset_obj->Last_Modified = $file->headers["Last-Modified"];
        $multicache_asset_obj->Vary = $file->headers["Vary"];
        $multicache_asset_obj->Cache_Control = $file->headers["Cache-Control"];
        $multicache_asset_obj->Expires = $file->headers["Expires"];
        $multicache_asset_obj->Content_Type = $file->headers["Content-Type"];
        $multicache_asset_obj->body = $file->body;
        
        $multicache_asset_obj->body_gzip = gzencode($file->body, $sub_precache_factor, FORCE_GZIP);
        if (! empty($multicache_asset_obj->body))
        {
            $cache->store($multicache_asset_obj, $key);
            $data = $multicache_asset_obj;
        }
    }
}
// $data should not be empty by now
// process
// 1. HTTP_IF_NONE_MATCH_
// 2. Header etag
// 3. get clients acceptable encodings
// 4. If $compress & not previously compressed
// 5. get supported encodings
// 6. if support gzip and isset body gzip set
// 7. set the content encoding header to gzip
if (! empty($data))
{
    
    if (isset($_SERVER['HTTP_IF_NONE_MATCH']))
    {
        
        $etag_stored = $data->ETag;
        $etag_stored_gzip = $etag_stored . '-gzip';
        $etag = stripslashes($_SERVER['HTTP_IF_NONE_MATCH']);
        
        if ($etag == $etag_stored_gzip || $etag == $etag_stored)
        {
            header('HTTP/1.x 304 Not Modified', true);
            $app->close();
        }
        $app->setHeader('ETag', $etag_stored, true);
        $compress = $app->get('gzip');
        $clientencodings = array_map('trim', explode(',', $app->client->acceptEncoding));
        
        if (! empty($clientencodings) && $compress && (bool) (extension_loaded('zlib') || ! ini_get('zlib.output_compression')) && ini_get('output_handler') != 'ob_gzhandler')
        {
            
            $supported = array(
                'x-gzip' => 'gz',
                'gzip' => 'gz',
                'deflate' => 'deflate'
            );
            $encodings = array_intersect($clientencodings, array_keys($supported));
            if (isset($data->body_gzip) && in_array('gzip', $encodings))
            {
                // echo "from zipper";
                $bodydata = $data->body_gzip;
                $app->setHeader('Content-Type', $data->Content_Type);
                $app->setHeader('Content-Encoding', 'gzip');
            }
            elseif (! empty($encodings) && ! headers_sent() && (connection_status() === CONNECTION_NORMAL))
            {
                
                foreach ($encodings as $encoding)
                {
                    
                    // a $logical gate check here appears redundant
                    
                    $gzdata = gzencode($bodydata, $sub_precache_factor, ($supported[$encoding] == 'gz') ? FORCE_GZIP : FORCE_DEFLATE);
                    
                    if ($gzdata === false)
                    {
                        continue;
                    }
                    $app->setHeader('Content-Type', $data->Content_Type);
                    $app->setHeader('Content-Encoding', $encoding);
                    // $app->setHeader('X-Content-Encoded-By', 'Joomla');
                    $bodydata = $gzdata;
                    
                    break;
                }
            }
        }
    }
    // we do not have browser cache details here
    if ($options['browsercache'] === 0)
    {
        $app->setHeader('Cache-Control', 'no-cache', false);
        $app->setHeader('Pragma', 'no-cache');
    }
    // not yet handled the case of no compress
    if (empty($bodydata))
    {
        $bodydata = $data->body;
        $app->setHeader('Content-Type', $data->Content_Type);
    }
    $app->sendHeaders();
    echo $bodydata;
    // test1
    $app->close();
}
else
{
    
    $app->redirect($a_uri);
}

