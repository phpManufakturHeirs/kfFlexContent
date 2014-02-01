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

// grant the ROLE hierarchy for the flexContent ROLES
$roles = $app['security.role_hierarchy'];
if (!in_array('ROLE_FLEXCONTENT_ADMIN', $roles)) {
    $roles['ROLE_ADMIN'][] = 'ROLE_FLEXCONTENT_ADMIN';
    $roles['ROLE_FLEXCONTENT_ADMIN'][] = 'ROLE_FLEXCONTENT_EDITOR';
    $app['security.role_hierarchy'] = $roles;
}

// add a protected area and access rules
$access_rules = $app['security.access_rules'];
if (!in_array('^/flexcontent/editor', $access_rules)) {
    $access_rules[] = array('^/flexcontent/editor', 'ROLE_FLEXCONTENT_EDITOR');
    $app['security.access_rules'] = $access_rules;
}

// add a access point for flexContent
$entry_points = $app['security.role_entry_points'];
if (!in_array('ROLE_FLEXCONTENT_EDITOR', $entry_points)) {
    $entry_points['ROLE_FLEXCONTENT_EDITOR'] = array(
        array(
            'route' => '/flexcontent/editor',
            'name' => 'flexContent',
            'info' => 'Organize and present contents in a flexible way',
            'icon' => array(
                'path' => '/extension/phpmanufaktur/phpManufaktur/flexContent/extension.jpg',
                'url' => MANUFAKTUR_URL.'/flexContent/extension.jpg'
            )
        )
    );
    $app['security.role_entry_points'] = $entry_points;
}

// add all ROLES provided and used by flexContent
$roles = array(
    'ROLE_FLEXCONTENT_ADMIN',
    'ROLE_FLEXCONTENT_EDITOR'
);
$roles_provided = $app['security.roles_provided'];
if (!in_array($roles, $roles_provided)) {
    foreach ($roles as $role) {
        if (!in_array($role, $roles_provided)) {
            $roles_provided[] = $role;
        }
    }
    $app['security.roles_provided'] = $roles_provided;
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
    return $administration->route('/flexcontent/editor/about', $cms_information, 'ROLE_FLEXCONTENT_EDITOR');
});

/**
 * The PermanentLink for flexContent uses configurable routes.
 * Setup will create the needed directories in the CMS root and place a
 * .htaccess file which redirect to the routes.
 */
if (file_exists(MANUFAKTUR_PATH.'/flexContent/bootstrap.include.inc')) {
    // the PermanentLink routes must exists and will be created by the setup routine!
    include_once MANUFAKTUR_PATH.'/flexContent/bootstrap.include.inc';
}
else {
    $app['monolog']->addError('Missing the permalink routes in /flexcontent/bootstrap.include.inc!',
        array(__FILE__, __LINE__));
}

/**
 * ADMIN routes
 */

$app->get('/admin/flexcontent/setup',
    // setup routine for flexContent
    'phpManufaktur\flexContent\Data\Setup\Setup::Controller');
$app->get('/admin/flexcontent/setup/pagesection',
    // additional setup flexContent as Page Section
    'phpManufaktur\flexContent\Data\Setup\SetupPageSection::ControllerSetupPageSection');
$app->get('/admin/flexcontent/update',
    // update flexContent
    'phpManufaktur\flexContent\Data\Setup\Update::Controller');
$app->get('/admin/flexcontent/uninstall',
    // uninstall routine for flexContent
    'phpManufaktur\flexContent\Data\Setup\Uninstall::Controller');


/**
 * EDITOR routes (PROTECTED)
 */

$app->get('/flexcontent/editor',
    'phpManufaktur\flexContent\Control\Admin\About::Controller');
$app->get('/flexcontent/editor/about',
    'phpManufaktur\flexContent\Control\Admin\About::Controller');

$app->get('/flexcontent/editor/edit',
    'phpManufaktur\flexContent\Control\Admin\ContentEdit::ControllerEdit');
$app->post('/flexcontent/editor/edit/language/check',
    'phpManufaktur\flexContent\Control\Admin\ContentEdit::ControllerLanguageCheck');
$app->get('/flexcontent/editor/edit/id/{content_id}',
    'phpManufaktur\flexContent\Control\Admin\ContentEdit::ControllerEdit');
$app->post('/flexcontent/editor/edit/check',
    'phpManufaktur\flexContent\Control\Admin\ContentEdit::ControllerEditCheck');
$app->post('/flexcontent/editor/edit/image/select',
    'phpManufaktur\flexContent\Control\Admin\ContentEdit::ControllerImage');
$app->get('/flexcontent/editor/edit/image/check/id/{content_id}',
    'phpManufaktur\flexContent\Control\Admin\ContentEdit::ControllerImageCheck');

$app->match('/flexcontent/editor/permalink/create',
    'phpManufaktur\flexContent\Control\Admin\PermaLinkResponse::ControllerPermaLink');
$app->match('/flexcontent/editor/permalink/create/category',
    'phpManufaktur\flexContent\Control\Admin\PermaLinkResponse::ControllerPermaLinkCategory');
$app->match('/flexcontent/editor/permalink/create/tag',
    'phpManufaktur\flexContent\Control\Admin\PermaLinkResponse::ControllerPermaLinkTag');

$app->get('/flexcontent/editor/list',
    'phpManufaktur\flexContent\Control\Admin\ContentList::ControllerList');
$app->get('/flexcontent/editor/list/page/{page}',
    'phpManufaktur\flexContent\Control\Admin\ContentList::ControllerList');
$app->post('/flexcontent/editor/search',
    'phpManufaktur\flexContent\Control\Admin\ContentSearch::ControllerSearch');

// #hashtag functions
$app->get('/flexcontent/editor/tag/autocomplete',
    'phpManufaktur\flexContent\Control\Admin\TagResponse::ControllerAutocomplete');
$app->get('/flexcontent/editor/tag/list',
    'phpManufaktur\flexContent\Control\Admin\ContentTag::ControllerList');
$app->get('/flexcontent/editor/tag/list/page/{page}',
    'phpManufaktur\flexContent\Control\Admin\ContentTag::ControllerList');
$app->get('/flexcontent/editor/tag/create',
    'phpManufaktur\flexContent\Control\Admin\ContentTag::ControllerEdit');
$app->post('/flexcontent/editor/tag/language/check',
    'phpManufaktur\flexContent\Control\Admin\ContentTag::ControllerLanguageCheck');
$app->get('/flexcontent/editor/tag/edit/id/{tag_id}',
    'phpManufaktur\flexContent\Control\Admin\ContentTag::ControllerEdit');
$app->post('/flexcontent/editor/tag/edit/check',
    'phpManufaktur\flexContent\Control\Admin\ContentTag::ControllerEditCheck');
$app->post('/flexcontent/editor/tag/image/select',
    'phpManufaktur\flexContent\Control\Admin\ContentTag::ControllerImage');
$app->get('/flexcontent/editor/tag/image/check/id/{tag_id}',
    'phpManufaktur\flexContent\Control\Admin\ContentTag::ControllerImageCheck');

// category functions
$app->get('/flexcontent/editor/category/list',
    'phpManufaktur\flexContent\Control\Admin\ContentCategory::ControllerList');
$app->get('/flexcontent/editor/category/list/page/{page}',
    'phpManufaktur\flexContent\Control\Admin\ContentCategory::ControllerList');
$app->get('/flexcontent/editor/category/create',
    'phpManufaktur\flexContent\Control\Admin\ContentCategory::ControllerEdit');
$app->post('/flexcontent/editor/category/language/check',
    'phpManufaktur\flexContent\Control\Admin\ContentCategory::ControllerLanguageCheck');
$app->get('/flexcontent/editor/category/edit/id/{category_id}',
    'phpManufaktur\flexContent\Control\Admin\ContentCategory::ControllerEdit');
$app->post('/flexcontent/editor/category/edit/check',
    'phpManufaktur\flexContent\Control\Admin\ContentCategory::ControllerEditCheck');
$app->post('/flexcontent/editor/category/image/select',
    'phpManufaktur\flexContent\Control\Admin\ContentCategory::ControllerImage');
$app->get('/flexcontent/editor/category/image/check/id/{category_id}',
    'phpManufaktur\flexContent\Control\Admin\ContentCategory::ControllerImageCheck');

// import functions
$app->get('/flexcontent/editor/import/list',
    'phpManufaktur\flexContent\Control\Admin\Import\ImportList::ControllerImportList');
$app->post('/flexcontent/editor/import/list/select',
    'phpManufaktur\flexContent\Control\Admin\Import\ImportList::ControllerImportSelect');
$app->get('/flexcontent/editor/import/ignore/id/{import_id}/language/{language}/status/{status}/type/{type}',
    'phpManufaktur\flexContent\Control\Admin\Import\ImportList::ControllerImportIgnore');
$app->get('/flexcontent/editor/import/pending/id/{import_id}/language/{language}/status/{status}/type/{type}',
    'phpManufaktur\flexContent\Control\Admin\Import\ImportList::ControllerImportPending');
$app->get('/flexcontent/editor/import/id/{import_id}',
    'phpManufaktur\flexContent\Control\Admin\Import\ImportDialog::ControllerImport');
$app->post('/flexcontent/editor/import/execute',
    'phpManufaktur\flexContent\Control\Admin\Import\ImportDialog::ControllerExecute');

// RSS functions
$app->get('/flexcontent/editor/rss/list',
    'phpManufaktur\flexContent\Control\Admin\ContentRSS::ControllerList');

/**
 * CMS Search function
 */

$app->post('/search/command/flexcontent',
    'phpManufaktur\flexContent\Control\cmsSearch::controllerSearch');

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
