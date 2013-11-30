<?php

/**
 * Content
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/Content
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\Content\Data\Setup;

use Silex\Application;
use phpManufaktur\Basic\Control\CMS\InstallAdminTool;

class Setup
{


    /**
     * Execute all steps needed to setup the Content application
     *
     * @param Application $app
     * @throws \Exception
     * @return string with result
     */
    public function exec(Application $app)
    {
        try {

            // setup kit_framework_content as Add-on in the CMS
            $admin_tool = new InstallAdminTool($app);
            $admin_tool->exec(MANUFAKTUR_PATH.'/Content/extension.json', '/content/cms');

            return $app['translator']->trans('Successfull installed the extension %extension%.',
                array('%extension%' => 'Content'));

        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }
}
