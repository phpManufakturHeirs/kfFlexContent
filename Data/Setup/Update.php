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

    protected function release_016()
    {
        $config = $this->Configuration->getConfiguration();
        if (!isset($config['kitcommand']['content']['kitcommand']['enabled'])) {
            $config['kitcommand']['content']['kitcommand']['enabled'] = false;
            $this->Configuration->setConfiguration($config);
            $this->Configuration->saveConfiguration();
            $this->app['monolog']->addDebug('Added kitcommand -> content -> kitcommand -> enabled to the config.flexcontent.json');
        }
        if (!isset($config['kitcommand']['parameter']['view']['rating'])) {
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
            $this->app['monolog']->addDebug('Added kitcommand -> parameter -> view -> rating to the config.flexcontent.json');
        }
        if (!isset($config['kitcommand']['parameter']['view']['comments'])) {
            $config['kitcommand']['parameter']['action']['view']['comments'] = array(
                'enabled' => true,
                'captcha' => false,
                'gravatar' => true,
                'publish' => 'admin',
                'rating' => true
            );
            $this->Configuration->setConfiguration($config);
            $this->Configuration->saveConfiguration();
            $this->app['monolog']->addDebug('Added kitcommand -> parameter -> view -> comments to the config.flexcontent.json');
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

        // Release 0.16
        $this->release_016();

        return $app['translator']->trans('Successfull updated the extension %extension%.',
            array('%extension%' => 'flexContent'));
    }
}
