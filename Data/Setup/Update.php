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
            $SQL = "ALTER TABLE `".FRAMEWORK_TABLE_PREFIX."flexcontent_content` ADD `redirect_target` ENUM('_blank','_self','_parent_','_top') NOT NULL DEFAULT '_blank' AFTER `redirect_url`";
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

        // Release 0.17
        $this->release_017();
        // Release 0.18
        $this->release_018();
        // Release 0.19
        $this->release_019();

        return $app['translator']->trans('Successfull updated the extension %extension%.',
            array('%extension%' => 'flexContent'));
    }
}
