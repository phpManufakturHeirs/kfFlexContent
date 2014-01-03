<?php

/**
 * flexContent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/event
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\flexContent\Control\Command;

use phpManufaktur\Basic\Control\kitCommand\Basic;
use Silex\Application;
use phpManufaktur\flexContent\Control\Configuration;
use phpManufaktur\flexContent\Data\Content\Category;
use phpManufaktur\flexContent\Data\Content\CategoryType;

class ActionCategory extends Basic
{
    protected static $parameter = null;
    protected static $config = null;
    protected static $language = null;
    protected static $use_iframe = null;

    protected $CategoryData = null;
    protected $CategoryTypeData = null;
    protected $ContentData = null;

    /**
     * (non-PHPdoc)
     * @see \phpManufaktur\Basic\Control\kitCommand\Basic::initParameters()
     */
    protected function initParameters(Application $app, $parameter_id=-1)
    {
        parent::initParameters($app, $parameter_id);

        $Config = new Configuration($app);
        self::$config = $Config->getConfiguration();

        self::$use_iframe = $app['request']->query->get('use_iframe', true);

        self::$language = $this->getCMSlocale();

        $this->CategoryData = new Category($app);
        $this->CategoryTypeData = new CategoryType($app);
    }

    /**
     * (non-PHPdoc)
     * @see \phpManufaktur\Basic\Control\Pattern\Alert::promptAlert()
     */
    public function promptAlert()
    {
        if (self::$use_iframe) {
            // we can use the default Bootstrap 3 alert response
            return parent::promptAlert();
        }
        else {
            // we must render the iframe free content template
            if (!isset(self::$parameter['css'])) {
                self::$parameter['css'] = self::$config['kitcommand']['parameter']['action']['view']['css'];
            }
            return $this->app['twig']->render($this->app['utils']->getTemplateFile(
                '@phpManufaktur/flexContent/Template', 'command/alert.twig',
                $this->getPreferredTemplateStyle()),
                array(
                    'basic' => $this->getBasicSettings(),
                    'parameter' => self::$parameter
                ));
        }
    }

    protected function showCategoryID()
    {
        if (false === ($category_type = $this->CategoryTypeData->select(self::$parameter['category_id']))) {
            $this->setAlert('The Category with the <strong>ID %id%</strong> does not exists for the language <strong>%language%</strong>!',
                array('%id%' => self::$parameter['category_id'], '%language%' => self::$language),
                self::ALERT_TYPE_DANGER, true, array(__METHOD__, __LINE__));
            return $this->promptAlert();
        }
//$this->setAlert('Test!');
        return $this->app['twig']->render($this->app['utils']->getTemplateFile(
            '@phpManufaktur/flexContent/Template', 'command/category.twig',
            $this->getPreferredTemplateStyle()),
            array(
                'basic' => $this->getBasicSettings(),
                'config' => self::$config,
                'parameter' => self::$parameter,
                'permalink_base_url' => CMS_URL.str_ireplace('{language}', strtolower(self::$language), self::$config['content']['permalink']['directory']),
                'category' => $category_type
            ));
    }

    /**
     * Controller to handle categories
     *
     * @param Application $app
     */
    public function ControllerCategory(Application $app)
    {
        $this->initParameters($app);

        // get the kitCommand parameters
        self::$parameter = $this->getCommandParameters();

        // check the CMS GET parameters
        $GET = $this->getCMSgetParameters();
        if (isset($GET['command']) && ($GET['command'] == 'flexcontent')) {
            // the command and parameters are set as GET from the CMS
            foreach ($GET as $key => $value) {
                if ($key == 'command') continue;
                self::$parameter[$key] = $value;
            }
            $this->setCommandParameters(self::$parameter);
        }

        // access the default parameters for action -> view from the configuration
        $default_parameter = self::$config['kitcommand']['parameter']['action']['category'];

        // the category ID is always needed!
        self::$parameter['category_id'] = isset(self::$parameter['category_id']) ? self::$parameter['category_id'] : -1;

        // optional: content ID
        self::$parameter['content_id'] = isset(self::$parameter['content_id']) ? self::$parameter['content_id'] : -1;

        // check wether to use the flexcontent.css or not (only needed if self::$parameter['use_iframe'] == false)
        self::$parameter['css'] = (isset(self::$parameter['css']) && ((self::$parameter['css'] == 0) || (strtolower(self::$parameter['css']) == 'false'))) ? false : $default_parameter['css'];

        // set the title level - default 1 = <h1>
        self::$parameter['title_level'] = (isset(self::$parameter['title_level']) && is_numeric(self::$parameter['title_level'])) ? self::$parameter['title_level'] : $default_parameter['title_level'];

        // show the category name above?
        self::$parameter['category_name'] = (isset(self::$parameter['category_name']) && ((self::$parameter['category_name'] == 0) || (strtolower(self::$parameter['category_name']) == 'false'))) ? false : $default_parameter['category_name'];

        // show the category description?
        self::$parameter['category_description'] = (isset(self::$parameter['category_description']) && ((self::$parameter['category_description'] == 0) || (strtolower(self::$parameter['category_description']) == 'false'))) ? false : $default_parameter['category_description'];

        // show the category image?
        self::$parameter['category_image'] = (isset(self::$parameter['category_image']) && ((self::$parameter['category_image'] == 0) || (strtolower(self::$parameter['category_image']) == 'false'))) ? false : $default_parameter['category_image'];

        if (self::$parameter['category_id'] > 0) {
            return $this->showCategoryID();
        }

        // Ooops ...
        $this->setAlert('Fatal error: Missing the category ID!', array(), self::ALERT_TYPE_DANGER, true, array(__METHOD__, __LINE__));
        return $this->promptAlert();
    }
}
