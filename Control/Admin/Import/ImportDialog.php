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
use phpManufaktur\flexContent\Data\Content\Content;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

require_once EXTENSION_PATH.'/htmlpurifier/latest/library/HTMLPurifier.auto.php';

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
                    'action' => '/flexcontent/editor/import/execute'.self::$usage_param,
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
        if (empty($content)) {
            return $content;
        }

        $cmsPages = new Page($this->app);
        $page_directory = $cmsPages->getPageDirectory();
        $page_extension = $cmsPages->getPageExtension();

        $DOM = new \DOMDocument;
        // enable internal error handling
        libxml_use_internal_errors(true);

        $DOM->preserveWhiteSpace = false;

        // need a hack to properly handle UTF-8 encoding
        if (!$DOM->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', "UTF-8"))) {
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
                if (strpos($src, CMS_MEDIA_URL) === 0) {
                    $path = substr($src, strlen(CMS_MEDIA_URL));
                    // copy the image to the framework /media directory
                    $this->app['filesystem']->copy(CMS_MEDIA_PATH.$path, FRAMEWORK_MEDIA_PATH.$path);
                    $src = FRAMEWORK_MEDIA_URL.$path;
                    // set the src again
                    $image->setAttribute('src', $src);
                }
            }
        }

        $XPath = new \DOMXPath($DOM);
        // get only the body tag with its contents, then trim the body tag itself to get only the original content
        return mb_substr($DOM->saveXML($XPath->query('//body')->item(0)), 6, -7, "UTF-8");
    }

    /**
     * Cleanup the code as good as possible ...
     *
     * @param string $content
     * @return string
     */
    protected function cleanupContent($content)
    {
        if (empty($content)) {
            return $content;
        }

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

        // need a hack to properly handle UTF-8 encoding
        if (!$DOM->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', "UTF-8"))) {
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

        $XPath = new \DOMXPath($DOM);
        // get only the body tag with its contents, then trim the body tag itself to get only the original content
        return mb_substr($DOM->saveXML($XPath->query('//body')->item(0)), 6, -7, "UTF-8");
    }

    /**
     * Try to get a teaser image from the given content
     *
     * @param string $content
     * @return mixed <boolean|string>
     */
    protected function getTeaserImage($content)
    {
        if (!self::$config['admin']['import']['data']['images']['teaser']['get_from_content']) {
            return false;
        }

        $min_width = self::$config['admin']['import']['data']['images']['teaser']['min_width'];
        $min_height = self::$config['admin']['import']['data']['images']['teaser']['min_height'];

        $DOM = new \DOMDocument();

        // enable internal error handling
        libxml_use_internal_errors(true);

        if (!$DOM->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', "UTF-8"))) {
            foreach (libxml_get_errors() as $error) {
                // handle errors here
                $this->app['monolog']->addError('[flexContent] '.$error->message, array(__METHOD__, __LINE__));
                libxml_clear_errors();
                return self::$content;
            }
        }
        libxml_clear_errors();

        foreach ($DOM->getElementsByTagName('img') as $image) {

            $width = null;
            $height = null;

            $style_str = $image->getAttribute('style');
            if (!empty($style_str)) {
                // it is possible that the width and height are set as CSS style information
                $style_array = (strpos($style_str, ';')) ? explode(';', $style_str) : array(trim($style_str));
                foreach ($style_array as $item) {
                    if (strpos($item, ':')) {
                        list($key, $value) = explode(':', $item);
                        if ((strtolower(trim($key)) == 'width') || (strtolower(trim($key)) == 'height')) {
                            if (strtolower(trim($key)) == 'width') {
                                $width = trim($value);
                            }
                            else {
                                $height = trim($value);
                            }
                        }
                    }
                }

                if (!is_null($width)) {
                    // set the width attribute
                    $image->setAttribute('width', trim(str_ireplace('px', '', $width)));
                }
                if (!is_null($height)) {
                    // set the height attribute
                    $image->setAttribute('height', trim(str_ireplace('px', '', $height)));
                }
            }

            $width = $image->getAttribute('width');
            $height = $image->getAttribute('height');

            if ((false !== (strpos($width, '%'))) || (false !== (strpos($height, '%')))) {
                // do not handle percentage image sizes
                continue;
            }

            if (($width >= $min_width) && ($height >= $min_height)) {
                $src = $image->getAttribute('src');
                if (strpos($src, CMS_URL) === 0) {
                    // take this image!
                    return (strpos($src, FRAMEWORK_URL) === 0) ? substr($src, strlen(FRAMEWORK_URL)) : substr($src, strlen(CMS_URL));
                }
            }
        }

        // no hit
        return false;
    }

    /**
     * Create a sample .htaccess files with Redirects for all imported pages and articles
     *
     * @return boolean
     */
    protected function createAccessFile()
    {
        if (!self::$config['admin']['import']['data']['htaccess']['create']) {
            return false;
        }

        $ImportControlData = new ImportControlData($this->app);
        if (false === ($redirects = $ImportControlData->selectRedirects())) {
            return false;
        }

        $lines = array();
        $lines[] = "# flexContent";
        $lines[] = '# Copy the following redirects into the .htaccess of your website';
        $lines[] = '';

        foreach ($redirects as $redirect) {
            $permalink = CMS_URL.str_ireplace('{language}', strtolower($redirect['language']), self::$config['content']['permalink']['directory']);
            $permalink .= '/'.$redirect['permalink'];
            $lines[] = 'Redirect 301 '.$redirect['identifier_link'].' '.$permalink;
        }

        if (!file_put_contents(CMS_PATH.'/'.self::$config['admin']['import']['data']['htaccess']['file'],
            implode("\n", $lines) . "\n")) {
            $error = error_get_last();
            if (is_array($error)) {
                $this->app['monolog']->addError("[flexContent] ".$error['message'], array($error['file'], $error['line']));
            }
            else {
                $this->app['monolog']->addError('[flexContent] Can not put contents to file '.
                    self::$config['admin']['import']['data']['htaccess']['file'], array(__METHOD__, __LINE__));
            }
            return false;
        }
        $this->app['monolog']->addDebug('Created the '.self::$config['admin']['import']['data']['htaccess']['file']. ' file');
        return true;
    }


    /**
     * Execute the import
     *
     * @return string
     */
    protected function executeImport()
    {
        $ContentData = new Content($this->app);

        $ImportControlData = new ImportControlData($this->app);
        $control = $ImportControlData->select(self::$import_id);

        // check if the content was already imported
        if ($control['flexcontent_id'] > 0) {
            // mark this flexContent record as deleted
            $data = array(
                'status' => 'DELETED'
            );
            $ContentData->update($data, $control['flexcontent_id']);
            $this->app['monolog']->addDebug('Mark flexContent record with ID '.$control['flexcontent_id'].
                ' as DELETED before again importing data for the identifier ID '.$control['identifier_id']);
        }

        if (false === ($content = $ImportControlData->selectContentData(self::$import_id))) {
            $this->setAlert("Can't read the content data from the given import ID %import_id%.",
                array('%import_id%' => self::$import_id), self::ALERT_TYPE_WARNING);
            return $this->renderImportDialog();
        }

        // replace placeholders with content
        $content['content'] = $this->prepareContent($content['content']);
        $content['teaser'] = $this->prepareContent($content['teaser']);

        if (self::$data_handling == 'CLEAN_UP') {
            $content['content'] = $this->cleanupContent($content['content']);
            $content['teaser'] = $this->cleanupContent($content['teaser']);
        }
        elseif (self::$data_handling == 'STRIP_TAGS') {
            $content['content'] = strip_tags($content['content']);
            $content['teaser'] = strip_tags($content['teaser']);
        }

        // create the teaser
        if (empty($content['teaser']) && self::$config['admin']['import']['data']['teaser']['create']) {
            $content['teaser'] = $this->app['utils']->Ellipsis($content['content'],
                self::$config['admin']['import']['data']['teaser']['ellipsis'],
                !self::$config['admin']['import']['data']['teaser']['html'],
                self::$config['admin']['import']['data']['teaser']['html']);
        }

        if (empty($content['teaser_image'])) {
            $content['teaser_image'] = (false !== ($img = $this->getTeaserImage($content['content']))) ? $img : '';
        }
        else {
            $image_path  = substr($content['teaser_image'], strlen(CMS_URL));
            if ($this->app['filesystem']->exists(CMS_PATH.$image_path)) {
                $this->app['filesystem']->copy(CMS_PATH.$image_path, FRAMEWORK_PATH.$image_path);
                $content['teaser_image'] = $image_path;
            }
            else {
                $content['teaser_image'] = '';
            }
        }

        // create the description
        if (empty($content['description']) && self::$config['admin']['import']['data']['description']['create']) {
            if ((self::$config['admin']['import']['data']['description']['source'] == 'teaser') && !empty($content['teaser'])) {
                // get the description from the teaser
                $content['description'] = $this->app['utils']->Ellipsis($content['teaser'],
                    self::$config['admin']['import']['data']['description']['ellipsis'], false, true);
            }
            else {
                // get the description from the content
                $content['description'] = $this->app['utils']->Ellipsis($content['content'],
                    self::$config['admin']['import']['data']['description']['ellipsis'], true, false);
            }
        }

        // create the flexContent record
        $content_id = $ContentData->insert($content);

        // update import control
        $data = array(
            'flexcontent_id' => $content_id,
            'import_status' => 'IMPORTED'
        );
        $ImportControlData->update(self::$import_id, $data);

        // create a access file
        $this->createAccessFile();

        // show the new flexContent record
        $subRequest = Request::create('/flexcontent/editor/edit/id/'.$content_id,
            'GET', array('usage' => self::$usage));
        return $this->app->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
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