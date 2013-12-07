<?php

/**
 * flexContent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/flexContent
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

$app->get('%subdirectory%/content/{name}',
    'phpManufaktur\flexContent\Control\PermanentLink::ControllerName');
$app->get('%subdirectory%/content/id/{content_id}',
    'phpManufaktur\flexContent\Control\PermanentLink::ControllerContentID');
