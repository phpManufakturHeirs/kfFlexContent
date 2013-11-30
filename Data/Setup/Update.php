<?php

/**
 * Content
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/content
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\Content\Data\Setup;

use Silex\Application;

class Update
{
    protected $app = null;

    /**
     * Execute the update for Content
     *
     * @param Application $app
     */
    public function exec(Application $app)
    {
        $this->app = $app;


        return $app['translator']->trans('Successfull updated the extension %extension%.',
            array('%extension%' => 'Content'));
    }
}
