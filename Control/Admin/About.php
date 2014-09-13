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

use phpManufaktur\flexContent\Control\Admin\Admin;
use Silex\Application;

class About extends Admin {

    /**
     * Show the about dialog for flexContent
     *
     * @return string rendered dialog
     */
    public function Controller(Application $app)
    {
        $this->initialize($app);

        $extension = $this->app['utils']->readJSON(MANUFAKTUR_PATH.'/flexContent/extension.json');

        return $this->app['twig']->render($this->app['utils']->getTemplateFile(
            '@phpManufaktur/flexContent/Template', 'admin/about.twig'),
            array(
                'usage' => self::$usage,
                'toolbar' => $this->getToolbar('about'),
                'extension' => $extension
            ));
    }

}
