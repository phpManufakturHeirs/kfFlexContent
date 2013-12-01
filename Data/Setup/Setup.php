<?php

/**
 * flexContent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/flexContent
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\flexContent\Data\Setup;

use Silex\Application;
use phpManufaktur\Basic\Control\CMS\InstallAdminTool;
use phpManufaktur\flexContent\Data\Content\Content;

class Setup
{


    /**
     * Execute all steps needed to setup the Content application
     *
     * @param Application $app
     * @throws \Exception
     * @return string with result
     */
    public function Controller(Application $app)
    {
        try {
            // create content table
            $Content = new Content($app);
            $Content->createTable();

            // setup kit_framework_flexcontent as Add-on in the CMS
            $admin_tool = new InstallAdminTool($app);
            $admin_tool->exec(MANUFAKTUR_PATH.'/flexContent/extension.json', '/content/cms');

            return $app['translator']->trans('Successfull installed the extension %extension%.',
                array('%extension%' => 'flexContent'));

        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }
}
