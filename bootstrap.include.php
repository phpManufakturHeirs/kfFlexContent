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
    'phpManufaktur\flexContent\Control\Admin\About::Controller');
$app->get('/admin/flexcontent/about',
    'phpManufaktur\flexContent\Control\Admin\About::Controller');

$app->get('/admin/flexcontent/edit',
    'phpManufaktur\flexContent\Control\Admin\ContentEdit::ControllerEdit');
$app->get('/admin/flexcontent/edit/id/{content_id}',
    'phpManufaktur\flexContent\Control\Admin\ContentEdit::ControllerEdit');
$app->post('/admin/flexcontent/edit/check',
    'phpManufaktur\flexContent\Control\Admin\ContentEdit::ControllerEditCheck');
$app->post('/admin/flexcontent/edit/image/select',
    'phpManufaktur\flexContent\Control\Admin\ContentEdit::ControllerImage');
$app->get('/admin/flexcontent/edit/image/check/id/{content_id}',
    'phpManufaktur\flexContent\Control\Admin\ContentEdit::ControllerImageCheck');

$app->match('/admin/flexcontent/permalink/create',
    'phpManufaktur\flexContent\Control\Admin\PermaLinkResponse::ControllerPermaLink');

$app->get('/admin/flexcontent/list',
    'phpManufaktur\flexContent\Control\Admin\ContentList::ControllerList');
$app->get('/admin/flexcontent/list/page/{page}',
    'phpManufaktur\flexContent\Control\Admin\ContentList::ControllerList');
$app->post('/admin/flexcontent/search',
    'phpManufaktur\flexContent\Control\Admin\ContentSearch::ControllerSearch');

$app->get('/admin/flexcontent/tag/autocomplete',
    'phpManufaktur\flexContent\Control\Admin\TagResponse::ControllerAutocomplete');
$app->get('/admin/flexcontent/tag/list',
    'phpManufaktur\flexContent\Control\Admin\ContentTag::ControllerList');
$app->get('/admin/flexcontent/tag/list/page/{page}',
    'phpManufaktur\flexContent\Control\Admin\ContentTag::ControllerList');
$app->get('/admin/flexcontent/tag/create',
    'phpManufaktur\flexContent\Control\Admin\ContentTag::ControllerEdit');
$app->get('/admin/flexcontent/tag/edit/id/{tag_id}',
    'phpManufaktur\flexContent\Control\Admin\ContentTag::ControllerEdit');
$app->post('/admin/flexcontent/tag/edit/check',
    'phpManufaktur\flexContent\Control\Admin\ContentTag::ControllerEditCheck');
$app->post('/admin/flexcontent/tag/image/select',
    'phpManufaktur\flexContent\Control\Admin\ContentTag::ControllerImage');
$app->get('/admin/flexcontent/tag/image/check/id/{tag_id}',
    'phpManufaktur\flexContent\Control\Admin\ContentTag::ControllerImageCheck');

$app->get('/admin/flexcontent/category/list',
    'phpManufaktur\flexContent\Control\Admin\ContentCategory::ControllerList');
$app->get('/admin/flexcontent/category/list/page/{page}',
    'phpManufaktur\flexContent\Control\Admin\ContentCategory::ControllerList');
$app->get('/admin/flexcontent/category/create',
    'phpManufaktur\flexContent\Control\Admin\ContentCategory::ControllerEdit');
$app->get('/admin/flexcontent/category/edit/id/{category_id}',
    'phpManufaktur\flexContent\Control\Admin\ContentCategory::ControllerEdit');
$app->post('/admin/flexcontent/category/edit/check',
    'phpManufaktur\flexContent\Control\Admin\ContentCategory::ControllerEditCheck');
$app->post('/admin/flexcontent/category/image/select',
    'phpManufaktur\flexContent\Control\Admin\ContentCategory::ControllerImage');
$app->get('/admin/flexcontent/category/image/check/id/{category_id}',
    'phpManufaktur\flexContent\Control\Admin\ContentCategory::ControllerImageCheck');

/**
 * kitCommand routes
 */

$app->post('/command/flexcontent',
    // create the iFrame for the kitCommands and execute the route /content/action
    'phpManufaktur\flexContent\Control\Command\flexContentFrame::controllerFlexContentFrame')
    ->setOption('info', MANUFAKTUR_PATH.'/flexContent/command.flexcontent.json');

$app->get('/command/flexcontent/getheader/id/{content_id}',
    // return header information to set title, description and keywords
    'phpManufaktur\flexContent\Control\Command\getHeader::controllerGetHeader');

$app->get('/flexcontent/action',
    'phpManufaktur\flexContent\Control\Command\Action::controllerAction');
