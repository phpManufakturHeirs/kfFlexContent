<?php

/**
 * flexContent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/flexContent
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\flexContent\Control\Admin;

use Silex\Application;
use phpManufaktur\flexContent\Data\Content\Content as ContentData;
use phpManufaktur\flexContent\Data\Content\CategoryType as CategoryTypeData;
use phpManufaktur\flexContent\Data\Content\TagType as TagTypeData;
use phpManufaktur\flexContent\Data\Content\RSSChannel;

class PermaLinkResponse
{

    /**
     * Controller for the autocomplete of flexContent permalinks, generated
     * from the content title
     *
     * @param Application $app
     * @throws \Exception
     */
    public function ControllerPermaLink(Application $app)
    {
        try {
            if (null == ($link = $app['request']->get('link'))) {
                throw new \Exception('Missing the GET parameter `link`!');
            }
            if (null == ($language = $app['request']->get('lang'))) {
                throw new \Exception('Missing the GET parameter `lang`!');
            }

            // create the permalink
            $perma = $app['utils']->sanitizeLink($link);

            $ContentData = new ContentData($app);
            if ($ContentData->existsPermaLink($perma, $language)) {
                // this permalink is already in use!
                $count = $ContentData->countPermaLinksLikeThis($perma, $language);
                $count++;
                // add a counter to the new permanet link
                $perma = sprintf('%s-%d', $perma, $count);
            }

            // return JSON response
            return $app->json($perma, 201);
        }
        catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Controller to create a permanent link from the given category name
     *
     * @param Application $app
     * @throws \Exception
     */
    public function ControllerPermaLinkCategory(Application $app)
    {
        try {
            if (null == ($link = $app['request']->get('link'))) {
                throw new \Exception('Missing the GET parameter `link`!');
            }
            if (null == ($language = $app['request']->get('lang'))) {
                throw new \Exception('Missing the GET parameter `lang`!');
            }

            // create the permalink
            $perma = $app['utils']->sanitizeLink($link);

            $CategoryTypeData = new CategoryTypeData($app);
            if ($CategoryTypeData->existsPermaLink($perma, $language)) {
                // this permalink is already in use!
                $count = $CategoryTypeData->countPermaLinksLikeThis($perma, $language);
                $count++;
                // add a counter to the new permanet link
                $perma = sprintf('%s-%d', $perma, $count);
            }

            // return JSON response
            return $app->json($perma, 201);
        }
        catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Controller to create a permanent link from the given tag name
     *
     * @param Application $app
     * @throws \Exception
     */
    public function ControllerPermaLinkTag(Application $app)
    {
        try {
            if (null == ($link = $app['request']->get('link'))) {
                throw new \Exception('Missing the GET parameter `link`!');
            }
            if (null == ($language = $app['request']->get('lang'))) {
                throw new \Exception('Missing the GET parameter `lang`!');
            }

            // create the permalink
            $perma = $app['utils']->sanitizeLink($link);

            $TagTypeData = new TagTypeData($app);
            if ($TagTypeData->existsPermaLink($perma, $language)) {
                // this permalink is already in use!
                $count = $TagTypeData->countPermaLinksLikeThis($perma, $language);
                $count++;
                // add a counter to the new permanet link
                $perma = sprintf('%s-%d', $perma, $count);
            }

            // return JSON response
            return $app->json($perma, 201);
        }
        catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    public function ControllerPermaLinkRSSChannel(Application $app)
    {
        try {
            if (null == ($link = $app['request']->get('link'))) {
                throw new \Exception('Missing the GET parameter `link`!');
            }
            if (null == ($language = $app['request']->get('lang'))) {
                throw new \Exception('Missing the GET parameter `lang`!');
            }

            // create the permalink
            $perma = $app['utils']->sanitizeLink($link);

            $RSSChannelData = new RSSChannel($app);
            if ($RSSChannelData->existsChannelLink($perma, $language)) {
                // this Channel Link is already in use!
                $count = $RSSChannelData->countChannelLinksLikeThis($perma, $language);
                $count++;
                // add a counter to the new permanet link
                $perma = sprintf('%s-%d', $perma, $count);
            }

            // return JSON response
            return $app->json($perma, 201);
        }
        catch (\Exception $e) {
            throw new \Exception($e);
        }
    }
}
