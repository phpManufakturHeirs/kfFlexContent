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
use phpManufaktur\flexContent\Data\Content\TagType;
use phpManufaktur\flexContent\Data\Content\Tag;
use phpManufaktur\flexContent\Data\Content\CategoryType;
use phpManufaktur\flexContent\Data\Content\Category;

class Setup
{
    protected $app = null;

    public function addSubdirectoryRoutes(Application $app)
    {
        // always remove an existing include
        $app['filesystem']->remove(MANUFAKTUR_PATH.'/flexContent/bootstrap.include.inc');

        $subdirectory = parse_url(CMS_URL, PHP_URL_PATH);
        if (strlen($subdirectory) > 1) {
            // the kitFramework is installed in a subdirectory
            if (false === ($include = file_get_contents(MANUFAKTUR_PATH.'/flexContent/Data/Setup/PermaLink/bootstrap.include.inc'))) {
                throw new \Exception('Missing /flexContent/Data/Setup/PermaLink/bootstrap.include.inc!');
            }
            $include = str_replace('%subdirectory%', $subdirectory, $include);
            if (false === (file_put_contents(MANUFAKTUR_PATH.'/flexContent/bootstrap.include.inc', $include))) {
                throw new \Exception("Can't create '/flexContent/bootstrap.include.inc!");
            }
            $app['monolog']->addDebug('Create /flexContent/bootstrap.include.inc');
        }
    }

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
            $this->app = $app;

            // create content table
            $Content = new Content($app);
            $Content->createTable();

            // create the TagType table
            $TagType = new TagType($app);
            $TagType->createTable();

            // create the Tag table
            $Tag = new Tag($app);
            $Tag->createTable();

            // create the CategoryType table
            $CategoryType = new CategoryType($app);
            $CategoryType->createTable();

            // create the Category table
            $Category = new Category($app);
            $Category->createTable();

            // setup kit_framework_flexcontent as Add-on in the CMS
            $admin_tool = new InstallAdminTool($app);
            $admin_tool->exec(MANUFAKTUR_PATH.'/flexContent/extension.json', '/flexcontent/cms');

            // add subdirectory routes
            $this->addSubdirectoryRoutes($app);

            return $app['translator']->trans('Successfull installed the extension %extension%.',
                array('%extension%' => 'flexContent'));

        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }
}
