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

JLoader::register('MulticacheOauthClient', JPATH_COMPONENT . '/lib/oauthclient.php', true);
JLoader::register('JCacheStoragetemp', JPATH_COMPONENT . '/lib/storagetemp.php', true);
jimport('joomla.application.component.controller');

/**
 * lognormal Model
 *
 * @package Multicache
 *         
 *         
 */
class MulticacheModelLnobject extends JModelList
{

    /**
     * An Array of CacheItems indexed by cache group ID
     *
     * @var Array
     */
    protected $_data = array();

    /**
     * Group total
     *
     * @var integer
     */
    protected $_total = null;

    /**
     * Pagination object
     *
     * @var object
     */
    protected $_pagination = null;

    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @since 1.6
     */
    protected function populateState($ordering = null, $direction = null)
    {

        $clientId = $this->getUserStateFromRequest($this->context . '.filter.client_id', 'filter_client_id', 0, 'int');
        $this->setState('clientId', $clientId == 1 ? 1 : 0);
        
        $client = JApplicationHelper::getClientInfo($clientId);
        $this->setState('client', $client);
        
        parent::populateState('group', 'asc');
    
    }

    /**
     * Method to get cache data
     *
     * @return array
     */
    public function getData()
    {

        if (empty($this->_data))
        {
            $cache = $this->getCache();
            $data = $cache->getAll();
            
            if ($data != false)
            {
                $this->_data = $data;
                $this->_total = count($data);
                
                if ($this->_total)
                {
                    // Apply custom ordering
                    $ordering = $this->getState('list.ordering');
                    $direction = ($this->getState('list.direction') == 'asc') ? 1 : - 1;
                    
                    jimport('joomla.utilities.arrayhelper');
                    $this->_data = JArrayHelper::sortObjects($data, $ordering, $direction);
                    
                    // Apply custom pagination
                    if ($this->_total > $this->getState('list.limit') && $this->getState('list.limit'))
                    {
                        $this->_data = array_slice($this->_data, $this->getState('list.start'), $this->getState('list.limit'));
                    }
                }
            }
            else
            {
                $this->_data = array();
            }
        }
        return $this->_data;
    
    }

    /**
     * Method to get cache instance
     *
     * @return object
     */
    public function getCache()
    {

        $conf = JFactory::getConfig();
        
        $options = array(
            'defaultgroup' => '',
            'storage' => $conf->get('cache_handler', ''),
            'caching' => true,
            'cachebase' => ($this->getState('clientId') == 1) ? JPATH_ADMINISTRATOR . '/cache' : $conf->get('cache_path', JPATH_SITE . '/cache')
        );
        
        $cache = JCache::getInstance('', $options);
        
        return $cache;
    
    }

    public function getGoogleAuth()
    {

        $session = JFactory::getSession();
        $app = JFactory::getApplication();
        
        $API_URL = 'https://www.googleapis.com/analytics/v3/data/ga';
        
        /* The form vars are absent during the google redirect hence this method is unreliable . Using sessions to add redundancy further use of db is to be ascertained as redundancy database support */
        
        $jfinput = $app->input->post->__call('getfilter', array(
            0 => 'jform',
            1 => null
        ));
        
        if (! isset($jfinput))
        {
            $jfinput = $app->input->__call('getfilter', array(
                0 => 'jform',
                1 => null
            ));
        }
        
        if (isset($jfinput['googleclientid']) && isset($jfinput['googleclientsecret']) && isset($jfinput['googleviewid']))
        {
            $jinput = new stdClass();
            foreach ($jfinput as $key => $value)
            {
                $jinput->$key = $value;
            }
            $sess_lnparam = serialize($jinput);
            $session->set('multicache_lnparam', $sess_lnparam);
            // var_dump($sess_lnparam);exit;
        }
        else
        {
            // check the sessions
            $session_lnparam = $session->get('multicache_lnparam');
            
            if (isset($session_lnparam))
            {
                
                $jinput = unserialize($session_lnparam);
            }
        }
        
        /*
         * else
         * {
         * $jinput = $this->getlnparams();//database redundant
         * }
         *
         */
        
        $client_id = $jinput->googleclientid;
        
        $client_secret = $jinput->googleclientsecret;
        $account_id = $jinput->googleviewid;
        if (empty($client_id) || empty($client_secret) || empty($account_id) || $account_id == 'ga:')
        {
            $credentails[] = empty($client_id) ? strtolower(JText::_('COM_MULTICACHE_FIELD_GOOGLE_CLIENT_ID_LABEL')) : '';
            $credentails[] = empty($client_secret) ? strtolower(JText::_('COM_MULTICACHE_FIELD_GOOGLE_CLIENT_SECRET_LABEL')) : '';
            $credentails[] = empty($account_id) || $account_id == 'ga:' ? strtolower(JText::_('COM_MULTICACHE_FIELD_GOOGLE_VIEW_ID_LABEL')) : '';
            
            if (! empty($credentails))
            {
                $credentails = array_filter($credentails);
                $credentails = implode(',	', $credentails);
            }
            $message = sprintf(JText::_("COM_MULTICACHE_GOOGLE_AUTHENTICATION_CREDENTIALS_EMPTY") . '	', $credentails);
            
            $app->redirect('index.php?option=com_multicache&view=config&layout=edit&id=1#page-parta', $message, 'notice');
        }
        $startdate = $jinput->googlestartdate;
        $enddate = $jinput->googleenddate;
        $maxresults = $jinput->googlenumberurlscache;
        
        $redirect_uri = substr(JURI::base(), 0, - 1) . '/index.php?option=com_multicache&view=lnobject';
        $this->urlfilter = (int) $jinput->urlfilters;
        $code = $app->input->get('code', null);
        $forceauth = $app->input->get('forceauth', null);
        $authobj = new MulticacheOauthClient();
        $authobj->setOption('authurl', 'https://accounts.google.com/o/oauth2/auth');
        $authobj->setOption('tokenurl', 'https://accounts.google.com/o/oauth2/token');
        $authobj->setOption('clientid', $client_id);
        $authobj->setOption('clientsecret', $client_secret);
        // $authobj->setOption('clientsecret', $account_id);
        $authobj->setOption('scope', array(
            'https://www.googleapis.com/auth/analytics.readonly'
        ));
        $authobj->setOption('redirecturi', $redirect_uri);
        $authobj->setOption('requestparams', array(
            'access_type' => 'offline',
            'approval_prompt' => 'auto'
        ));
        $authobj->setOption('sendheaders', true);
        $authobj->setOption('userefresh', true);
        // session_start();
        
        if (isset($forceauth)) $session->set('googleoauth_access_code', null);
        $session_google = $session->get('googleoauth_access_code');
        $token_created = $session_google['created'];
        $token_expires = $session_google['expires_in'];
        $now = microtime(true);
        if (! isset($session_google) || isset($session_google) && ($now > ($token_created + $token_expires)))
        {
            $session->set('googleoauth_access_code', null);
            $access_token = $authobj->authenticate();
            $session->set('googleoauth_access_code', $access_token);
            // $_SESSION['oauth_access_token'] = $access_token;
        }
        else
        {
            $authobj->setToken($session->get('googleoauth_access_code'));
        }
        
        if (! $authobj->isAuthenticated())
        {
            $token = $authobj->getToken();
            $token_created = $session_google['created'];
            $token_expires = $session_google['expires_in'];
            $now = microtime(true);
            if ($now > ($token_created + $token_expires))
            {
                $t = $now - ($token_created + $token_expires);
                $app->enqueueMessage(JText::_('COM_MULTICACHE_LNOBJECT_ERROR_TOKEN_EXPIRED') . '	' . $t . ' ' . JText::_('COM_MULTICACHE_LNOBJECT_ERROR_DESC_SECONDS_AGO'), 'error');
            }
            Return false;
        }
        
        if ($authobj->isAuthenticated())
        {
            
            $access_token = $authobj->getToken();
            $params = array(
                'ids' => $account_id,
                'metrics' => 'ga:pageviews',
                'dimensions' => 'ga:pagePath',
                'sort' => '-ga:pageviews',
                'start-date' => $jinput->googlestartdate,
                'end-date' => $jinput->googleenddate,
                'max-results' => $jinput->googlenumberurlscache,
                'access_token' => $access_token[access_token]
            );
            
            $authobj->setOption('getparam', http_build_query($params));
            $authobj->setOption('authmethod', 'get');
            
            $result = $authobj->query($API_URL, $params, array(), 'get');
            
            if ($result->code != 200)
            {
                $app->enqueueMessage(JText::_('COM_MULTICACHE_LNOBJECT_GOOGLE') . '	' . $result->code, 'error');
                
                if ($result['error']['errors'][0]['reason'] == 'authError')
                {
                    $session->clear('googleoauth_access_code');
                    $session->set('googleoauth_access_code', null);
                    $session->set('multicache_lnparam', null);
                    $session->clear('multicache_lnparam');
                    $app->close();
                    // $this->setRedirect(JRoute::_('index.php?option=com_multicache&view=lnobject&code=0'));
                }
                return;
            }
            $decoded_result = json_decode($result->body, true);
            // it is important to derive the root from Jroute over JURI to keep case sensitivity in cases wher live-site settings differ
            // $siteroot = substr(JURI::root(), 0, -1);
            $siteroot = JURI::getInstance()->toString(array(
                "scheme",
                "host"
            ));
            
            if (! stristr($siteroot, 'www.'))
            {
                $s_uri = JURI::getInstance();
                $siteroot2 = $s_uri->getScheme() . '://www.' . $s_uri->getHost();
            }
            else
            {
                $siteroot2 = str_ireplace('www.', '', $siteroot);
            }
            $rawurlarrayobj = array();
            foreach ($decoded_result['rows'] as $lobj)
            {
                $key = $siteroot . $lobj[0];
                $key2 = $siteroot2 . $lobj[0];
                $value = $lobj[1];
                if (isset($rawurlarrayobj[$key]))
                {
                    $rawurlarrayobj[$key] = $rawurlarrayobj[$key] + $value;
                }
                else
                {
                    $rawurlarrayobj[$key] = $value;
                }
                
                if (isset($rawurlarrayobj[$key2]))
                {
                    $rawurlarrayobj[$key2] = $rawurlarrayobj[$key2] + $value;
                }
                else
                {
                    $rawurlarrayobj[$key2] = $value;
                }
            }
            $urlobj = array();
            
            if ($this->urlfilter == 1)
            {
                
                foreach ($rawurlarrayobj as $key => $value)
                {
                    $newkey = strstr($key, '?', true);
                    if ($newkey)
                    {
                        
                        // check whether the key is already present in the object
                        if (isset($urlobj[$newkey]))
                        {
                            
                            $urlobj[$newkey] = $urlobj[$newkey] + $value;
                        }
                        else
                        {
                            
                            $urlobj[$newkey] = $value;
                        }
                    }
                    else
                    {
                        
                        $urlobj[$key] = $value;
                    }
                }
            }
            elseif ($this->urlfilter == 2)
            {
                foreach ($rawurlarrayobj as $key => $value)
                {
                    $newkey = strstr($key, '?', true);
                    if (empty($newkey))
                    {
                        $urlobj[$key] = $value;
                    }
                }
            }
            else
            {
                $urlobj = $rawurlarrayobj;
            }
            
            arsort($urlobj);
            $count = count($urlobj);
            $this->insertUarray($urlobj, $jinput);
            // $message = "Google Authentication Success. ".$count." urls retrieved and replaced! ";
            $message = sprintf(JText::_("COM_MULTICACHE_GOOGLE_AUTHENTICATION_SUCCESS"), $count);
            $app->redirect('index.php?option=com_multicache&view=config&layout=edit&id=1#page-parta', $message, 'message');
            Return $count;
        }
        else
        {
            $app->enqueueMessage(JText::_('COM_MULTICACHE_LNOBJECT_NOTAUTHENTICATED'), 'error');
            Return false;
        }
    
    }

    /**
     * Method to get client data
     *
     * @return array
     */
    public function getClient()
    {

        return $this->getState('client');
    
    }

    /**
     * Get the number of current Cache Groups
     *
     * @return int
     */
    public function getTotal()
    {

        if (empty($this->_total))
        {
            $this->_total = count($this->getData());
        }
        
        return $this->_total;
    
    }

    /**
     * Method to get a pagination object for the cache
     *
     * @return integer
     */
    public function getPagination()
    {

        if (empty($this->_pagination))
        {
            $this->_pagination = new JPagination($this->getTotal(), $this->getState('list.start'), $this->getState('list.limit'));
        }
        
        return $this->_pagination;
    
    }

    /**
     * Clean out a cache group as named by param.
     * If no param is passed clean all cache groups.
     *
     * @param String $group        
     */
    public function clean($group = '')
    {

        $cache = $this->getCache();
        $cache->clean($group);
    
    }

    public function cleanlist($array)
    {

        foreach ($array as $group)
        {
            $this->clean($group);
        }
    
    }

    public function purge()
    {

        $cache = JFactory::getCache();
        return $cache->gc();
    
    }

    protected function getCacheid($url, $group)
    {

        $obj = new JCacheStoragetemp();
        $cache_id = $obj->getCacheid($url, $group);
        
        Return $cache_id;
    
    }

    protected function insertUarray($uarray, $params = NULL, $type = 'google')
    {

        if (isset($params->frequency_distribution))
        {
            $total_views = array_sum($uarray);
        }
        $db = JFactory::getDbo();
        $query = $db->getQuery('true');
        $query->delete($db->quoteName('#__multicache_urlarray'));
        $query->where($db->quoteName('type') . ' = ' . $db->quote($type));
        $db->setQuery($query);
        $db->execute();
        
        // delete current goog objects
        foreach ($uarray as $key => $value)
        {
            $insertobj = new stdClass();
            $insertobj->url = $key;
            $insertobj->url_manifest = $key;
            $insertobj->cache_id = $this->getCacheid($key, 'page');
            $insertobj->views = $value;
            $insertobj->type = $type;
            $insertobj->created = date('Y-m-d');
            if (isset($params->frequency_distribution))
            {
                $insertobj->f_dist = $value / $total_views;
            }
            if (isset($params->natlogdist))
            {
                $insertobj->ln_dist = log($value);
            }
            
            $res = $db->insertObject('#__multicache_urlarray', $insertobj);
        }
    
    }

    protected function getlnparams()
    {

        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('*');
        $query->from($db->quoteName('#__multicache_config'));
        $query->where($db->quoteName('id') . ' = ' . $db->quote('1'));
        $db->setQuery($query);
        $res = $db->loadObject();
        Return $res;
    
    }

}