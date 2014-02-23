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
use phpManufaktur\flexContent\Data\Content\RSSChannel;
use phpManufaktur\flexContent\Data\Content\RSSChannelCounter;
use phpManufaktur\flexContent\Data\Content\RSSChannelStatistic;
use phpManufaktur\flexContent\Data\Content\RSSViewCounter;
use phpManufaktur\flexContent\Data\Content\RSSViewStatistic;
use phpManufaktur\flexContent\Data\Content\Event;

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

            // drop the RSS Channel tables
            $RSSChannel = new RSSChannel($app);
            $RSSChannel->dropTable();
            $RSSChannelCounter = new RSSChannelCounter($app);
            $RSSChannelCounter->dropTable();
            $RSSChannelStatistic = new RSSChannelStatistic($app);
            $RSSChannelStatistic->dropTable();
            $RSSViewCounter = new RSSViewCounter($app);
            $RSSViewCounter->dropTable();
            $RSSViewStatistic = new RSSViewStatistic($app);
            $RSSViewStatistic->dropTable();

            // drop the event extension
            $Event = new Event($app);
            $Event->dropTable();

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
