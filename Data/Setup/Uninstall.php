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
use phpManufaktur\Basic\Control\CMS\UninstallAdminTool;
use phpManufaktur\flexContent\Data\Content\Content;

class Uninstall
{

    protected $app = null;

    public function Controller(Application $app)
    {
        try {
            // drop content table
            $Content = new Content($app);
            $Content->dropTable();

            // uninstall kit_framework_flexcontent from the CMS
            $admin_tool = new UninstallAdminTool($app);
            $admin_tool->exec(MANUFAKTUR_PATH.'/flexContent/extension.json');

            $app['monolog']->addInfo('[flexContent Uninstall] Dropped all tables successfull');
            return $app['translator']->trans('Successfull uninstalled the extension %extension%.',
                array('%extension%' => 'flexContent'));
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }
}
