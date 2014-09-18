<?php

/**
 * flexContent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/flexContent
 * @copyright 2014 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
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
use phpManufaktur\flexContent\Data\Content\RSSChannel;
use phpManufaktur\flexContent\Data\Content\RSSChannelCounter;
use phpManufaktur\flexContent\Data\Content\RSSChannelStatistic;
use phpManufaktur\flexContent\Data\Content\RSSViewCounter;
use phpManufaktur\flexContent\Data\Content\RSSViewStatistic;
use phpManufaktur\flexContent\Data\Content\Event;

class Setup
{
    protected $app = null;

    /**
     * Create the routes needed for the permanentlinks and write bootstrap.include.inc
     *
     * @param Application $app
     * @param array $config load config only if needed!
     * @param string $subdirectory calculate subdirectory only if needed
     * @throws \Exception
     */
    public function createPermalinkRoutes(Application $app, $config=null, $subdirectory=null)
    {
        if (is_null($config)) {
            $Configuration = new Configuration($app);
            $config = $Configuration->getConfiguration();
        }

        if (is_null($subdirectory)) {
            // get the subdirectory from the CMS_URL
            $subdirectory = parse_url(CMS_URL, PHP_URL_PATH);
        }

        // always remove an existing include
        $app['filesystem']->remove(MANUFAKTUR_PATH.'/flexContent/bootstrap.include.inc');

        if (false === ($include = file_get_contents(MANUFAKTUR_PATH.'/flexContent/Data/Setup/PermaLink/bootstrap.include.inc'))) {
            throw new \Exception('Missing /flexContent/Data/Setup/PermaLink/bootstrap.include.inc!');
        }

        $permalink = $config['content']['permalink']['directory'];
        $rsslink = $config['rss']['permalink']['directory'];

        $search = array('%subdirectory%', '%permalink%', '%default_language%', '%rsslink%');
        $replace = array($subdirectory, $permalink, strtolower($config['content']['language']['default']), $rsslink);

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
    public function createPermalinkDirectories(Application $app, $config=null, $subdirectory=null, $CMS_PATH=null)
    {
        if (is_null($config)) {
            $Configuration = new Configuration($app);
            $config = $Configuration->getConfiguration();
        }

        if (is_null($subdirectory)) {
            // get the subdirectory from the CMS URL
            $subdirectory = parse_url(CMS_URL, PHP_URL_PATH);
        }

        if (is_null($CMS_PATH)) {
            // need the CMS path
            $CMS_PATH = CMS_PATH;
        }

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

            $app['filesystem']->mkdir($CMS_PATH.$path);
            if (false === ($include = file_get_contents(MANUFAKTUR_PATH.'/flexContent/Data/Setup/PermaLink/.htaccess'))) {
                throw new \Exception('Missing /flexContent/Data/Setup/PermaLink/.htaccess!');
            }
            $include = str_replace(array('%subdirectory%'), array($subdirectory), $include);

            if (false === (file_put_contents($CMS_PATH.$path.'/.htaccess', $include))) {
                throw new \Exception("Can't create $path/.htaccess!");
            }
            $app['monolog']->addDebug('Create '.'/'.strtolower($language['code']).$config['content']['permalink']['directory'].'/.htaccess');

            $rss_path = $config['rss']['permalink']['directory'];
            $rss_path = str_ireplace('{language}', strtolower($language['code']), $rss_path);

            $app['filesystem']->mkdir($CMS_PATH.$rss_path);
            if (false === ($include = file_get_contents(MANUFAKTUR_PATH.'/flexContent/Data/Setup/PermaLink/.htaccess'))) {
                throw new \Exception('Missing /flexContent/Data/Setup/PermaLink/.htaccess!');
            }
            $include = str_replace(array('%subdirectory%'), array($subdirectory), $include);

            if (false === (file_put_contents($CMS_PATH.$rss_path.'/.htaccess', $include))) {
                throw new \Exception("Can't create $rss_path/.htaccess!");
            }
            $app['monolog']->addDebug('Create '.'/'.strtolower($language['code']).$config['rss']['permalink']['directory'].'/.htaccess');

        }
    }

    /**
     * Install the CKEditor Plugins for flexContent in the parent CMS
     *
     * @param Application $app
     * @throws \Exception
     */
    public function InstallCKEditorPlugins(Application $app)
    {
        if ((CMS_TYPE === 'BlackCat') && $app['filesystem']->exists(CMS_PATH.'/modules/ckeditor4')) {
            // add buttons for flexContent Articles and #Hashtags
            $plugins = array('flexcontentlink', 'hashtaglink');
            $cms_plugin_path = CMS_PATH.'/modules/ckeditor4/ckeditor/plugins/';
            $subdirectory = parse_url(CMS_URL, PHP_URL_PATH);
            foreach ($plugins as $plugin) {
                if ($app['filesystem']->exists($cms_plugin_path.$plugin)) {
                    // remove existing plugin
                    $app['filesystem']->remove($cms_plugin_path.$plugin);
                }
                // mirror the plugin from kitFramework to CMS
                $app['filesystem']->mirror(MANUFAKTUR_PATH.'/CKEditor/Source/plugins/'.$plugin, $cms_plugin_path.$plugin);
                // prepare the .htaccess file to correct the plugin route
                $htaccess = file_get_contents(MANUFAKTUR_PATH.'/flexContent/Data/Setup/CKEditor/.htaccess');
                $htaccess = str_ireplace(
                    array('%plugin%', '%subdirectory%', '%manufaktur_url%'),
                    array($plugin, $subdirectory, MANUFAKTUR_URL), $htaccess);
                // write the .htaccess file
                file_put_contents($cms_plugin_path.$plugin.'/.htaccess', $htaccess);
                if ($app['db.utils']->tableExists(CMS_TABLE_PREFIX.'mod_wysiwyg_admin_v2')) {
                    // register plugin at CMS WYSIWYG Admin
                    try {
                        $SQL = "SELECT `set_value` FROM `".CMS_TABLE_PREFIX."mod_wysiwyg_admin_v2` WHERE ".
                            "`editor`='ckeditor4' AND `set_name`='plugins'";
                        $result = $app['db']->fetchColumn($SQL);
                        $cke_plugins = explode(',', $result);
                        if (!in_array($plugin, $cke_plugins)) {
                            $cke_plugins[] = $plugin;
                            $app['db']->update(CMS_TABLE_PREFIX.'mod_wysiwyg_admin_v2',
                                array('set_value' => implode(',', $cke_plugins)),
                                array('editor' => 'ckeditor4', 'set_name' => 'plugins'));
                        }
                    } catch (\Doctrine\DBAL\DBALException $e) {
                        throw new \Exception($e);
                    }
                }
            }
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

            // create the RSS Channel
            $RSSChannel = new RSSChannel($app);
            $RSSChannel->createTable();
            $RSSChannelCounter = new RSSChannelCounter($app);
            $RSSChannelCounter->createTable();
            $RSSChannelStatistic = new RSSChannelStatistic($app);
            $RSSChannelStatistic->createTable();
            $RSSViewCounter = new RSSViewCounter($app);
            $RSSViewCounter->createTable();
            $RSSViewStatistic = new RSSViewStatistic($app);
            $RSSViewStatistic->createTable();

            // create the Event extension
            $Event = new Event($app);
            $Event->createTable();

            // setup kit_framework_flexcontent as Add-on in the CMS
            $admin_tool = new InstallAdminTool($app);
            $admin_tool->exec(MANUFAKTUR_PATH.'/flexContent/extension.json', '/flexcontent/cms');

            // create the configured permalink routes
            $this->createPermalinkRoutes($app);

            // install .htaccess files for the configured languages
            $this->createPermalinkDirectories($app);

            // install CMS CKEditor plugins
            $this->InstallCKEditorPlugins($app);

            return $app['translator']->trans('Successfull installed the extension %extension%.',
                array('%extension%' => 'flexContent'));

        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }
}
