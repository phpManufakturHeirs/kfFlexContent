<?php

/**
 * flexContent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://addons.phpmanufaktur.de/flexContent
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\flexContent\Control\Admin;

use Silex\Application;
use phpManufaktur\flexContent\Data\Content\Content as ContentData;


class PermaLinkResponse
{
    public function ControllerPermaLink(Application $app)
    {
        try {
            if (null == ($link = $app['request']->get('link'))) {
                throw new \Exception('Missing the GET parameter `link`!');
            }

            // create the permalink
            $perma = $app['utils']->sanitizeLink($link);

            $ContentData = new ContentData($app);
            if ($ContentData->existsPermaLink($perma)) {
                // this permalink is already in use!
                $count = $ContentData->countPermaLinksLikeThis($perma);
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
