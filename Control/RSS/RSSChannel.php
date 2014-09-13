<?php

/**
 * flexContent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/flexContent
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\flexContent\Control\RSS;

use Silex\Application;
use phpManufaktur\flexContent\Control\Configuration;
use phpManufaktur\flexContent\Data\Content\RSSChannel as RSSChannelData;
use phpManufaktur\flexContent\Control\Command\Tools;
use Carbon\Carbon;

class RSSChannel
{
    protected $app = null;
    protected $RSSChannelData = null;
    protected $Tools = null;
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

        $this->RSSChannelData = new RSSChannelData($app);
        $this->Tools = new Tools($app);
    }

    /**
     * Get a image for the RSS Channel. Will optimize and create a new image
     * if necessary
     *
     * @param string $origin_image relative path to FRAMEWORK_PATH
     * @param integer $channel_id the RSS Channel ID
     * @return boolean|string false or URL of the image to use
     */
    protected function getRSSChannelImageURL($origin_image, $channel_id)
    {
        if (!$this->app['filesystem']->exists(FRAMEWORK_PATH.$origin_image)) {
            return false;
        }

        $max_width = self::$config['rss']['channel']['image']['max_width'];
        $max_height = self::$config['rss']['channel']['image']['max_height'];

        $image_info = $this->app['image']->getImageInfo(FRAMEWORK_PATH.$origin_image);

        if (($image_info['width'] > $max_width) || ($image_info['height'] > $max_height)) {
            // optimize the image
            $new_size = $this->app['image']->reCalculateImage($image_info['width'], $image_info['height'], $max_width, $max_height);
            // create a new filename
            $pathinfo = pathinfo($origin_image);
            $image = sprintf('%s_%dx%d.%s', $pathinfo['filename'],
                $new_size['width'], $new_size['height'], $pathinfo['extension']);
            $tweak_path = FRAMEWORK_PATH.'/media/public/rss/'.$channel_id.'/';
            $tweak_url = FRAMEWORK_URL.'/media/public/rss/'.$channel_id.'/';
            if (!$this->app['filesystem']->exists($tweak_path.$image) ||
            (filemtime($tweak_path.$image) != $image_info['last_modified'])) {
                // create a resampled image
                $this->app['image']->resampleImage(FRAMEWORK_PATH.$origin_image, $image_info['image_type'],
                    $image_info['width'], $image_info['height'], $tweak_path.$image,
                    $new_size['width'], $new_size['height']);
            }
            // return the new image URL
            return $tweak_url.$image;
        }
        else {
            return FRAMEWORK_URL.$origin_image;
        }
    }

    /**
     * Get the XML RSS Feed for the given RSS Channel ID
     *
     * @param integer $channel_id
     * @return boolean|string
     */
    public function getRSSChannelXML($channel_id)
    {
        $channel_data = array();
        $channel_items = array();

        $limit = self::$config['rss']['channel']['limit'];

        if (!$this->RSSChannelData->selectChannel($channel_id, $limit, $channel_data, $channel_items)) {
            return false;
        }

        $Tools = new Tools($this->app);

        // now we create the XML RSS Feed
        $xml = new \DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;

        $rss = $xml->createElement('rss');
        $rss->setAttribute('version', '2.0');
        $rss->setAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
        $xml->appendChild($rss);

        $channel = $xml->createElement('channel');
        $rss->appendChild($channel);

        $child  = $xml->createElement('atom:link');
        $child->setAttribute('href', $Tools->getRSSPermalinkBaseURL($channel_data['language']).'/'.$channel_data['channel_link']);
        $child->setAttribute('rel', 'self');
        $child->setAttribute('type', 'application/rss+xml');
        $channel->appendChild($child);

        $child = $xml->createElement('title', $channel_data['channel_title']);
        $channel->appendChild($child);
        $child = $xml->createElement('link', $Tools->getRSSPermalinkBaseURL($channel_data['language']).'/'.$channel_data['channel_link']);
        $channel->appendChild($child);
        $child = $xml->createElement('description', $channel_data['channel_description']);
        $channel->appendChild($child);
        $child = $xml->createElement('language', strtolower($channel_data['language']));
        $channel->appendChild($child);

        if (!empty($channel_data['channel_image']) && $this->app['filesystem']->exists(FRAMEWORK_PATH.$channel_data['channel_image'])) {
            $channel_image = $this->getRSSChannelImageURL($channel_data['channel_image'], $channel_data['channel_id']);
            $image = $xml->createElement('image');
            $channel->appendChild($image);
            $child = $xml->createElement('url', $channel_image);
            $image->appendChild($child);
            $child = $xml->createElement('title', $channel_data['channel_title']);
            $image->appendChild($child);
            $child = $xml->createElement('link', $Tools->getRSSPermalinkBaseURL($channel_data['language']).'/'.$channel_data['channel_link']);
            $image->appendChild($child);
        }

        if (!empty($channel_data['channel_category'])) {
            $child = $xml->createElement('category', $channel_data['channel_category']);
            $channel->appendChild($child);
        }

        if (!empty($channel_data['channel_copyright'])) {
            $child = $xml->createElement('copyright', $channel_data['channel_copyright']);
            $channel->appendChild($child);
        }

        $child = $xml->createElement('generator', 'kitFramework::flexContent');
        $channel->appendChild($child);

        if (!empty($channel_data['channel_webmaster'])) {
            $child = $xml->createElement('webMaster', sprintf('%s (Webmaster)', $channel_data['channel_webmaster']));
            $channel->appendChild($child);
        }

        $carbon = Carbon::create();
        $child = $xml->createElement('lastBuildDate', $carbon->format(Carbon::RSS));
        $channel->appendChild($child);

        if (isset($channel_items[0]['publish_from'])) {
            $carbon = Carbon::createFromFormat('Y-m-d H:i:s', $channel_items[0]['publish_from']);
            $child = $xml->createElement('pubDate', $carbon->format(Carbon::RSS));
            $channel->appendChild($child);
        }

        $max_width = self::$config['rss']['channel']['image']['max_width'];
        $max_height = self::$config['rss']['channel']['image']['max_height'];

        // loop through the contents and create the RSS items
        foreach ($channel_items as $content) {
            $item = $xml->createElement('item');
            $channel->appendChild($item);

            $child = $xml->createElement('title', $content['title']);
            $item->appendChild($child);
            $child = $xml->createElement('link', $Tools->getPermalinkBaseURL($channel_data['language']).'/'.$content['permalink'].'?ref=rss');
            $item->appendChild($child);
            $child = $xml->createElement('guid', $Tools->getPermalinkBaseURL($channel_data['language']).'/'.$content['permalink']);
            $item->appendChild($child);

            $description = strip_tags($content['teaser']);

            $carbon = Carbon::createFromFormat('Y-m-d H:i:s', $channel_items[0]['publish_from']);
            $child = $xml->createElement('pubDate', $carbon->format(Carbon::RSS));
            $item->appendChild($child);

            if (!empty($content['teaser_image']) && $this->app['filesystem']->exists(FRAMEWORK_PATH.$content['teaser_image'])) {
                $image = $this->getRSSChannelImageURL($content['teaser_image'], $channel_data['channel_id']);
                $image_tag = sprintf('<img src="%s" hspace="5" align="left">', $image);
                $cdata_value = $xml->createCDATASection($image_tag.$description);
                $child = $xml->createElement('description');
                $child->appendChild($cdata_value);
                $item->appendChild($child);
            }
            else {
                // create description without image
                $cdata_value = $xml->createCDATASection($description);
                $child = $xml->createElement('description');
                $child->appendChild($cdata_value);
                $item->appendChild($child);
            }

            if (false !== ($user = $this->app['account']->getUserData($content['author_username']))) {
                $child = $xml->createElement('author', sprintf('%s (%s)', $user['email'], $user['displayname']));
                $item->appendChild($child);
            }
        }
        // ok - return the XML
        return $xml->saveXML();
    }
}
