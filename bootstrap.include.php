<?php

/**
 * flexContent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/flexContent
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

use phpManufaktur\Basic\Control\CMS\EmbeddedAdministration;

// not really needed but make error control more easy ...
global $app;

$roles = $app['security.role_hierarchy'];
if (!in_array('ROLE_FLEXCONTENT_ADMIN', $roles)) {
    $roles['ROLE_ADMIN'][] = 'ROLE_FLEXCONTENT_ADMIN';
    $roles['ROLE_EVENT_ADMIN'][] = 'ROLE_FLEXCONTENT_EDIT';
    $app['security.role_hierarchy'] = $roles;
}


// scan the /Locale directory and add all available languages
$app['utils']->addLanguageFiles(MANUFAKTUR_PATH.'/flexContent/Data/Locale');
// scan the /Locale/Custom directory and add all available languages
$app['utils']->addLanguageFiles(MANUFAKTUR_PATH.'/flexContent/Data/Locale/Custom');

/**
 * Use the EmbeddedAdministration feature to connect the extension with the CMS
 *
 * @link https://github.com/phpManufaktur/kitFramework/wiki/Extensions-%23-Embedded-Administration
 */
$app->get('/flexcontent/cms/{cms_information}', function ($cms_information) use ($app) {
    $administration = new EmbeddedAdministration($app);
    return $administration->route('/admin/flexcontent/about', $cms_information);
});

/**
 * The PermanentLink for flexContent uses the route /content.
 * Setup will create the directory /content in the CMS root and place a
 * .htaccess file which redirect to /content.
 */
$app->get('/content/{name}',
    'phpManufaktur\flexContent\Control\PermanentLink::ControllerName');
$app->get('/content/id/{content_id}',
    'phpManufaktur\flexContent\Control\PermanentLink::ControllerContentID');

if (file_exists(MANUFAKTUR_PATH.'/flexContent/bootstrap.include.inc')) {
    // the PermanentLink routes must exists similiar for installations
    // of the kitFramework in a subdirectory!
    include_once MANUFAKTUR_PATH.'/flexContent/bootstrap.include.inc';
}

/**
 * ADMIN routes
 */

$app->get('/admin/flexcontent/setup',
    // setup routine for flexContent
    'phpManufaktur\flexContent\Data\Setup\Setup::Controller');
$app->get('/admin/flexcontent/update',
    // update flexContent
    'phpManufaktur\flexContent\Data\Setup\Update::Controller');
$app->get('/admin/flexcontent/uninstall',
    // uninstall routine for flexContent
    'phpManufaktur\flexContent\Data\Setup\Uninstall::Controller');

$app->get('/admin/flexcontent',
    'phpManufaktur\flexContent\Control\Backend\About::Controller');
$app->get('/admin/flexcontent/about',
    'phpManufaktur\flexContent\Control\Backend\About::Controller');

$app->get('/admin/flexcontent/edit',
    'phpManufaktur\flexContent\Control\Backend\ContentEdit::ControllerEdit');
$app->get('/admin/flexcontent/edit/id/{content_id}',
    'phpManufaktur\flexContent\Control\Backend\ContentEdit::ControllerEdit');
$app->post('/admin/flexcontent/edit/check',
    'phpManufaktur\flexContent\Control\Backend\ContentEdit::ControllerEditCheck');
$app->post('/admin/flexcontent/edit/image/select',
    'phpManufaktur\flexContent\Control\Backend\ContentEdit::ControllerImage');
$app->get('/admin/flexcontent/edit/image/check/id/{content_id}',
    'phpManufaktur\flexContent\Control\Backend\ContentEdit::ControllerImageCheck');

$app->match('/admin/flexcontent/permalink/create',
    'phpManufaktur\flexContent\Control\Backend\PermaLinkResponse::ControllerPermaLink');

$app->get('/admin/flexcontent/list',
    'phpManufaktur\flexContent\Control\Backend\ContentList::ControllerList');
$app->get('/admin/flexcontent/list/page/{page}',
    'phpManufaktur\flexContent\Control\Backend\ContentList::ControllerList');
$app->post('/admin/flexcontent/search',
    'phpManufaktur\flexContent\Control\Backend\ContentSearch::ControllerSearch');

$app->get('/admin/flexcontent/tag/autocomplete',
    'phpManufaktur\flexContent\Control\Backend\TagResponse::ControllerAutocomplete');
$app->get('/admin/flexcontent/tag/list',
    'phpManufaktur\flexContent\Control\Backend\ContentTag::ControllerList');
$app->get('/admin/flexcontent/tag/list/page/{page}',
    'phpManufaktur\flexContent\Control\Backend\ContentTag::ControllerList');
$app->get('/admin/flexcontent/tag/create',
    'phpManufaktur\flexContent\Control\Backend\ContentTag::ControllerEdit');
$app->get('/admin/flexcontent/tag/edit/id/{tag_id}',
    'phpManufaktur\flexContent\Control\Backend\ContentTag::ControllerEdit');
$app->post('/admin/flexcontent/tag/edit/check',
    'phpManufaktur\flexContent\Control\Backend\ContentTag::ControllerEditCheck');
$app->post('/admin/flexcontent/tag/image/select',
    'phpManufaktur\flexContent\Control\Backend\ContentTag::ControllerImage');
$app->get('/admin/flexcontent/tag/image/check/id/{tag_id}',
    'phpManufaktur\flexContent\Control\Backend\ContentTag::ControllerImageCheck');

$app->get('/admin/flexcontent/category/list',
    'phpManufaktur\flexContent\Control\Backend\ContentCategory::ControllerList');
$app->get('/admin/flexcontent/category/list/page/{page}',
    'phpManufaktur\flexContent\Control\Backend\ContentCategory::ControllerList');
$app->get('/admin/flexcontent/category/create',
    'phpManufaktur\flexContent\Control\Backend\ContentCategory::ControllerEdit');
$app->get('/admin/flexcontent/category/edit/id/{category_id}',
    'phpManufaktur\flexContent\Control\Backend\ContentCategory::ControllerEdit');
$app->post('/admin/flexcontent/category/edit/check',
    'phpManufaktur\flexContent\Control\Backend\ContentCategory::ControllerEditCheck');
$app->post('/admin/flexcontent/category/image/select',
    'phpManufaktur\flexContent\Control\Backend\ContentCategory::ControllerImage');
$app->get('/admin/flexcontent/category/image/check/id/{category_id}',
    'phpManufaktur\flexContent\Control\Backend\ContentCategory::ControllerImageCheck');
