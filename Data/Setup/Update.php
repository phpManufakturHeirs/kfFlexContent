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

class Update
{
    protected $app = null;

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

        return $app['translator']->trans('Successfull updated the extension %extension%.',
            array('%extension%' => 'flexContent'));
    }
}
