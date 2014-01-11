<?php

/**
 * flexContent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/event
 * @copyright 2014 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\flexContent\Control\Command;

use Silex\Application;
use phpManufaktur\flexContent\Control\Configuration;

class Tools
{
    protected $app = null;
    protected static $config = null;

    /**
     * Constructor
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $Configuration = new Configuration($app);
        self::$config = $Configuration->getConfiguration();
    }

    /**
     * Highlight a search result
     *
     * @param string $word
     * @param string reference $content
     * @return string
     */
    public function highlightSearchResult($word, &$content)
    {
        if (!self::$config['search']['result']['highlight']) {
            return $content;
        }
        $replacement = self::$config['search']['result']['replacement'];
        $content = str_ireplace($word, str_ireplace('{word}', $word, $replacement), $content);
        return $content;
    }

    protected function replaceTags(\DOMNode $DOM, $excludeParents=array())
    {
        if (!empty($DOM->childNodes)) {
            foreach ($DOM->childNodes as $node) {
                if ($node instanceof \DOMText &&
                    !in_array($node->parentNode->nodeName, $excludeParents)) {

                    $node->nodeValue = $node->nodeValue;
                    //$node->nodeValue = preg_replace($regex, $replacement, $node->nodeValue);
                }
                else {
                    $this->replaceTags($DOM, $excludeParents);
                }
            }
        }
    }

    public function linkTags(&$content)
    {
        if (empty($content)) {
            return $content;
        }

        $DOM = new \DOMDocument();
        // loadXml needs properly formatted documents, so it's better to use loadHtml, but it needs a hack to properly handle UTF-8 encoding
        $DOM->loadHtml(mb_convert_encoding($content, 'HTML-ENTITIES', "UTF-8"));

        $XPath = new \DOMXPath($DOM);

        foreach($XPath->query('//text()[not(ancestor::a)]') as $node) {
            preg_match_all('/\x23([\w-]{1,64})/i', $node->wholeText, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                // $match[0] = #tag
                // $match[1] = tag
                 
                /*
                $replaced = str_ireplace('match this text', 'MATCH', $node->wholeText);
                $newNode  = $DOM->createDocumentFragment();
                $newNode->appendXML($replaced);
                $node->parentNode->replaceChild($newNode, $node);
                */
            }
        }

        $content = mb_substr($DOM->saveXML($XPath->query('//body')->item(0)), 6, -7, "UTF-8");
        return $content;
    }

}
