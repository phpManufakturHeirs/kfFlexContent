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
use phpManufaktur\flexContent\Control\Configuration;
use phpManufaktur\flexContent\Data\Import\ImportControl;
use phpManufaktur\Basic\Control\CMS\InstallPageSection;

class Setup
{
    protected $app = null;

    /**
     * Create the routes needed for the permanentlinks and write bootstrap.include.inc
     *
     * @param Application $app
     * @param array $config load config only if needed!
     * @throws \Exception
     */
    public function createPermalinkRoutes(Application $app, $config=null)
    {
        if (is_null($config)) {
            $Configuration = new Configuration($app);
            $config = $Configuration->getConfiguration();
        }

        $subdirectory = parse_url(CMS_URL, PHP_URL_PATH);

        // always remove an existing include
        $app['filesystem']->remove(MANUFAKTUR_PATH.'/flexContent/bootstrap.include.inc');

        if (false === ($include = file_get_contents(MANUFAKTUR_PATH.'/flexContent/Data/Setup/PermaLink/bootstrap.include.inc'))) {
            throw new \Exception('Missing /flexContent/Data/Setup/PermaLink/bootstrap.include.inc!');
        }

        $permalink = $config['content']['permalink']['directory'];

        $search = array('%subdirectory%', '%permalink%', '%default_language%');
        $replace = array($subdirectory, $permalink, strtolower($config['content']['language']['default']));

        $include = str_replace($search, $replace, $include);

        if (false === (file_put_contents(MANUFAKTUR_PATH.'/flexContent/bootstrap.include.inc', $include))) {
            throw new \Exception("Can't create '/flexContent/bootstrap.include.inc!");
        }
        $app['monolog']->addDebug('Create /flexContent/bootstrap.include.inc');

    }

    /**
     * Create the physical directories and the needed .htaccess files for the permanent links
     *
     * @param Application $app
     * @param array $config load config only if needed!
     * @throws \Exception
     */
    public function createPermalinkDirectories(Application $app, $config=null)
    {
        if (is_null($config)) {
            $Configuration = new Configuration($app);
            $config = $Configuration->getConfiguration();
        }

        $subdirectory = parse_url(CMS_URL, PHP_URL_PATH);

        if ($config['content']['language']['select']) {
            // create directories for all supported languages
            $languages = $config['content']['language']['support'];
        }
        else {
            // create a directory for the default language
            $languages = array();
            foreach ($config['content']['language']['support'] as $language) {
                if ($language['code'] == $config['content']['language']['default']) {
                    $languages[] = $language;
                    break;
                }
            }
        }

        foreach ($languages as $language) {
            $path = $config['content']['permalink']['directory'];
            $path = str_ireplace('{language}', strtolower($language['code']), $path);

            $app['filesystem']->mkdir(CMS_PATH.$path);
            if (false === ($include = file_get_contents(MANUFAKTUR_PATH.'/flexContent/Data/Setup/PermaLink/.htaccess'))) {
                throw new \Exception('Missing /flexContent/Data/Setup/PermaLink/.htaccess!');
            }
            $include = str_replace(array('%subdirectory%'), array($subdirectory), $include);


            if (false === (file_put_contents(CMS_PATH.$path.'/.htaccess', $include))) {
                throw new \Exception("Can't create $path/.htaccess!");
            }
            $app['monolog']->addDebug('Create '.'/'.strtolower($language['code']).$config['content']['permalink']['directory'].'/.htaccess');
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

            // create the import control table
            $ImportControl = new ImportControl($app);
            $ImportControl->createTable();

            // setup kit_framework_flexcontent as Add-on in the CMS
            $admin_tool = new InstallAdminTool($app);
            $admin_tool->exec(MANUFAKTUR_PATH.'/flexContent/extension.json', '/flexcontent/cms');

            // setup kit_framework_flexcontent_section_access
            $section_access = new InstallPageSection($app);
            $section_access->exec(MANUFAKTUR_PATH.'/flexContent/extension.json', '/flexcontent/cms');

            // create the configured permalink routes
            $this->createPermalinkRoutes($app);

            // install .htaccess files for the configured languages
            $this->createPermalinkDirectories($app);

            return $app['translator']->trans('Successfull installed the extension %extension%.',
                array('%extension%' => 'flexContent'));

        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }
}
