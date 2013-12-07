<?php

/**
 * flexContent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/flexContent
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\flexContent\Control;

use Silex\Application;

class PermanentLink
{
    protected $app = null;

    public function ControllerContentID(Application $app, $content_id)
    {
        return "id: $content_id";
    }

    public function ControllerName(Application $app, $name)
    {

        return "<br>name:$name";
    }

}

