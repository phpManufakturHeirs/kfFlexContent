<?php

/**
 * flexContent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/event
 * @copyright 2014 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\flexContent\Control\Admin\Import;

use phpManufaktur\flexContent\Control\Admin\Admin;
use Silex\Application;
use phpManufaktur\flexContent\Data\Import\ImportControl as ImportControlData;

class ImportDialog extends Admin
{
    protected $ImportControlData = null;
    protected static $language = null;
    protected static $import_id = null;
    protected static $data_handling = null;

    /**
     * Get the form for the import dialog
     *
     */
    protected function getImportDialog()
    {
        $form = $this->app['form.factory']->createBuilder('form')
        ->add('import_id', 'hidden', array(
            'data' => self::$import_id
        ))
        ->add('data_handling', 'choice', array(
            'choices' => array('UNCHANGED' => 'DATA_UNCHANGED', 'CLEAN_UP' => 'DATA_CLEAN_UP', 'STRIP_TAGS' => 'DATA_STRIP_TAGS'),
            'expanded' => true,
            'data' => self::$config['admin']['import']['data']['handling']
        ))
        ;
        return $form->getForm();
    }

    /**
     * Render the import dialog for output
     */
    protected function renderImportDialog()
    {
        $form = $this->getImportDialog();

        return $this->app['twig']->render($this->app['utils']->getTemplateFile(
            '@phpManufaktur/flexContent/Template', 'admin/import.handling.twig'),
            array(
                'usage' => self::$usage,
                'toolbar' => $this->getToolbar('import'),
                'alert' => $this->getAlert(),
                'form' => $form->createView(),
                'route' => array(
                    'action' => '/admin/flexcontent/import/execute'.self::$usage_param,
                )
            ));
    }

    /**
     * Prepare the content
     *
     * @param string $content
     * @return string
     */
    protected function prepareContent($content)
    {
        // replace the WebsiteBaker SYSVAR:MEDIA_REL placeholder
        $content = str_ireplace('{SYSVAR:MEDIA_REL}', CMS_MEDIA_URL, $content);

        // replace the extendedWYSIWYG placeholder
        $content = str_ireplace('~~ wysiwyg replace[CMS_MEDIA_URL] ~~', CMS_MEDIA_URL, $content);

        return $content;
    }

    /**
     * Execute the import
     *
     * @return string
     */
    protected function executeImport()
    {
        // get the import control recored
        $ImportControlData = new ImportControlData($this->app);
        $import_control = $ImportControlData->select(self::$import_id);

        if (false === ($content = $ImportControlData->selectContentData(self::$import_id))) {
            $this->setAlert("Can't read the content data from the given import ID %import_id%.",
                array('%import_id%' => self::$import_id), self::ALERT_TYPE_WARNING);
            return $this->renderImportDialog();
        }

        $content['content'] = $this->prepareContent($content['content']);

        print_r($content);
        return __METHOD__;
    }

    /**
     * Controller to show the import control list
     *
     * @param Application $app
     */
    public function ControllerImport(Application $app, $import_id)
    {
        $this->initialize($app);

        self::$import_id = $import_id;
        return $this->renderImportDialog();
    }

    /**
     * Controller to execute the import
     *
     * @param Application $app
     * @return string
     */
    public function ControllerExecute(Application $app)
    {
        $this->initialize($app);

        // get the form
        $form = $this->getImportDialog();
        // get the requested data
        $form->bind($this->app['request']);

        if ($form->isValid()) {
            // the form is valid
            $data = $form->getData();
            self::$import_id = $data['import_id'];
            self::$data_handling = $data['data_handling'];
            // execute the import
            return $this->executeImport();
        }
        else {
            // general error (timeout, CSFR ...)
            $this->setAlert('The form is not valid, please check your input and try again!', array(), self::ALERT_TYPE_DANGER);
            return $this->ControllerImport($app, self::$import_id);
        }
    }
}
