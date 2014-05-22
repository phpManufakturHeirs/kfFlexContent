<?php

/**
 * flexContent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://addons.phpmanufaktur.de/flexContent
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\flexContent\Control\Admin;

use Silex\Application;
use phpManufaktur\flexContent\Control\Configuration;
use phpManufaktur\Basic\Control\Pattern\Alert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class Admin extends Alert
{

    protected static $usage = null;
    protected static $usage_param = null;
    protected static $config = null;

    /**
     * Initialize the class with the needed parameters
     *
     * @param Application $app
     */
    protected function initialize(Application $app)
    {
        parent::initialize($app);

        $cms = $this->app['request']->get('usage');
        self::$usage = is_null($cms) ? 'framework' : $cms;
        self::$usage_param = (self::$usage != 'framework') ? '?usage='.self::$usage : '';
        // set the locale from the CMS locale
        if (self::$usage != 'framework') {
            $app['translator']->setLocale($this->app['session']->get('CMS_LOCALE', 'en'));
        }
        $Configuration = new Configuration($app);
        self::$config = $Configuration->getConfiguration();
    }

    /**
     * Get the toolbar for all backend dialogs
     *
     * @param string $active dialog
     * @return array
     */
    public function getToolbar($active) {
        $toolbar = array();
        foreach (self::$config['nav_tabs']['order'] as $tab) {
            switch ($tab) {
                case 'list':
                    $toolbar[$tab] = array(
                        'name' => 'list',
                        'text' => 'List',
                        'hint' => 'List of all flexContent articles',
                        'link' => FRAMEWORK_URL.'/flexcontent/editor/list'.self::$usage_param,
                        'active' => ($active == 'list')
                    );
                    break;
                case 'edit':
                    $toolbar[$tab] = array(
                        'name' => 'edit',
                        'text' => 'Edit',
                        'hint' => 'Create or edit a flexContent article',
                        'link' => FRAMEWORK_URL.'/flexcontent/editor/edit'.self::$usage_param,
                        'active' => ($active == 'edit')
                    );
                    break;
                case 'tags':
                    $toolbar[$tab] = array(
                        'name' => 'tags',
                        'text' => 'Hashtags',
                        'hint' => 'Create or edit hashtags',
                        'link' => FRAMEWORK_URL.'/flexcontent/editor/buzzword/list'.self::$usage_param,
                        'active' => ($active == 'tags')
                    );
                    break;
                case 'categories':
                    $toolbar[$tab] = array(
                        'name' => 'categories',
                        'text' => 'Categories',
                        'hint' => 'Create or edit categories',
                        'link' => FRAMEWORK_URL.'/flexcontent/editor/category/list'.self::$usage_param,
                        'active' => ($active == 'categories')
                    );
                    break;
                case 'rss':
                    $toolbar[$tab] = array(
                        'name' => 'rss',
                        'text' => 'RSS',
                        'hint' => 'Organize RSS Feeds for the flexContent articles',
                        'link' => FRAMEWORK_URL.'/flexcontent/editor/rss/channel/list'.self::$usage_param,
                        'active' => ($active == 'rss')
                    );
                    break;
                case 'import':
                    $toolbar[$tab] = array(
                        'name' => 'import',
                        'text' => 'Import',
                        'hint' => 'Import WYSIWYG and Blog contents',
                        'link' => FRAMEWORK_URL.'/flexcontent/editor/import/list'.self::$usage_param,
                        'active' => ($active == 'import')
                    );
                    break;
                case 'about':
                    $toolbar[$tab] = array(
                        'name' => 'about',
                        'text' => 'About',
                        'hint' => 'Information about the flexContent extension',
                        'link' => FRAMEWORK_URL.'/flexcontent/editor/about'.self::$usage_param,
                        'active' => ($active == 'about')
                    );
                    break;
            }
        }

        if (!self::$config['admin']['import']['enabled']) {
            // show the import only, if enabled!
            unset($toolbar['import']);
        }

        if (!self::$config['rss']['enabled']) {
            // show the rss only, if enabled!
            unset($toolbar['rss']);
        }

        return $toolbar;
    }

    /**
     * Controller to select the default navigation tab.
     *
     * @param Application $app
     * @throws \Exception
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function ControllerSelectDefaultTab(Application $app)
    {
        $this->initialize($app);

        switch (self::$config['nav_tabs']['default']) {
            case 'about':
                $route = '/flexcontent/editor/about';
                break;
            case 'import':
                $route = '/flexcontent/editor/import/list';
                break;
            case 'rss':
                $route = '/flexcontent/editor/rss/channel/list';
                break;
            case 'categories':
                $route = '/flexcontent/editor/category/list';
                break;
            case 'tags':
                $route = '/flexcontent/editor/buzzword/list';
                break;
            case 'edit':
                $route = '/flexcontent/editor/edit';
                break;
            case 'list':
                $route = '/flexcontent/editor/list';
                break;
            default:
                throw new \Exception('Invalid default nav_tab in configuration: '.self::$config['nav_tabs']['default']);
        }

        $subRequest = Request::create($route, 'GET', array('usage' => self::$usage));
        return $app->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    }
 }
