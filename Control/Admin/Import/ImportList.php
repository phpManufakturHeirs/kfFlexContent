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

use phpManufaktur\flexContent\Control\Admin\Admin;
use Silex\Application;
use phpManufaktur\flexContent\Data\Import\ImportControl as ImportControlData;

class ImportList extends Admin
{
    protected $ImportControlData = null;
    protected static $language = null;
    protected static $import_type = 'WYSIWYG';
    protected static $import_status = 'PENDING';

    /**
     * (non-PHPdoc)
     * @see \phpManufaktur\flexContent\Control\Admin\Admin::initialize()
     */
    protected function initialize(Application $app)
    {
        parent::initialize($app);

        // set the language
        self::$language = $this->app['request']->get('form[language]', self::$config['content']['language']['default'], true);

        $this->ImportControlData = new ImportControlData($app);

        // check the import control table
        if (self::$config['content']['language']['select']) {
            foreach (self::$config['content']['language']['support'] as $language) {
                $this->ImportControlData->checkExternals($language);
            }
        }
        else {
            $this->ImportControlData->checkExternals(self::$config['content']['language']['default']);
        }
    }

    /**
     * Create the import control selection form
     */
    protected function getImportTypeForm()
    {
        $languages = array();
        foreach (self::$config['content']['language']['support'] as $language) {
            $languages[$language['code']] = $language['name'];
        }

        $form = $this->app['form.factory']->createBuilder('form')
        ->add('language', 'choice', array(
            'choices' => $languages,
            'empty_value' => '- please select -',
            'expanded' => false,
            'data' => self::$language,
            'disabled' => true
        ))
        ->add('status', 'choice', array(
            'choices' => $this->ImportControlData->getStatusValuesForForm(),
            'empty_value' => '- please select -',
            'expanded' => false,
            'data' => self::$import_status
        ))
        ->add('type', 'choice', array(
            'choices' => $this->ImportControlData->getTypeValuesForForm(),
            'empty_value' => '- please select -',
            'expanded' => false,
            'data' => self::$import_type
        ));
        return $form->getForm();
    }

    /**
     * Render the import control selection form and the result list
     */
    protected function renderImportList()
    {
        $select = $this->getImportTypeForm();

        $list = $this->ImportControlData->selectImportControlList(self::$language, self::$import_type, self::$import_status);

        return $this->app['twig']->render($this->app['utils']->getTemplateFile(
            '@phpManufaktur/flexContent/Template', 'admin/import.list.twig'),
            array(
                'usage' => self::$usage,
                'toolbar' => $this->getToolbar('import'),
                'alert' => $this->getAlert(),
                'select' => $select->createView(),
                'list' => $list,
                'route' => array(
                    'select' => '/flexcontent/editor/import/list/select'.self::$usage_param,
                    'import' => '/flexcontent/editor/import/id/{import_id}'.self::$usage_param,
                    'ignore' => '/flexcontent/editor/import/ignore/id/{import_id}/language/{language}/status/{status}/type/{type}'.self::$usage_param,
                    'pending' => '/flexcontent/editor/import/pending/id/{import_id}/language/{language}/status/{status}/type/{type}'.self::$usage_param
                )
            ));
    }

    public function ControllerImportIgnore(Application $app, $import_id, $language, $status, $type)
    {
        $this->initialize($app);

        $data = array(
            'import_status' => 'IGNORE'
        );
        $this->ImportControlData->update($import_id, $data);

        self::$language = $language;
        self::$import_status = $status;
        self::$import_type = $type;

        $this->setAlert('Changed status of import ID %import_id% to <i>ignore</i>',
            array('%import_id%' => $import_id), self::ALERT_TYPE_SUCCESS);

        return $this->renderImportList();
    }

    public function ControllerImportPending(Application $app, $import_id, $language, $status, $type)
    {
        $this->initialize($app);

        $data = array(
            'import_status' => 'PENDING'
        );
        $this->ImportControlData->update($import_id, $data);

        self::$language = $language;
        self::$import_status = $status;
        self::$import_type = $type;

        $this->setAlert('Changed status of import ID %import_id% to <i>pending</i>',
            array('%import_id%' => $import_id), self::ALERT_TYPE_SUCCESS);

        return $this->renderImportList();
    }

    /**
     * Controller check the import control selection and show the control list
     *
     * @param Application $app
     */
    public function ControllerImportSelect(Application $app)
    {
        $this->initialize($app);

        // get the form
        $select = $this->getImportTypeForm();
        // get the requested data
        $select->bind($this->app['request']);

        if ($select->isValid()) {
            // the form is valid
            $data = $select->getData();
            self::$language = isset($data['language']) ? $data['language'] : self::$config['content']['language']['default'];
            self::$import_status = $data['status'];
            self::$import_type = $data['type'];
        }
        else {
            // general error (timeout, CSFR ...)
            $this->setAlert('The form is not valid, please check your input and try again!', array(),
                self::ALERT_TYPE_DANGER, true, array('form_errors' => $form->getErrorsAsString(),
                    'method' => __METHOD__, 'line' => __LINE__));
        }

        return $this->renderImportList();
    }

    /**
     * Controller to show the import control list
     *
     * @param Application $app
     */
    public function ControllerImportList(Application $app)
    {
        $this->initialize($app);
        return $this->renderImportList();
    }
}
