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
use phpManufaktur\Basic\Data\CMS\Page;

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
     * Prepare the content, replace placeholders with contents a.s.o. ...
     *
     * @param string $content
     * @return string
     */
    protected function prepareContent($content)
    {
        $cmsPages = new Page($this->app);
        $page_directory = $cmsPages->getPageDirectory();
        $page_extension = $cmsPages->getPageExtension();

        $DOM = new \DOMDocument;
        // enable internal error handling
        libxml_use_internal_errors(true);

        $DOM->preserveWhiteSpace = false;

        // enshure the correct encoding of the content
        $encoding_str = '<?xml encoding="UTF-8">';
        if (!$DOM->loadHTML($encoding_str.$content)) {
            foreach (libxml_get_errors() as $error) {
                // handle errors here
                $this->app['monolog']->addError('[flexContent] '.$error->message, array(__METHOD__, __LINE__));
                libxml_clear_errors();
            }
            throw new \Exception('Problem processing the content - check the logfile for more information.');
        }
        libxml_clear_errors();

        $search = array('{SYSVAR:MEDIA_REL}','~~ wysiwyg replace[CMS_MEDIA_URL] ~~',
            '{flexContent:FRAMEWORK_URL}','{flexContent:CMS_MEDIA_URL}', '{flexContent:CMS_URL}');
        $replace = array(CMS_MEDIA_URL, CMS_MEDIA_URL, FRAMEWORK_URL, CMS_MEDIA_URL, CMS_URL);

        foreach ($DOM->getElementsByTagName('a') as $link) {
            $href = $link->getAttribute('href');
            $href = urldecode($href);

            $href = str_ireplace($search, $replace, $href);

            $pattern = '/\[wblink([0-9]+)\]/isU';
            if (preg_match($pattern, $href, $id)) {
                if (false !== ($page_link = $cmsPages->getPageLinkByPageID($id[1]))) {
                    $href = CMS_URL.$page_directory.$page_link.$page_extension;
                }
                else {
                    $this->app['monolog']->addDebug('Replaced the link '.$id[0].' with '.CMS_URL.' because the page ID '.$id[1].' does not exists!');
                    $href = CMS_URL;
                }
            }

            // set the href again
            $link->setAttribute('href', $href);
        }

        if (self::$config['admin']['import']['data']['images']['move']) {
            foreach ($DOM->getElementsByTagName('img') as $image) {
                $src = $image->getAttribute('src');
                $src = urldecode($src);

                $src = str_ireplace($search, $replace, $src);
echo "??<br>";
                if (strpos($src, CMS_MEDIA_URL) == 0) {
                    $path = substr($src, strlen(CMS_MEDIA_URL));
                    // copy the image to the framework /media directory
                    $this->app['filesystem']->copy(CMS_MEDIA_PATH.$path, FRAMEWORK_MEDIA_PATH.$path);
                    $src = FRAMEWORK_MEDIA_URL.$path;
                    // set the src again
                    $image->setAttribute('src', $src);
                }
            }


        }

        // we need only the <body> content of the document!
        $newDom = new \DOMDocument;
        $body = $DOM->getElementsByTagName('body')->item(0);
        foreach ($body->childNodes as $child){
            $newDom->appendChild($newDom->importNode($child, true));
        }
        $content = $newDom->saveHTML();

        return $content;
    }

    /**
     * Cleanup the code as good as possible ...
     *
     * @param string $content
     * @return string
     */
    protected function cleanupContent($content)
    {
        if (self::$config['admin']['import']['data']['remove']['nbsp']) {
            // replace &nbsp; with a regular space
            $content = str_ireplace('&nbsp;', ' ', $content);
        }
        if (self::$config['admin']['import']['data']['remove']['double-space']) {
            // remove double spaces
            while (strpos($content, '  ')) {
                $content = str_replace('  ', ' ', $content);
            }
        }

        if (self::$config['admin']['import']['data']['htmlpurifier']['enabled']) {
            // cleanup the HTML with HTML Purifier
            require_once EXTENSION_PATH.'/htmlpurifier/latest/library/HTMLPurifier.auto.php';
            $config = \HTMLPurifier_Config::createDefault();
            $config->set('URI.Base', 'http://www.example.com');
            $config->set('URI.MakeAbsolute', true);
            $purifier = new \HTMLPurifier($config);
            $content = $purifier->purify($content);
        }

        $DOM = new \DOMDocument;
        // enable internal error handling
        libxml_use_internal_errors(true);

        $DOM->preserveWhiteSpace = false;

        // enshure the correct encoding of the content
        $encoding_str = '<?xml encoding="UTF-8">';
        if (!$DOM->loadHTML($encoding_str.$content)) {
            foreach (libxml_get_errors() as $error) {
                // handle errors here
                $this->app['monolog']->addError('[flexContent] '.$error->message, array(__METHOD__, __LINE__));
                libxml_clear_errors();
            }
            throw new \Exception('Problem processing the content - check the logfile for more information.');
        }
        libxml_clear_errors();

        $DOMIterator = new \RecursiveIteratorIterator(
            new RecursiveDOMIterator($DOM),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($DOMIterator as $node) {
            if ($node->nodeType === XML_ELEMENT_NODE) {
                if (self::$config['admin']['import']['data']['remove']['style'] &&
                    $node->hasAttribute('style')) {
                    // remove style information
                    $node->removeAttribute('style');
                }
                if (self::$config['admin']['import']['data']['remove']['class'] &&
                    $node->hasAttribute('class')) {
                    // remove class information
                    $node->removeAttribute('class');
                }
            }
        }

        // we need only the <body> content of the document!
        $newDom = new \DOMDocument;
        $body = $DOM->getElementsByTagName('body')->item(0);
        foreach ($body->childNodes as $child){
            $newDom->appendChild($newDom->importNode($child, true));
        }
        $content = $newDom->saveHTML();
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

        // replace placeholders with content
        $content['content'] = $this->prepareContent($content['content']);

        if (self::$data_handling == 'CLEAN_UP') {
            $content['content'] = $this->cleanupContent($content['content']);
        }
        elseif (self::$data_handling == 'STRIP_TAGS') {
            $content['content'] = strip_tags($content['content']);
        }

        $teaser = $this->app['utils']->Ellipsis($content['content'], 500, false, true);

        echo $content['content']."<br>";
        //print_r($content);
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
