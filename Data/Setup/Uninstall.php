<?php

/**
 * Event
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/event
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\Content\Data\Setup;

use Silex\Application;
use phpManufaktur\Basic\Control\CMS\UninstallAdminTool;

class Uninstall
{

    protected $app = null;

    public function exec(Application $app)
    {
        try {
            // uninstall kit_framework_content from the CMS
            $admin_tool = new UninstallAdminTool($app);
            $admin_tool->exec(MANUFAKTUR_PATH.'/Content/extension.json');

            $app['monolog']->addInfo('[Content Uninstall] Dropped all tables successfull');
            return $app['translator']->trans('Successfull uninstalled the extension %extension%.',
                array('%extension%' => 'Content'));
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }
}
