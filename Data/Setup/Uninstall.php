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
use phpManufaktur\flexContent\Data\Content\TagType;
use phpManufaktur\flexContent\Data\Content\Tag;
use phpManufaktur\flexContent\Data\Content\CategoryType;
use phpManufaktur\flexContent\Data\Content\Category;

class Uninstall
{

    protected $app = null;

    public function Controller(Application $app)
    {
        try {
            // drop content table
            $Content = new Content($app);
            $Content->dropTable();

            // drop the tag type table
            $TagType = new TagType($app);
            $TagType->dropTable();

            // drop the tag table
            $Tag = new Tag($app);
            $Tag->dropTable();

            // drop the category type table
            $CategoryType = new CategoryType($app);
            $CategoryType->dropTable();

            // drop the category table
            $Category = new Category($app);
            $Category->dropTable();

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
