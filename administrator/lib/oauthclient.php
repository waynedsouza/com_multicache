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

class MulticacheOauthClient extends JOAuth2Client
{

    public static $_cacheid = NULL;

    public function __construct(JRegistry $options = null, JHttp $http = null, JInput $input = null, JApplicationWeb $application = null)
    {

        parent::__construct($options, $http, $input, $application);
    
    }

    /**
     * Get the access token or redict to the authentication URL.
     *
     * @return string The access token
     *        
     * @since 12.3
     * @throws RuntimeException
     */
    public function authenticate()
    {

        if ($data['code'] = $this->input->get('code', false, 'raw'))
        {
            $data['grant_type'] = 'authorization_code';
            $data['redirect_uri'] = $this->getOption('redirecturi');
            $data['client_id'] = $this->getOption('clientid');
            $data['client_secret'] = $this->getOption('clientsecret');
            $response = $this->http->post($this->getOption('tokenurl'), $data);
            if ($response->code >= 200 && $response->code < 400)
            {
                if ($response->headers['Content-Type'] == 'application/json' || $response->headers['Content-Type'] == 'application/json; charset=utf-8')
                {
                    $token = array_merge(json_decode($response->body, true), array(
                        'created' => time()
                    ));
                }
                else
                {
                    parse_str($response->body, $token);
                    $token = array_merge($token, array(
                        'created' => time()
                    ));
                }
                $this->setToken($token);
                return $token;
            }
            else
            {
                throw new RuntimeException('Error code ' . $response->code . ' received requesting access token: ' . $response->body . '.');
            }
        }
        if ($this->getOption('sendheaders'))
        {
            $this->application->redirect($this->createUrl());
        }
        return false;
    
    }

    /**
     * Send a signed Oauth request.
     *
     * @param string $url
     *        The URL forf the request.
     * @param mixed $data
     *        The data to include in the request
     * @param array $headers
     *        The headers to send with the request
     * @param string $method
     *        The method with which to send the request
     * @param int $timeout
     *        The timeout for the request
     *        
     * @return string The URL.
     *        
     * @since 12.3
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function query($url, $data = null, $headers = array(), $method = 'get', $timeout = null)
    {

        $token = $this->getToken();
        if (array_key_exists('expires_in', $token) && $token['created'] + $token['expires_in'] < time() + 20)
        {
            if (! $this->getOption('userefresh'))
            {
                return false;
            }
            $token = $this->refreshToken($token['refresh_token']);
        }
        if (! $this->getOption('authmethod') || $this->getOption('authmethod') == 'bearer')
        {
            $headers['Authorization'] = 'Bearer ' . $token['access_token'];
        }
        elseif ($this->getOption('authmethod') == 'get')
        {
            if (strpos($url, '?'))
            {
                $url .= '&';
            }
            else
            {
                $url .= '?';
            }
            $url .= $this->getOption('getparam') ? $this->getOption('getparam') : 'access_token';
            // $url .= '=' . $token['access_token'];
        }
        switch ($method)
        {
            case 'head':
            case 'get':
            case 'delete':
            case 'trace':
                $response = $this->http->$method($url, $headers, $timeout);
                break;
            case 'post':
            case 'put':
            case 'patch':
                $response = $this->http->$method($url, $data, $headers, $timeout);
                break;
            default:
                throw new InvalidArgumentException('Unknown HTTP request method: ' . $method . '.');
        }
        if ($response->code < 200 || $response->code >= 400)
        {
            throw new RuntimeException('Error code ' . $response->code . ' received requesting data: ' . $response->body . '.');
        }
        return $response;
    
    }

}

?>