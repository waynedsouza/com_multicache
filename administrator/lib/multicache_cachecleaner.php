<?php
/*
 * @version 1.0.1.5
 * @package com_multicache
 * @copyright Copyright (C) Multicache.org 2015. All rights reserved.
 * @licenseEULA see LICENSE.txt - http://multicache.org/terms-conditions/end-user-license-agreement.html
 * @author Wayne DSouza <consulting@OnlineMarketingConsultants.in> - http://OnlineMarketingConsultants.in
 */
define('_JEXEC', 1);

if (file_exists(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/defines.php'))
{
    include_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/defines.php';
}

if (! defined('_JDEFINES'))
{
    define('JPATH_BASE', dirname(dirname(dirname(dirname(dirname(__FILE__))))));
    require_once JPATH_BASE . '/includes/defines.php';
}

require_once JPATH_BASE . '/includes/framework.php';

$app = JFactory::getApplication('site');
$user = JFactory::getUser();
if ($user->get('guest') || $app->input->getMethod() != 'POST')
{
    $app->close();
}
$config = JFactory::getConfig();
$session = JFactory::getSession();
JLoader::register('JCache', JPATH_ROOT . '/libraries/joomla/cache/cache.php');
/*
 * JLog::addLogger(array(
 * 'text_file' => 'cachecleanererrors.php'
 * ), JLog::ALL, array(
 * 'error'
 * ));
 */
/*
 * Level 1 security -> checks against core.admin & core.delete
 * Level 2 security -> anti spoofing check
 */
$canDo = new JObject();
$assetName = 'com_multicache';
$actions = array(
    'core.admin',
    'core.manage',
    'core.create',
    'core.edit',
    'core.edit.own',
    'core.edit.state',
    'core.delete'
);

foreach ($actions as $action)
{
    $canDo->set($action, $user->authorise($action, $assetName));
}
// JLog::add(var_export($canDo, true), JLog::ERROR);
if (! $canDo->get('core.admin') || ! $canDo->get('core.delete'))
{
    echo "Insufficient Permissions";
    $app->close();
}
$hash = md5(JFactory::getApplication()->get('secret') . $user->get('id', 0) . $session->getToken());
if (is_callable(array(
    'JApplication',
    'getHash'
)))
{
    $hash2 = JApplication::getHash($user->get('id', 0) . $session->getToken($forceNew));
}
$sec_pin = $app->input->post->get("p_sec");
$uri = $app->input->post->get("p_url", "", "HTML");
$uri_i = $app->input->post->get("p_urli", "", "HTML");
$task = $app->input->post->get("task");

if (empty($sec_pin))
{
    $app->close();
}

if (! ($sec_pin == $hash || $sec_pin == $hash2))
{
    echo JText::_("COM_MULTICACHE_CACHECLEANER_NOTAUTHORIZED");
    $app->close();
}
if ($task == 'hidecclr')
{
    // JLog::add(var_export($sec_pin, true), JLog::ERROR);
    $session->set('multicache_cclr_panelhide', true);
    echo "hidden";
    $app->close();
}
if (empty($uri_i) && empty($uri))
{
    $app->close();
}
// lets check the load
$load = sys_getloadavg();
if ($load[0] > 10)
{
    echo "Not Clearing Cache Load too high- " . $load[0];
    $app->close();
}

if (stristr($uri, 'www.'))
{
    $uri2 = str_ireplace('www.', '', $uri);
}
else
{
    $u_obj = JURI::getInstance($uri);
    $uri2 = $u_obj->getScheme() . '://www.' . preg_replace('~https?\:\/\/~', '', $u_obj->toString());
}
if (stristr($uri_i, 'www.'))
{
    $uri_i2 = str_ireplace('www.', '', $uri_i);
}
else
{
    $u_obj2 = JURI::getInstance($uri_i);
    $uri_i2 = $u_obj2->getScheme() . '://www.' . preg_replace('~https?\:\/\/~', '', $u_obj2->toString());
}
// creating the url array
$clean_urls = array();
$clean_urls[$uri] = 1;
$clean_urls[$uri_i] = 1;
$clean_urls[$uri2] = 1;
$clean_urls[$uri_i2] = 1;
// creating type 2 for some Apache servers with issues in trailing /
if (empty($clean_urls))
{
    $app->close();
}
foreach ($clean_urls as $clean_url => $val)
{
    If (! stristr($clean_url, '?') && substr($clean_url, - 1) == '/')
    {
        $key = substr($clean_url, 0, - 1);
        $clean_urls[$key] = 1;
    }
    if (stristr($clean_url, '/?'))
    {
        $key = str_replace('/?', '?', $clean_url);
        $clean_urls[$key] = 1;
    }
}
// instantiate the cache object

$options = array(
    'defaultgroup' => 'page',
    'storage' => $config->get('cache_handler', ''),
    'caching' => true,
    'cachebase' => $config->get('cache_path', JPATH_SITE . '/cache')
);
$cache = JCache::getInstance('', $options);
// $cache->remove($id, 'page');
$result = array();
foreach ($clean_urls as $url => $val)
{
    $result[$url] = $cache->remove($url, 'page');
}
$cache_cleaned = false;
foreach ($result as $url => $val)
{
    if (! empty($result[$url]))
    {
        $cache_cleaned = true;
        break;
    }
}
if ($cache_cleaned)
{
    echo JText::_('COM_MULTICACHE_PAGE_CLEARED_FROM_CACHE');
}
else
{
    echo JText::_('COM_MULTICACHE_PAGE_NOT_CLEARED_FROM_CACHE');
}
/*
 * JLog::add(var_export($sec_pin, true), JLog::ERROR);
 * JLog::add(var_export($clean_urls, true), JLog::ERROR);
 * JLog::add(var_export(JPATH_ROOT . '/libraries/joomla/cache/cache.php', true), JLog::ERROR);
 * JLog::add(var_export($result, true), JLog::ERROR);
 */

$app->close();