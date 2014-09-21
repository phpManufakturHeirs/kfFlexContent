<?php

/**
 * flexContent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/flexContent
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

    const SESSION_CATEGORY_ID = 'FLEXCONTENT_ACTIVE_CATEGORY_ID';

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

        if (null !== ($locale = $this->app['session']->get('CMS_LOCALE'))) {
            // set the locale from the CMS locale
            $app['translator']->setLocale($locale);
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
                        'text' => $this->app['translator']->trans('List'),
                        'hint' => $this->app['translator']->trans('List of all flexContent articles'),
                        'link' => FRAMEWORK_URL.'/flexcontent/editor/list',
                        'active' => ($active == 'list')
                    );
                    break;
                case 'edit':
                    $toolbar[$tab] = array(
                        'name' => 'edit',
                        'text' => ($active === 'edit') ? $this->app['translator']->trans('Edit article') : $this->app['translator']->trans('Create article'),
                        'hint' => $this->app['translator']->trans('Create or edit a flexContent article'),
                        'link' => FRAMEWORK_URL.'/flexcontent/editor/edit',
                        'active' => ($active === 'edit')
                    );
                    break;
                case 'tags':
                    $toolbar[$tab] = array(
                        'name' => 'tags',
                        'text' => $this->app['translator']->trans('Hashtags'),
                        'hint' => $this->app['translator']->trans('Create or edit hashtags'),
                        'link' => FRAMEWORK_URL.'/flexcontent/editor/buzzword/list',
                        'active' => ($active == 'tags')
                    );
                    break;
                case 'categories':
                    $toolbar[$tab] = array(
                        'name' => 'categories',
                        'text' => $this->app['translator']->trans('Categories'),
                        'hint' => $this->app['translator']->trans('Create or edit categories'),
                        'link' => FRAMEWORK_URL.'/flexcontent/editor/category/list',
                        'active' => ($active == 'categories')
                    );
                    break;
                case 'rss':
                    $toolbar[$tab] = array(
                        'name' => 'rss',
                        'text' => $this->app['translator']->trans('RSS'),
                        'hint' => $this->app['translator']->trans('Organize RSS Feeds for the flexContent articles'),
                        'link' => FRAMEWORK_URL.'/flexcontent/editor/rss/channel/list',
                        'active' => ($active == 'rss')
                    );
                    break;
                case 'import':
                    $toolbar[$tab] = array(
                        'name' => 'import',
                        'text' => $this->app['translator']->trans('Import'),
                        'hint' => $this->app['translator']->trans('Import WYSIWYG and Blog contents'),
                        'link' => FRAMEWORK_URL.'/flexcontent/editor/import/list',
                        'active' => ($active == 'import')
                    );
                    break;
                case 'about':
                    $toolbar[$tab] = array(
                        'name' => 'about',
                        'text' => $this->app['translator']->trans('About'),
                        'hint' => $this->app['translator']->trans('Information about the flexContent extension'),
                        'link' => FRAMEWORK_URL.'/flexcontent/editor/about',
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
