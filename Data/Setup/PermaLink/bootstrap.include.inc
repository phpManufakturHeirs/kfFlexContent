<?php

/**
 * flexContent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/flexContent
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

// permalink route to a content
$app->get('%subdirectory%%permalink%/{name}',
    'phpManufaktur\flexContent\Control\PermanentLink::ControllerContentName')
    ->value('language', '%default_language%');

// permalink route to a category
$app->get('%subdirectory%%permalink%/category/{name}',
    'phpManufaktur\flexContent\Control\PermanentLink::ControllerCategoryName')
    ->value('language', '%default_language%');

// permalink route to a tag
$app->get('%subdirectory%%permalink%/buzzword/{name}',
    'phpManufaktur\flexContent\Control\PermanentLink::ControllerTagName')
    ->value('language', '%default_language%');

// permalink route to a list (content, category, tag ...)
$app->get('%subdirectory%%permalink%/list/{name}',
    'phpManufaktur\flexContent\Control\PermanentLink::ControllerListName')
    ->value('language', '%default_language%');

// permalink route to a FAQ
$app->get('%subdirectory%%permalink%/faq/{name}',
    'phpManufaktur\flexContent\Control\PermanentLink::ControllerFAQName')
    ->value('language', '%default_language%');

// permalink route to a RSS Channel
$app->get('%subdirectory%%rsslink%/{channel}',
    'phpManufaktur\flexContent\Control\PermanentLink::ControllerRSSChannel')
    ->value('language', '%default_language%');
