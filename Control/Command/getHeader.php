<?php

/**
 * flexContent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/flexContent
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\flexContent\Control\Command;

use Silex\Application;
use phpManufaktur\flexContent\Data\Content\Content;

class getHeader
{
    public function controllerGetHeader(Application $app, $content_id)
    {
        $ContentData = new Content($app);
        if (false === ($content = $ContentData->select($content_id))) {
            return $app->json(array('status' => "The flexContent ID $content_id does not exists!"), 404);
        }
        return $app->json(array(
            'title' => $content['page_title'],
            'description' => $content['description'],
            'keywords' => $content['keywords']));
    }
}
