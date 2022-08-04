<?php

/**
 * Class HTML_Optimize
 */

/**
 * Compress HTML
 *
 * by Stephen Clay <steve@mrclay.org>
 */
JLoader::register('MulticacheOptimize', JPATH_ROOT . '/administrator/components/com_multicache/lib/compression_libs/multicacheoptimize.php');
JLoader::register('MulticacheJSOptimize', JPATH_ROOT . '/administrator/components/com_multicache/lib/compression_libs/multicachejsoptimize.php');
JLoader::register('MulticacheCSSOptimize', JPATH_ROOT . '/administrator/components/com_multicache/lib/compression_libs/multicachecssoptimize.php');

class MulticacheHtmlMinify extends MulticacheOptimize
{

    protected $_isXhtml = null;

    protected $_isHtml5 = null;

    protected $_replacementHash = null;

    protected $_placeholders = array();

    protected $_cssMinifier = null;

    protected $_jsMinifier = null;

    protected $_html = '';

    protected $_sMinifyLevel = 0;

    protected $_js_comments = false;

    protected $_css_comments = false;

    /**
     * "Minify" an HTML page
     *
     * @param string $html        
     *
     * @param array $options
     *        'cssMinifier' : (optional) callback function to process content of STYLE
     *        elements.
     *        
     *        'jsMinifier' : (optional) callback function to process content of SCRIPT
     *        elements. Note: the type attribute is ignored.
     *        
     *        'xhtml' : (optional boolean) should content be treated as XHTML1.0? If
     *        unset, minify will sniff for an XHTML doctype.
     *        
     * @return string
     */
    public static function process($html, $options = array())
    {

        $min = new MulticacheHtmlMinify($html, $options);
        return $min->_process();
    
    }

    /**
     * Create a minifier object
     *
     * @param string $html        
     *
     * @param array $options
     *        'cssMinifier' : (optional) callback function to process content of STYLE
     *        elements.
     *        
     *        'jsMinifier' : (optional) callback function to process content of SCRIPT
     *        elements. Note: the type attribute is ignored.
     *        
     *        'xhtml' : (optional boolean) should content be treated as XHTML1.0? If
     *        unset, minify will sniff for an XHTML doctype.
     *        
     * @return null
     */
    public function __construct($html, $options = array())
    {

        $this->_html = $html;
        
        if (isset($options['xhtml']))
        {
            $this->_isXhtml = (bool) $options['xhtml'];
        }
        if (isset($options['html5']))
        {
            $this->_isHtml5 = (bool) $options['html5'];
        }
        if (isset($options['cssMinifier']))
        {
            $this->_cssMinifier = $options['cssMinifier'];
        }
        if (isset($options['jsMinifier']))
        {
            $this->_jsMinifier = $options['jsMinifier'];
        }
        if (isset($options['js_comments']))
        {
            $this->_js_comments = $options['js_comments'];
        }
        if (isset($options['css_comments']))
        {
            $this->_css_comments = $options['css_comments'];
        }
        
        $this->_sMinifyLevel = isset($options['minify_level']) ? $options['minify_level'] : 0;
    
    }

    /**
     * Minify the markeup given in the constructor
     *
     * @return string
     */
    private function _process()
    {

        if ($this->_isXhtml === null)
        {
            // $this->_isXhtml = (preg_match('#^\s*+<!DOCTYPE[^X>]++XHTML#i', $this->_html));
            $this->_isXhtml = (false !== strpos($this->_html, '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML'));
        }
        
        if ($this->_isHtml5 === null)
        {
            $this->_isHtml5 = (preg_match('#^\s*+<!DOCTYPE html>#i', $this->_html));
        }
        
        $x = '<!--(?>-?[^-]*+)*?--!?>';
        $s1 = self::DOUBLE_QUOTE_STRING;
        $s2 = self::SINGLE_QUOTE_STRING;
        
        // Regex for escape elements
        $pr = "<pre\b[^>]*+>(?><?[^<]*+)*?</pre>";
        $sc = "<script\b[^>]*+>(?><?[^<]*+)*?</script>";
        $st = "<style\b[^>]*+>(?><?[^<]*+)*?</style>";
        $tx = "<textarea\b[^>]*+>(?><?[^<]*+)*?</textarea>";
        
        if (! ($this->_js_comments || $this->_css_comments))
        {
            // Remove comments (not containing IE conditional comments)
            $this->_html = preg_replace("#(?><?[^<]*+(?>$sc|$st|<!--\[(?><?[^<]*+)*?" . "<!\s*\[(?>-?[^-]*+)*?--!?>|<!DOCTYPE[^>]++>)?)*?\K(?:$x|$)#i", '', $this->_html);
        }
        
        // Reduce runs of whitespace outside all elements to one
        $this->_html = preg_replace("#(?>[^<]*+(?:$sc|$st|$x|<(?>[^>'\"]*+(?:$s1|$s2)?)*?>)?)*?\K" . '(?:[\t\f ]++(?=[\r\n]\s*+<)|(?>\r?\n|\r)\K\s++(?=<)|[\t\f]++(?=[ ]\s*+<)|[\t\f]\K\s*+(?=<)|[ ]\K\s*+(?=<)|$)#i', '', $this->_html);
        
        // Minify scripts
        $this->_html = preg_replace_callback("#(?><?[^<]*+(?:$x)?)*?\K" . "(?:(<script\b[^>]*+>)((?><?[^<]*+)*?)(</script>)|" . "(<style\b[^>]*+>)((?><?[^<]*+)*?)(</style>)|$)#i", array(
            $this,
            '_minifyCB'
        ), $this->_html);
        
        if ($this->_sMinifyLevel < 1)
        {
            return trim($this->_html);
        }
        
        // Replace line feed with space (legacy)
        $this->_html = preg_replace("#(?>[^<]*+(?:<script\b[^>]*+>(?><?[^<]*+)*?</script>|$x|<(?>[^>'\"]*+(?:$s1|$s2)?)*?>)?)*?\K" . '(?:[\r\n\t\f]++(?=<)|$)#i', ' ', $this->_html);
        
        // remove ws around block elements preserving space around inline elements
        // block/undisplayed elements
        $b = 'address|article|aside|audio|body|blockquote|canvas|dd|div|dl' . '|fieldset|figcaption|figure|footer|form|h[1-6]|head|header|hgroup|html|noscript|ol|output|p' . '|pre|section|style|table|title|tfoot|ul|video';
        
        // self closing block/undisplayed elements
        $b2 = 'base|meta|link|hr';
        
        // inline elements
        $i = 'b|big|i|small|tt' . '|abbr|acronym|cite|code|dfn|em|kbd|strong|samp|var' . '|a|bdo|br|map|object|q|script|span|sub|sup' . '|button|label|select|textarea';
        
        // self closing inline elements
        $i2 = 'img|input';
        
        $this->_html = preg_replace("#(?>\s*+(?:$x|<(?:(?>$i)\b[^>]*+>|(?:/(?>$i)\b>|(?>$i2)\b[^>]*+>)\s*+)|<[^>]*+>)|[^<]++)*?\K" . "(?:\s++(?=<(?>$b|$b2)\b)|(?:</(?>$b)\b>|<(?>$b2)\b[^>]*+>)\K\s++(?!<(?>$i|$i2)\b)|$)#i", '', $this->_html);
        
        // Replace runs of whitespace inside elements with single space escaping pre, textarea, scripts and style elements
        // elements to escape
        $e = 'pre|script|style|textarea';
        
        $this->_html = preg_replace("#(?>[^<]*+(?:$pr|$sc|$st|$tx|$x|<[^>]++>[^<]*+))*?(?:(?:<(?!$e|!)[^>]*+>)?(?>\s?[^\s<]*+)*?\K\s{2,}|\K$)#i", ' ', $this->_html);
        
        // Remove additional ws around attributes
        $this->_html = preg_replace("#(?>\s?(?>[^<>]*+(?:<!(?!DOCTYPE)(?>>?[^>]*+)*?>[^<>]*+)?<|(?=[^<>]++>)[^\s>'\"]++(?>$s1|$s2)?|[^<]*+))*?\K" . "(?>\s\s++|$)#i", ' ', $this->_html);
        
        if ($this->_sMinifyLevel < 2)
        {
            
            return trim($this->_html);
        }
        
        // remove redundant attributes
        $this->_html = preg_replace("#(?:(?=[^<>]++>)|(?><?[^<]*+(?>$x|<(?!(?:script|style|link)|/html>))?)*?<(?:(?:script|style|link)|/html>))(?>[ ]?[^ >]*+)*?\K" . '(?: (?:type|language)=["\']?(?:(?:text|application)/(?:javascript|css)|javascript)["\']?|[^<]*+\K$)#i', '', $this->_html);
        
        $j = '<input type="hidden" name="[0-9a-f]{32}" value="1" />';
        
        // Remove quotes from selected attributes
        if ($this->_isHtml5)
        {
            $ns1 = '"[^"\'`=<>\s]*+(?:[\'`=<>\s]|(?<=\\\\)")(?>(?:(?<=\\\\)")?[^"]*+)*?(?<!\\\\)"';
            $ns2 = "'[^'\"`=<>\s]*+(?:[\"`=<>\s]|(?<=\\\\)')(?>(?:(?<=\\\\)')?[^']*+)*?(?<!\\\\)'";
            
            $this->_html = preg_replace("#(?:(?=[^>]*+>)|<[a-z0-9]++ )" . "(?>[=]?[^=><]*+(?:=(?:$ns1|$ns2)|>(?>[^<]*+(?:$j|$x|<(?![a-z0-9]++ ))?)*?(?:<[a-z0-9]++ |$))?)*?" . "(?:=\K([\"'])([^\"'`=<>\s]++)\g{1}[ ]?|\K$)#i", '$2 ', $this->_html);
        }
        
        // Remove last whitespace in open tag
        $this->_html = preg_replace("#(?>[^<]*+(?:$j|$x|<(?![a-z0-9]++))?)*?(?:<[a-z0-9]++(?>\s*+[^\s>]++)*?\K" . "(?:\s*+(?=>)|(?<=[\"'])\s++(?=/>))|$\K)#i", '', $this->_html);
        
        return trim($this->_html);
    
    }

    /**
     *
     * @param type $m        
     * @return type
     */
    protected function _minifyCB($m)
    {

        if ($m[0] == '')
        {
            return $m[0];
        }
        
        $openTag = isset($m[1]) && $m[1] != '' ? $m[1] : (isset($m[4]) && $m[4] != '' ? $m[4] : '');
        $content = isset($m[2]) && $m[2] != '' ? $m[2] : (isset($m[5]) && $m[5] != '' ? $m[5] : '');
        $closeTag = isset($m[3]) && $m[3] != '' ? $m[3] : (isset($m[6]) && $m[6] != '' ? $m[6] : '');
        
        if (trim($content) == '')
        {
            
            return $m[0];
        }
        
        $type = stripos($openTag, 'script') == 1 ? 'js' : 'css';
        
        if ($this->{'_' . $type . 'Minifier'})
        {
            
            // remove HTML comments (and preceding "//" if present) and CDATA
            $content = self::cleanScript($content, $type);
            
            // minify
            $content = $this->_callMinifier($this->{'_' . $type . 'Minifier'}, $content);
            
            return $this->_needsCdata($content) ? "{$openTag}/*<![CDATA[*/{$content}/*]]>*/{$closeTag}" : "{$openTag}{$content}{$closeTag}";
        }
        else
        {
            return $m[0];
        }
    
    }

    /**
     *
     * @param type $str        
     * @return type
     */
    protected function _needsCdata($str)
    {

        return ($this->_isXhtml && preg_match('#(?:[<&]|\-\-|\]\]>)#', $str));
    
    }

    /**
     *
     * @param type $aFunc        
     * @param type $content        
     * @return type
     */
    protected function _callMinifier($aFunc, $content)
    {

        $class = $aFunc[0];
        $method = $aFunc[1];
        
        return $class::$method($content);
    
    }

    /**
     */
    public static function cleanScript($content, $type)
    {

        $s1 = self::DOUBLE_QUOTE_STRING;
        $s2 = self::SINGLE_QUOTE_STRING;
        $b = self::BLOCK_COMMENTS;
        $l = self::LINE_COMMENTS;
        
        $c = '(?:(?:<!--|-->)[^\r\n]*+)|(?:<!\[CDATA\[|\]\]>)';
        
        if ($type == 'css')
        {
            return preg_replace("#(?>[<\]\-]?[^'\"<\]\-/]*+(?>$s1|$s2|$b|$l|/)?)*?\K(?:$c|$)#i", '', $content);
        }
        else
        {
            $content = MulticacheJSOptimize::process($content, array(
                'prepare_only' => TRUE
            ));
            $r = $GLOBALS['REGEXP_LITERAL'];
            
            return preg_replace("#(?>[<\]\-]?[^'\"<\]\-/]*+(?>$s1|$s2|$b|$l|$r|/)?)*?\K(?:$c|$)#i", '', $content);
        }
    
    }

}