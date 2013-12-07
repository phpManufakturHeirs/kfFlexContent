<?php

/**
 * flexContent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://addons.phpmanufaktur.de/flexContent
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\flexContent\Control\Backend;

use Silex\Application;
use phpManufaktur\flexContent\Control\Configuration;

class Backend {

    protected $app = null;
    protected static $usage = null;
    protected static $usage_param = null;
    protected static $message = '';
    protected static $config = null;

    /**
     * Constructor
     */
    public function __construct(Application $app=null) {
        if (!is_null($app)) {
            $this->initialize($app);
        }
    }

    /**
     * Initialize the class with the needed parameters
     *
     * @param Application $app
     */
    protected function initialize(Application $app)
    {
        $this->app = $app;
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
     * @return multitype:multitype:string boolean
     */
    public function getToolbar($active) {
        $toolbar_array = array(
            'list' => array(
                'text' => 'List',
                'hint' => 'List of all flexContent articles',
                'link' => FRAMEWORK_URL.'/admin/flexcontent/list'.self::$usage_param,
                'active' => ($active == 'list')
            ),
            'edit' => array(
                'text' => 'Edit',
                'hint' => 'Create or edit a flexContent article',
                'link' => FRAMEWORK_URL.'/admin/flexcontent/edit'.self::$usage_param,
                'active' => ($active == 'edit')
            ),
            'tags' => array(
                'text' => 'Tags',
                'hint' => 'Create or edit tags',
                'link' => FRAMEWORK_URL.'/admin/flexcontent/tag/list'.self::$usage_param,
                'active' => ($active == 'tags')
            ),
            'categories' => array(
                'text' => 'Categories',
                'hint' => 'Create or edit categories',
                'link' => FRAMEWORK_URL.'/admin/flexcontent/category/list'.self::$usage_param,
                'active' => ($active == 'categories')
            ),
            'about' => array(
                'text' => 'About',
                'hint' => 'Information about the flexContent extension',
                'link' => FRAMEWORK_URL.'/admin/flexcontent/about'.self::$usage_param,
                'active' => ($active == 'about')
                ),
        );
        return $toolbar_array;
    }

    /**
     * @return the $message
     */
    public function getMessage ()
    {
        return self::$message;
    }

      /**
     * @param string $message
     */
    public function setMessage($message, $params=array())
    {
        self::$message .= $this->app['twig']->render($this->app['utils']->getTemplateFile('@phpManufaktur/flexContent/Template', 'backend/message.twig'),
            array('message' => $this->app['translator']->trans($message, $params)));
    }

    public function clearMessage()
    {
        self::$message = '';
    }

    /**
     * Check if a message is active
     *
     * @return boolean
     */
    public function isMessage()
    {
        return !empty(self::$message);
    }
 }
