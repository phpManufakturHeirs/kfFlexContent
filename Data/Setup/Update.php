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
     * Release 0.20
     */
    protected function release_020()
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
        // Release 0.20
        $this->release_020();

        return $app['translator']->trans('Successfull updated the extension %extension%.',
            array('%extension%' => 'flexContent'));
    }
}
