<?php

/**
 * flexContent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/flexContent
 * @copyright 2014 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\flexContent\Control\Command;

use Silex\Application;
use phpManufaktur\flexContent\Control\Configuration;
use phpManufaktur\flexContent\Data\Content\TagType;
use phpManufaktur\flexContent\Data\Content\Tag;

class Tools
{
    protected $app = null;
    protected $TagType = null;
    protected $Tag = null;
    protected static $config = null;

    /**
     * Constructor
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->TagType = new TagType($app);
        $this->Tag = new Tag($app);

        $Configuration = new Configuration($app);
        self::$config = $Configuration->getConfiguration();
    }

    /**
     * Get the permanent link base URL for the given language
     *
     * @param string $language
     * @return string
     */
    public function getPermalinkBaseURL($language)
    {
        return CMS_URL.str_ireplace('{language}', strtolower($language), self::$config['content']['permalink']['directory']);
    }

    /**
     * Get the permanet link base URL for the RSS Channels in the given language
     *
     * @param string $language
     * @return string
     */
    public function getRSSPermalinkBaseURL($language)
    {
        return CMS_URL.str_ireplace('{language}', strtolower($language), self::$config['rss']['permalink']['directory']);
    }

    /**
     * Highlight a search result
     *
     * @param string $word
     * @param string reference $content
     * @return string
     * @todo does not work proper - function disabled!
     */
    public function highlightSearchResult($word, &$content)
    {
        // does not work proper !!!
        return $content;

        if (!self::$config['search']['result']['highlight']) {
            return $content;
        }
    }

    /**
     * Replace all #tags within the content with links to this tags
     *
     * @param string reference $content
     * @param string $language
     * @return string
     */
    public function linkTags(&$content, $language)
    {
        if (!self::$config['content']['tag']['auto-link']['enabled'] || empty($content)) {
            return $content;
        }

        $link_replacement = self::$config['content']['tag']['auto-link']['replacement']['link'];
        $invalid_replacement = self::$config['content']['tag']['auto-link']['replacement']['invalid'];
        $unassigned_replacement = self::$config['content']['tag']['auto-link']['replacement']['unassigned'];
        $remove_sharp = self::$config['content']['tag']['auto-link']['remove-sharp'];
        $ellipsis = self::$config['content']['tag']['auto-link']['ellipsis'];

        $ignore_hashtags = array();
        preg_match_all('%(?!<a[^>]*?>)((\B##(\w{2,64}\b))|(\B#(\w{2,64}\b)))(?![^<]*?</a>)%i', $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            if (isset($match[5])) {
                if (((strlen($match[5]) === 3) || strlen($match[5]) === 6) &&
                    (preg_match('/([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})/i', $match[5]) === 1)) {
                    // ignore hexadecimal color codes ...
                    continue;
                }

                if (in_array($match[0], $ignore_hashtags)) {
                    // ignore this already processed hashtag!
                    continue;
                }

                $tag_name = str_replace('_', ' ', $match[5]);

                if (false !== ($tag = $this->TagType->selectByName($tag_name, $language))) {
                    if ($this->Tag->isAssigned($tag['tag_id'])) {
                        // replace #tag with a link
                        $search = array('{link}','{description}','{tag}');
                        $replace = array(
                            $this->getPermalinkBaseURL($language).'/buzzword/'.$tag['tag_permalink'],
                            (!empty($tag['tag_description'])) ? $this->app['utils']->Ellipsis($tag['tag_description'], $ellipsis) : $tag['tag_name'],
                            ($remove_sharp) ? $tag['tag_name'] : '#'.$tag['tag_name']
                        );
                        $tag_link = str_ireplace($search, $replace, $link_replacement);
                    }
                    else {
                        // this #tag is not assigned with any content
                        $tag_link = str_ireplace('{tag}', '#'.$tag_name, $unassigned_replacement);
                    }
                }
                else {
                    // invalid #tag
                    $tag_link = str_ireplace('{tag}', '#'.$tag_name, $invalid_replacement);
                }
                $content = str_replace($match[0], $tag_link, $content);
            }
            else {
                // this is a ## marked item which has to be ignored - so we remove only the first # ...
                $ignore_hashtags[] = substr($match[0], 1);
                $content = str_replace($match[0], substr($match[0], 1), $content);
            }
        }
        return $content;
    }

}
