<?php

/**
 * flexContent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/flexContent
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\flexContent\Control\Backend;

use Silex\Application;
use phpManufaktur\flexContent\Data\Content\Content as ContentData;

class ContentSearch extends Backend
{

    public function ControllerSearch(Application $app)
    {
        $this->initialize($app);

        $this->setMessage('The search function is not integrated now ...');

        return $this->app['twig']->render($this->app['utils']->getTemplateFile(
            '@phpManufaktur/flexContent/Template', 'backend/response.twig'),
            array(
                'usage' => self::$usage,
                'toolbar' => $this->getToolbar('list'),
                'message' => $this->getMessage()
            ));
    }
}
