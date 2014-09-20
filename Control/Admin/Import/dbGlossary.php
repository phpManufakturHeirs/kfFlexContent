<?php

/**
 * flexContent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/flexContent
 * @copyright 2014 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\flexContent\Control\Admin\Import;

use Silex\Application;
use phpManufaktur\Basic\Control\Pattern\Alert;
use phpManufaktur\flexContent\Data\Content\CategoryType as DataCategoryType;

class dbGlossary extends Alert
{
    protected static $usage = null;
    protected $DataCategoryType = null;

    /**
     * Initialize the class with the needed parameters
     *
     * @param Application $app
     */
    protected function initialize(Application $app)
    {
        parent::initialize($app);

        self::$usage = $this->app['request']->get('usage', 'framework');

        // set the locale from the CMS locale
        if (self::$usage != 'framework') {
            $app['translator']->setLocale($this->app['session']->get('CMS_LOCALE', 'en'));
        }

        $this->DataCategoryType = new DataCategoryType($app);
    }

    public function Controller(Application $app)
    {
        $this->initialize($app);

        if (false === $this->DataCategoryType->selectCategoriesByType('GLOSSARY')) {
            $this->setAlert('Please create a flexContent category of type <var>GLOSSARY</var> before you import CSV data from dbGlossary.',
                array(), self::ALERT_TYPE_INFO);
        }

        return $this->app['twig']->render($this->app['utils']->getTemplateFile(
            '@phpManufaktur/flexContent/Template', 'admin/import.dbglossary.twig'),
            array(
                'usage' => self::$usage,
                'alert' => $this->getAlert(),
                'form' => null,
            ));

    }
}
