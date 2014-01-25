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

    /**
     * Release 0.16
     */
    protected function release_016()
    {
        $config = $this->Configuration->getConfiguration();
        if (!isset($config['kitcommand']['content']['kitcommand']['enabled'])) {
            $config['kitcommand']['content']['kitcommand']['enabled'] = false;
            $this->Configuration->setConfiguration($config);
            $this->Configuration->saveConfiguration();
            $this->app['monolog']->addDebug('Added kitcommand -> content -> kitcommand -> enabled to the config.flexcontent.json');
        }
        if (!isset($config['kitcommand']['parameter']['action']['view']['rating'])) {
            $config['kitcommand']['parameter']['action']['view']['rating'] = array(
                'enabled' => true,
                'maximum_rate' => 5,
                'size' => 'big',
                'stars' => 5,
                'step' => true,
                'template' => 'default'
            );
            $this->Configuration->setConfiguration($config);
            $this->Configuration->saveConfiguration();
            $this->app['monolog']->addDebug('Added kitcommand -> parameter -> action -> view -> rating to the config.flexcontent.json');
        }
        if (!isset($config['kitcommand']['parameter']['action']['view']['comments'])) {
            $config['kitcommand']['parameter']['action']['view']['comments'] = array(
                'enabled' => true,
                'captcha' => false,
                'gravatar' => true,
                'publish' => 'admin',
                'rating' => true
            );
            $this->Configuration->setConfiguration($config);
            $this->Configuration->saveConfiguration();
            $this->app['monolog']->addDebug('Added kitcommand -> parameter -> action -> view -> comments to the config.flexcontent.json');
        }
    }

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

        $config = $this->Configuration->getConfiguration();

        if (!isset($config['content']['field']['redirect_url'])) {
            $config['content']['field']['redirect_url'] = array(
                'required' => false,
                'default' => '_blank'
            );
            $this->Configuration->setConfiguration($config);
            $this->Configuration->saveConfiguration();
            $this->app['monolog']->addDebug('Added content -> field -> redirect_url to the config.flexcontent.json');
        }

        if (!isset($config['kitcommand']['parameter']['action']['list'])) {
            $config['kitcommand']['parameter']['action']['list'] = array(
                'css' => true,
                'title_level' => 1,
                'categories' => array(),
                'categories_exclude' => array(),
                'order_by' => 'publish_from',
                'order_direction' => 'DESC',
                'list_tags' => true,
                'content_limit' => 100,
                'content_status' => array(
                    'BREAKING',
                    'PUBLISHED'
                ),
                'content_image' => true,
                'content_image_max_width' => 350,
                'content_image_max_height' => 350,
                'content_image_small_max_width' => 100,
                'content_image_small_max_height' => 100,
                'content_title' => true,
                'content_description' => false,
                'content_teaser' => true,
                'content_content' => false,
                'content_tags' => true,
                'content_author' => true,
                'content_date' => true,
                'content_categories' => true
            );
            $this->Configuration->setConfiguration($config);
            $this->Configuration->saveConfiguration();
            $this->app['monolog']->addDebug('Added kitcommand -> parameter -> action -> list to the config.flexcontent.json');
        }

        if (!isset($config['kitcommand']['parameter']['action']['list_simple'])) {
            $config['kitcommand']['parameter']['action']['list'] = array(
                'css' => true,
                'title_level' => 1,
                'categories' => array(),
                'categories_exclude' => array(),
                'order_by' => 'publish_from',
                'order_direction' => 'DESC',
                'list_tags' => false,
                'content_limit' => 10,
                'content_status' => array(
                    'BREAKING',
                    'PUBLISHED'
                ),
                'content_image' => true,
                'content_image_max_width' => 350,
                'content_image_max_height' => 350,
                'content_image_small_max_width' => 100,
                'content_image_small_max_height' => 100,
                'content_title' => true,
                'content_description' => false,
                'content_teaser' => true,
                'content_content' => false,
                'content_tags' => true,
                'content_author' => true,
                'content_date' => true,
                'content_categories' => true
            );
            $this->Configuration->setConfiguration($config);
            $this->Configuration->saveConfiguration();
            $this->app['monolog']->addDebug('Added kitcommand -> parameter -> action -> list to the config.flexcontent.json');
        }

        if (!isset($config['kitcommand']['parameter']['action']['category']['list_tags'])) {
            $config['kitcommand']['parameter']['action']['category']['list_tags'] = false;
            $this->Configuration->setConfiguration($config);
            $this->Configuration->saveConfiguration();
            $this->app['monolog']->addDebug('Added kitcommand -> parameter -> action -> category -> list_tags to the config.flexcontent.json');
        }

        if (!isset($config['kitcommand']['parameter']['action']['tag']['list_tags'])) {
            $config['kitcommand']['parameter']['action']['tag']['list_tags'] = false;
            $this->Configuration->setConfiguration($config);
            $this->Configuration->saveConfiguration();
            $this->app['monolog']->addDebug('Added kitcommand -> parameter -> action -> tag -> list_tags to the config.flexcontent.json');
        }

        // delete no longer needed templates
        $this->app['filesystem']->remove(MANUFAKTUR_PATH.'/flexContent/Template/default/category.exposed.twig');
        $this->app['filesystem']->remove(MANUFAKTUR_PATH.'/flexContent/Template/default/category.item.twig');
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

        // Release 0.16
        $this->release_016();
        // Release 0.17
        $this->release_017();

        return $app['translator']->trans('Successfull updated the extension %extension%.',
            array('%extension%' => 'flexContent'));
    }
}
