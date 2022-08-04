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

class MulticacheUri
{

    protected $uri = null;

    protected $scheme = null;

    protected $host = null;

    protected $port = null;

    protected $user = null;

    protected $pass = null;

    protected $path = null;

    protected $query = null;

    protected $fragment = null;

    protected static $base = array();

    protected static $root = array();

    protected static $current;

    protected static $instances = array();

    protected $vars = array();

    public function __construct($uri = null)
    {

        if (! is_null($uri))
        {
            $this->parse($uri);
        }
    
    }

    public static function getInstance($uri = 'SERVER')
    {

        if (empty(static::$instances[$uri]))
        {
            
            if ($uri == 'SERVER')
            {
                
                if (isset($_SERVER['HTTPS']) && ! empty($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) != 'off'))
                {
                    $https = 's://';
                }
                else
                {
                    $https = '://';
                }
                /*
                 * If PHP_SELF and REQUEST_URI
                 * are present, we will assume we are running on apache.
                 */
                if (! empty($_SERVER['PHP_SELF']) && ! empty($_SERVER['REQUEST_URI']))
                {
                    $theURI = 'http' . $https . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                }
                else
                {
                    $theURI = 'http' . $https . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];
                    if (isset($_SERVER['QUERY_STRING']) && ! empty($_SERVER['QUERY_STRING']))
                    {
                        $theURI .= '?' . $_SERVER['QUERY_STRING'];
                    }
                }
                $theURI = str_replace(array(
                    "'",
                    '"',
                    '<',
                    '>'
                ), array(
                    "%27",
                    "%22",
                    "%3C",
                    "%3E"
                ), $theURI);
            }
            else
            {
                
                $theURI = $uri;
            }
            self::$instances[$uri] = new MulticacheUri($theURI);
        }
        return self::$instances[$uri];
    
    }

    public function toString(array $parts = array('scheme', 'user', 'pass', 'host', 'port', 'path', 'query', 'fragment'))
    {

        $query = $this->getQuery();
        $uri = '';
        $uri .= in_array('scheme', $parts) ? (! empty($this->scheme) ? $this->scheme . '://' : '') : '';
        $uri .= in_array('user', $parts) ? $this->user : '';
        $uri .= in_array('pass', $parts) ? (! empty($this->pass) ? ':' : '') . $this->pass . (! empty($this->user) ? '@' : '') : '';
        $uri .= in_array('host', $parts) ? $this->host : '';
        $uri .= in_array('port', $parts) ? (! empty($this->port) ? ':' : '') . $this->port : '';
        $uri .= in_array('path', $parts) ? $this->path : '';
        $uri .= in_array('query', $parts) ? (! empty($query) ? '?' . $query : '') : '';
        $uri .= in_array('fragment', $parts) ? (! empty($this->fragment) ? '#' . $this->fragment : '') : '';
        
        return $uri;
    
    }

    public function getQuery($toArray = false)
    {

        if ($toArray)
        {
            return $this->vars;
        }
        
        if (is_null($this->query))
        {
            $this->query = self::buildQuery($this->vars);
        }
        
        return $this->query;
    
    }

    public function setQuery($query)
    {

        if (is_array($query))
        {
            $this->vars = $query;
        }
        else
        {
            if (strpos($query, '&amp;') !== false)
            {
                $query = str_replace('&amp;', '&', $query);
            }
            
            parse_str($query, $this->vars);
        }
        
        // Empty the query
        $this->query = null;
    
    }

    public function setVar($name, $value)
    {

        $tmp = isset($this->vars[$name]) ? $this->vars[$name] : null;
        
        $this->vars[$name] = $value;
        
        // Empty the query
        $this->query = null;
        
        return $tmp;
    
    }

    public static function isInternal($url)
    {

        if (strpos($url, '//') === 0)
        {
            // for checking purposes we add a scheme equivalent to our scheme
            $home_scheme = MulticacheUri::getInstance()->getScheme();
            $url = $home_scheme . ':' . $url;
        }
        $uri = self::getInstance($url);
        $base = $uri->toString(array(
            'scheme',
            'host',
            'port',
            'path'
        ));
        $host = $uri->toString(array(
            'scheme',
            'host',
            'port'
        ));
        
        if (stripos($base, self::base()) !== 0 && ! empty($host))
        {
            return false;
        }
        
        return true;
    
    }

    public function getVar($name, $default = null)
    {

        if (array_key_exists($name, $this->vars))
        {
            return $this->vars[$name];
        }
        
        return $default;
    
    }

    public function hasVar($name)
    {

        return array_key_exists($name, $this->vars);
    
    }

    public function getScheme()
    {

        return $this->scheme;
    
    }

    public static function buildQuery(array $params)
    {

        if (count($params) == 0)
        {
            return false;
        }
        
        return urldecode(http_build_query($params, '', '&'));
    
    }

    public static function base($pathonly = false)
    {
        // Get the base request path.
        if (empty(self::$base))
        {
            
            $live_site = ''; // for issues with base info not available on $_server we will have to ammend this to get the base
            
            if (trim($live_site) != '')
            {
                $uri = self::getInstance($live_site);
                self::$base['prefix'] = $uri->toString(array(
                    'scheme',
                    'host',
                    'port'
                ));
                self::$base['path'] = rtrim($uri->toString(array(
                    'path'
                )), '/\\');
            }
            else
            {
                $uri = self::getInstance();
                self::$base['prefix'] = $uri->toString(array(
                    'scheme',
                    'host',
                    'port'
                ));
                
                if (strpos(php_sapi_name(), 'cgi') !== false && ! ini_get('cgi.fix_pathinfo') && ! empty($_SERVER['REQUEST_URI']))
                {
                    // PHP-CGI on Apache with "cgi.fix_pathinfo = 0"
                    
                    // We shouldn't have user-supplied PATH_INFO in PHP_SELF in this case
                    // because PHP will not work with PATH_INFO at all.
                    $script_name = $_SERVER['PHP_SELF'];
                }
                else
                {
                    // Others
                    $script_name = $_SERVER['SCRIPT_NAME'];
                }
                
                self::$base['path'] = rtrim(dirname($script_name), '/\\');
            }
        }
        
        return $pathonly === false ? self::$base['prefix'] . self::$base['path'] . '/' : self::$base['path'];
    
    }

    public static function current()
    {
        // Get the current URL.
        if (empty(self::$current))
        {
            $uri = self::getInstance();
            self::$current = $uri->toString(array(
                'scheme',
                'host',
                'port',
                'path'
            ));
        }
        
        return self::$current;
    
    }

    public static function root($pathonly = false, $path = null)
    {

        if (empty(self::$root))
        {
            $uri = self::getInstance(self::base());
            self::$root['prefix'] = $uri->toString(array(
                'scheme',
                'host',
                'port'
            ));
            self::$root['path'] = rtrim($uri->toString(array(
                'path'
            )), '/\\');
        }
        
        // Get the scheme
        if (isset($path))
        {
            self::$root['path'] = $path;
        }
        
        return $pathonly === false ? self::$root['prefix'] . '/' : self::$root['path'];
    
    }

    public function getHost()
    {

        return $this->host;
    
    }

    protected function parse($uri)
    {

        $this->uri = $uri;
        
        /*
         * Parse the URI and populate the object fields. If URI is parsed properly,
         * set method return value to true.
         */
        $parts = $this->parse_url($uri);
        
        $retval = ($parts) ? true : false;
        
        // We need to replace &amp; with & for parse_str to work right...
        if (isset($parts['query']) && strpos($parts['query'], '&amp;'))
        {
            $parts['query'] = str_replace('&amp;', '&', $parts['query']);
        }
        
        $this->scheme = isset($parts['scheme']) ? $parts['scheme'] : null;
        $this->user = isset($parts['user']) ? $parts['user'] : null;
        $this->pass = isset($parts['pass']) ? $parts['pass'] : null;
        $this->host = isset($parts['host']) ? $parts['host'] : null;
        $this->port = isset($parts['port']) ? $parts['port'] : null;
        $this->path = isset($parts['path']) ? $parts['path'] : null;
        $this->query = isset($parts['query']) ? $parts['query'] : null;
        $this->fragment = isset($parts['fragment']) ? $parts['fragment'] : null;
        if (isset($parts['query']))
        {
            parse_str($parts['query'], $this->vars);
        }
        
        return $retval;
    
    }

    public function getPath()
    {

        return $this->path;
    
    }

    protected function parse_url($url)
    {

        $result = false;
        $entities = array(
            '%21',
            '%2A',
            '%27',
            '%28',
            '%29',
            '%3B',
            '%3A',
            '%40',
            '%26',
            '%3D',
            '%24',
            '%2C',
            '%2F',
            '%3F',
            '%23',
            '%5B',
            '%5D'
        );
        $replacements = array(
            '!',
            '*',
            "'",
            "(",
            ")",
            ";",
            ":",
            "@",
            "&",
            "=",
            "$",
            ",",
            "/",
            "?",
            "#",
            "[",
            "]"
        );
        $encodedURL = str_replace($entities, $replacements, urlencode($url));
        $encodedParts = parse_url($encodedURL);
        if ($encodedParts)
        {
            foreach ($encodedParts as $key => $value)
            {
                $result[$key] = urldecode(str_replace($replacements, $entities, $value));
            }
        }
        
        return $result;
    
    }

}