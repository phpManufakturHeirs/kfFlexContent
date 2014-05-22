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
use phpManufaktur\flexContent\Control\Configuration;
use phpManufaktur\flexContent\Data\Content\RSSChannel;
use phpManufaktur\flexContent\Data\Content\RSSChannelCounter;
use phpManufaktur\flexContent\Data\Content\RSSChannelStatistic;
use phpManufaktur\flexContent\Data\Content\RSSViewCounter;
use phpManufaktur\flexContent\Data\Content\RSSViewStatistic;
use phpManufaktur\flexContent\Data\Content\Event;
use Symfony\Component\Finder\Finder;

class Update
{
    protected $app = null;
    protected $Configuration = null;
    protected static $config = null;

    /**
     * Release 0.17
     */
    protected function release_017()
    {
        if (!$this->app['db.utils']->columnExists(FRAMEWORK_TABLE_PREFIX.'flexcontent_content', 'redirect_target')) {
            // add column redirect_target
            $SQL = "ALTER TABLE `".FRAMEWORK_TABLE_PREFIX."flexcontent_content` ADD ".
                "`redirect_target` ENUM('_blank','_self','_parent_','_top') NOT NULL DEFAULT '_blank' AFTER `redirect_url`";
            $this->app['db']->query($SQL);
            $this->app['monolog']->addInfo('[flexContent Update] Add field `redirect_target` to table `flexcontent_content`');
        }

        // delete no longer needed templates
        $this->app['filesystem']->remove(MANUFAKTUR_PATH.'/flexContent/Template/default/category.exposed.twig');
        $this->app['filesystem']->remove(MANUFAKTUR_PATH.'/flexContent/Template/default/category.item.twig');
    }

    /**
     * Release 0.18
     */
    protected function release_018()
    {
        if (isset(self::$config['kitcommand']['parameter']['action']['view']['description'])) {
            // the configuration use old and obsolete keys, delete the file and create a new one!
            $this->app['filesystem']->remove(MANUFAKTUR_PATH.'/flexContent/config.flexcontent.json');
            // create a new default configuration
            self::$config = $this->Configuration->getDefaultConfigArray();
            $this->Configuration->setConfiguration(self::$config);
            $this->Configuration->saveConfiguration();
        }
    }

    /**
     * Release 0.19
     */
    protected function release_019()
    {
        if (isset(self::$config['kitcommand']['parameter']['action']['tag']['content_teaser'])) {
            // the config items 'content_teaser' and 'content_content' are replaced by 'content_view'
            unset(self::$config['kitcommand']['parameter']['action']['tag']['content_teaser']);
            unset(self::$config['kitcommand']['parameter']['action']['tag']['content_content']);
            $this->Configuration->setConfiguration(self::$config);
            $this->Configuration->saveConfiguration();
        }
        if (!isset(self::$config['kitcommand']['parameter']['action']['tag']['content_view'])) {
            // add missing 'content_view'
            self::$config['kitcommand']['parameter']['action']['tag']['content_view'] = 'teaser';
            $this->Configuration->setConfiguration(self::$config);
            $this->Configuration->saveConfiguration();
        }

        if (!$this->app['db.utils']->tableExists(FRAMEWORK_TABLE_PREFIX.'flexcontent_rss_channel')) {
            // introduce RSS Channel
            $RSSChannel = new RSSChannel($this->app);
            $RSSChannel->createTable();
        }
        if (!$this->app['db.utils']->tableExists(FRAMEWORK_TABLE_PREFIX.'flexcontent_rss_channel_counter')) {
            $RSSChannelCounter = new RSSChannelCounter($this->app);
            $RSSChannelCounter->createTable();
        }
        if (!$this->app['db.utils']->tableExists(FRAMEWORK_TABLE_PREFIX.'flexcontent_rss_channel_statistic')) {
            $RSSChannelStatistic = new RSSChannelStatistic($this->app);
            $RSSChannelStatistic->createTable();
        }
        if (!$this->app['db.utils']->tableExists(FRAMEWORK_TABLE_PREFIX.'flexcontent_rss_view_counter')) {
            $RSSViewCounter = new RSSViewCounter($this->app);
            $RSSViewCounter->createTable();
        }
        if (!$this->app['db.utils']->tableExists(FRAMEWORK_TABLE_PREFIX.'flexcontent_rss_view_statistic')) {
            $RSSViewStatistic = new RSSViewStatistic($this->app);
            $RSSViewStatistic->createTable();
        }


        if (!isset(self::$config['admin']['rss'])) {
            // general configuration for the RSS Channels
            self::$config['admin']['rss'] = array(
                'enabled' => true,
                'channel' => array(
                    'limit' => 50
                )
            );
            $this->Configuration->setConfiguration(self::$config);
            $this->Configuration->saveConfiguration();
        }

        if (!$this->app['db.utils']->columnExists(FRAMEWORK_TABLE_PREFIX.'flexcontent_content', 'rss')) {
            // add column redirect_target
            $SQL = "ALTER TABLE `".FRAMEWORK_TABLE_PREFIX."flexcontent_content` ADD `rss` ENUM('YES','NO') NOT NULL DEFAULT 'YES' AFTER `content`";
            $this->app['db']->query($SQL);
            $this->app['monolog']->addInfo('[flexContent Update] Add field `rss` to table `flexcontent_content`');
        }

        if (!isset(self::$config['content']['field']['rss']['required'])) {
            // add missing 'rss'
            self::$config['content']['field']['rss']['required'] = false;
            $this->Configuration->setConfiguration(self::$config);
            $this->Configuration->saveConfiguration();
        }
    }

    /**
     * Release 0.20
     */
    protected function release_020()
    {
        if (!isset(self::$config['admin']['import']['timelimit'])) {
            // set default timelimit for the import
            self::$config['admin']['import']['timelimit'] = 60;
            $this->Configuration->setConfiguration(self::$config);
            $this->Configuration->saveConfiguration();
        }
        if (!isset(self::$config['admin']['import']['data']['htmlpurifier']['config'])) {
            $default = $this->Configuration->getDefaultConfigArray();
            self::$config['admin']['import']['data']['htmlpurifier']['config'] =
                $default['admin']['import']['data']['htmlpurifier']['config'];
            $this->Configuration->setConfiguration(self::$config);
            $this->Configuration->saveConfiguration();
        }
        if (!isset(self::$config['admin']['import']['data']['images']['sanitize'])) {
            self::$config['admin']['import']['data']['images']['sanitize'] = true;
            $this->Configuration->setConfiguration(self::$config);
            $this->Configuration->saveConfiguration();
        }
        if (isset(self::$config['admin']['import']['data']['remove']['nbsp'])) {
            // remove items which are replaced by htmlpurifier
            unset(self::$config['admin']['import']['data']['remove']['nbsp']);
            unset(self::$config['admin']['import']['data']['remove']['style']);
            unset(self::$config['admin']['import']['data']['remove']['class']);
            $this->Configuration->setConfiguration(self::$config);
            $this->Configuration->saveConfiguration();
        }
        if (!isset(self::$config['admin']['import']['data']['replace'])) {
            $default = $this->Configuration->getDefaultConfigArray();
            self::$config['admin']['import']['data']['replace'] =
                $default['admin']['import']['data']['replace'];
            $this->Configuration->setConfiguration(self::$config);
            $this->Configuration->saveConfiguration();
        }
    }

    /**
     * Release 0.21
     */
    protected function release_021()
    {
        if (isset(self::$config['admin']['import']['data']['htmlpurifier']['config']['AutoFormat.RemoveSpansWithoutAttributes'])) {
            // remove conflicting setting in htmlpurifier
            unset(self::$config['admin']['import']['data']['htmlpurifier']['config']['AutoFormat.RemoveSpansWithoutAttributes']);
            $this->Configuration->setConfiguration(self::$config);
            $this->Configuration->saveConfiguration();
        }
    }

    /**
     * Release 0.22
     */
    protected function release_022()
    {
        if (!isset(self::$config['kitcommand']['parameter']['action']['list']['content_exposed'])) {
            self::$config['kitcommand']['parameter']['action']['list']['content_exposed'] = 2;
            $this->Configuration->setConfiguration(self::$config);
            $this->Configuration->saveConfiguration();
        }
        if (!isset(self::$config['kitcommand']['parameter']['action']['category']['content_exposed'])) {
            self::$config['kitcommand']['parameter']['action']['category']['content_exposed'] = 2;
            $this->Configuration->setConfiguration(self::$config);
            $this->Configuration->saveConfiguration();
        }
        if (!isset(self::$config['kitcommand']['parameter']['action']['tag']['content_exposed'])) {
            self::$config['kitcommand']['parameter']['action']['tag']['content_exposed'] = 2;
            $this->Configuration->setConfiguration(self::$config);
            $this->Configuration->saveConfiguration();
        }
    }

    /**
     * Release 0.23
     */
    public function release_023()
    {
        if (!$this->app['db.utils']->columnExists(FRAMEWORK_TABLE_PREFIX.'flexcontent_category_type', 'category_type')) {
            // add column category_type
            $SQL = "ALTER TABLE `".FRAMEWORK_TABLE_PREFIX."flexcontent_category_type` ADD ".
                "`category_type` ENUM ('DEFAULT','EVENT','FAQ') NOT NULL DEFAULT 'DEFAULT' AFTER `category_image`";
            $this->app['db']->query($SQL);
            $this->app['monolog']->addInfo('[flexContent Update] Add field `category_type` to table `flexcontent_category_type`');
        }

        if (!$this->app['db.utils']->tableExists(FRAMEWORK_TABLE_PREFIX.'flexcontent_event')) {
            // create the table flexcontent_event
            $Event = new Event($this->app);
            $Event->createTable();
        }

        if (!isset(self::$config['content']['field']['event_date_from']['required'])) {
            self::$config['content']['field']['event_date_from']['required'] = true;
            $this->Configuration->setConfiguration(self::$config);
            $this->Configuration->saveConfiguration();
        }
        if (!isset(self::$config['content']['field']['event_date_to']['required'])) {
            self::$config['content']['field']['event_date_to']['required'] = true;
            $this->Configuration->setConfiguration(self::$config);
            $this->Configuration->saveConfiguration();
        }
        if (!isset(self::$config['content']['field']['event_organizer']['required'])) {
            self::$config['content']['field']['event_organizer']['required'] = false;
            $this->Configuration->setConfiguration(self::$config);
            $this->Configuration->saveConfiguration();
        }
        if (!isset(self::$config['content']['field']['event_organizer']['tags'])) {
            self::$config['content']['field']['event_organizer']['tags'] = array();
            $this->Configuration->setConfiguration(self::$config);
            $this->Configuration->saveConfiguration();
        }
        if (!isset(self::$config['content']['field']['event_location']['required'])) {
            self::$config['content']['field']['event_location']['required'] = false;
            $this->Configuration->setConfiguration(self::$config);
            $this->Configuration->saveConfiguration();
        }
        if (!isset(self::$config['content']['field']['event_location']['tags'])) {
            self::$config['content']['field']['event_location']['tags'] = array();
            $this->Configuration->setConfiguration(self::$config);
            $this->Configuration->saveConfiguration();
        }

    }

    /**
     * Release 0.24
     */
    protected function release_024()
    {
        if (isset(self::$config['kitcommand']['template'])) {
            // the key template is no longer used
            unset(self::$config['kitcommand']['template']);
            $this->Configuration->setConfiguration(self::$config);
            $this->Configuration->saveConfiguration();
        }

        $keys = array('view', 'category', 'tag', 'list', 'list_simple', 'faq');
        foreach ($keys as $key) {
            if (!isset(self::$config['kitcommand']['parameter']['action'][$key]['check_jquery'])) {
                self::$config['kitcommand']['parameter']['action'][$key]['check_jquery'] = true;
                $this->Configuration->setConfiguration(self::$config);
                $this->Configuration->saveConfiguration();
            }
        }

        $keys = array('list', 'list_simple');
        foreach ($keys as $key) {
            if (!isset(self::$config['kitcommand']['parameter']['action'][$key]['paging'])) {
                self::$config['kitcommand']['parameter']['action'][$key]['paging'] = 0;
                $this->Configuration->setConfiguration(self::$config);
                $this->Configuration->saveConfiguration();
            }
        }
    }

    /**
     * Release 0.26
     */
    protected function release_026()
    {
        if (!isset(self::$config['nav_tabs'])) {
            self::$config['nav_tabs'] = array(
                'order' => array(
                    'list',
                    'edit',
                    'tags',
                    'categories',
                    'rss',
                    'import',
                    'about'
                ),
                'default' => 'about'
            );
            $this->Configuration->setConfiguration(self::$config);
            $this->Configuration->saveConfiguration();
        }
    }

    /**
     * Release 0.29
     */
    protected function release_029()
    {
        $twig_files = new Finder();
        $twig_files->files()->name('*.twig')->in(MANUFAKTUR_PATH.'/flexContent/Template')->exclude(array('default', 'backup'));
        foreach ($twig_files as $twig_file) {
            if ($twig_file->isReadable()) {
                $origin = $twig_file->getRealpath();
                if (false !== ($content = file_get_contents($origin))) {
                    if (strpos($content, '/tag/')) {
                        $backup_path = MANUFAKTUR_PATH.'/flexContent/Template/'.$twig_file->getRelativePath().'/backup';
                        if (!$this->app['filesystem']->exists($backup_path)) {
                            $this->app['filesystem']->mkdir($backup_path);
                        }
                        if (false !== (file_put_contents($backup_path.'/'.$twig_file->getBasename(), $content))) {
                            // continue only if a backup was saved
                            $content = str_replace('/tag/', '/buzzword/', $content);
                            file_put_contents($origin, $content);
                            $this->app['monolog']->addDebug('Created Backup and updated Twig file: '.$twig_file->getBasename());
                        }
                    }
                }
            }
        }
    }

    /**
     * Execute the update for flexContent
     *
     * @param Application $app
     */
    public function Controller(Application $app)
    {
        $this->app = $app;

        $Setup = new Setup();

        // create the configured permalink routes
        $Setup->createPermalinkRoutes($app);

        // install .htaccess files for the configured languages
        $Setup->createPermalinkDirectories($app);

        // initialize Configuration for the update routines
        $this->Configuration = new Configuration($app);
        self::$config = $this->Configuration->getConfiguration();

        $this->release_017();
        $this->release_018();
        $this->release_019();
        $this->release_020();
        $this->release_021();
        $this->release_022();
        $this->release_023();
        $this->release_024();
        $this->release_026();
        $this->release_029();

        return $app['translator']->trans('Successfull updated the extension %extension%.',
            array('%extension%' => 'flexContent'));
    }
}
