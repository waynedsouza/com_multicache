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

class MulticacheApplication
{

    public $charSet = 'utf-8';

    public $mimeType = 'text/html';

    public $modifiedDate;

    public $client;

    protected $response;
    // true
    protected static $instance;
    // true
    protected $input = null;
    // true
    protected $config = null;
    // true
    public function __construct($input = null, $config = null, $client = null)
    {
        // If a input object is given use it.
        if ($input instanceof MulticacheInput)
        {
            $this->input = $input;
        }
        
        // If a config object is given use it.
        if ($config instanceof MulticacheConfig)
        {
            $this->config = $config;
        }
        else
        {
            $this->config = MulticacheFactory::getConfig();
        }
        
        // If a client object is given use it.
        if ($client instanceof MulticacheClient)
        {
            $this->client = $client;
        }
        
        if (empty($acceptEncoding) && isset($_SERVER['HTTP_ACCEPT_ENCODING']))
        {
            $this->acceptEncoding = $_SERVER['HTTP_ACCEPT_ENCODING'];
        }
        if (! empty($this->acceptEncoding))
        {
            $this->encodings = array_map('trim', (array) explode(',', $this->acceptEncoding));
        }
        $this->set('execution.datetime', gmdate('Y-m-d H:i:s'));
        $this->set('execution.timestamp', time());
        // Setup the response object.
        $this->response = new stdClass();
        $this->response->cachable = false;
        $this->response->headers = array();
        $this->response->body = array();
    
    }

    public static function getInstance()
    {
        // Only create the object if it doesn't exist.
        if (empty(self::$instance))
        {
            
            self::$instance = new MulticacheApplication();
        }
        return self::$instance;
    
    }

    public function toString($compress = false)
    {

        if ($compress && ! ini_get('zlib.output_compression') && ini_get('output_handler') != 'ob_gzhandler')
        {
            $this->compress();
        }
        if ($this->allowCache() === false)
        {
            $this->setHeader('Cache-Control', 'no-cache', false);
            // HTTP 1.0
            $this->setHeader('Pragma', 'no-cache');
        }
        $this->sendHeaders();
        return $this->getBody();
    
    }

    public function allowCache($allow = null)
    {

        if ($allow !== null)
        {
            $this->response->cachable = (bool) $allow;
        }
        return $this->response->cachable;
    
    }

    protected function compress()
    {
        // Supported compression encodings.
        $supported = array(
            'x-gzip' => 'gz',
            'gzip' => 'gz',
            'deflate' => 'deflate'
        );
        // Get the supported encoding.
        if (! isset($this->encodings))
        {
            return;
        }
        $encodings = array_intersect($this->encodings, array_keys($supported));
        // If no supported encoding is detected do nothing and return.
        if (empty($encodings))
        {
            return;
        }
        // Verify that headers have not yet been sent, and that our connection is still alive.
        if ($this->checkHeadersSent() || ! $this->checkConnectionAlive())
        {
            return;
        }
        // Iterate through the encodings and attempt to compress the data using any found supported encodings.
        foreach ($encodings as $encoding)
        {
            if (($supported[$encoding] == 'gz') || ($supported[$encoding] == 'deflate'))
            {
                // Verify that the server supports gzip compression before we attempt to gzip encode the data.
                // @codeCoverageIgnoreStart
                if (! extension_loaded('zlib') || ini_get('zlib.output_compression'))
                {
                    continue;
                }
                // @codeCoverageIgnoreEnd
                // Attempt to gzip encode the data with an optimal level 4.
                $data = $this->getBody();
                $gzdata = gzencode($data, 4, ($supported[$encoding] == 'gz') ? FORCE_GZIP : FORCE_DEFLATE);
                // If there was a problem encoding the data just try the next encoding scheme.
                // @codeCoverageIgnoreStart
                if ($gzdata === false)
                {
                    continue;
                }
                // @codeCoverageIgnoreEnd
                // Set the encoding headers.
                $this->setHeader('Content-Encoding', $encoding);
                // NB: content length overidden: if this is to be removed the last content length in setbuffers shoul be removed
                // $this->setHeader('Content-Length', strlen($gzdata));
                // Header will be removed at 4.0
                if ($this->get('MetaVersion'))
                {
                    $this->setHeader('X-Content-Encoded-By', 'Wordpress');
                }
                // Replace the output with the encoded data.
                $this->setBody($gzdata);
                // Compression complete, let's break out of the loop.
                break;
            }
        }
    
    }

    public function setHeader($name, $value, $replace = false)
    {
        // Sanitize the input values.
        $name = (string) $name;
        $value = (string) $value;
        // If the replace flag is set, unset all known headers with the given name.
        if ($replace)
        {
            foreach ($this->response->headers as $key => $header)
            {
                if ($name == $header['name'])
                {
                    unset($this->response->headers[$key]);
                }
            }
            // Clean up the array as unsetting nested arrays leaves some junk.
            $this->response->headers = array_values($this->response->headers);
        }
        // Add the header to the internal array.
        $this->response->headers[] = array(
            'name' => $name,
            'value' => $value
        );
        return $this;
    
    }

    public function getHeaders()
    {

        return $this->response->headers;
    
    }

    public function clearHeaders()
    {

        $this->response->headers = array();
        return $this;
    
    }

    public function sendHeaders()
    {

        if (! $this->checkHeadersSent())
        {
            foreach ($this->response->headers as $header)
            {
                if ('status' == strtolower($header['name']))
                {
                    // 'status' headers indicate an HTTP status, and need to be handled slightly differently
                    $this->header(ucfirst(strtolower($header['name'])) . ': ' . $header['value'], null, (int) $header['value']);
                }
                else
                {
                    $this->header($header['name'] . ': ' . $header['value']);
                }
            }
        }
        return $this;
    
    }

    public function setBody($content)
    {

        $this->response->body = array(
            (string) $content
        );
        return $this;
    
    }

    public function getBody($asArray = false)
    {

        return $asArray ? $this->response->body : implode((array) $this->response->body);
    
    }

    public function close($id = 0)
    {

        exit($id);
    
    }

    public function get($key, $default = null)
    {

        return $this->config->getC($key, $default);
    
    }

    protected function checkConnectionAlive()
    {

        return (connection_status() === CONNECTION_NORMAL);
    
    }

    protected function checkHeadersSent()
    {

        return headers_sent();
    
    }

    protected function header($string, $replace = true, $code = null)
    {

        header($string, $replace, $code);
    
    }

    public function set($key, $value = null)
    {

        $previous = $this->config->getC($key);
        $this->config->setC($key, $value);
        
        return $previous;
    
    }

    public function isSSLConnection()
    {

        return ((isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on')) || getenv('SSL_PROTOCOL_VERSION'));
    
    }

}