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
use phpManufaktur\flexContent\Data\Content\TagType as TagTypeData;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use phpManufaktur\flexContent\Control\Configuration;

class ContentTag extends Admin
{
    protected static $tag_id = null;
    protected $TagTypeData = null;

    protected static $route = null;
    protected static $columns = null;
    protected static $rows_per_page = null;
    protected static $order_by = null;
    protected static $order_direction = null;
    protected static $current_page = null;
    protected static $max_pages = null;
    protected static $config = null;
    protected static $language = null;

    /**
     * (non-PHPdoc)
     * @see \phpManufaktur\flexContent\Control\Admin\Admin::initialize()
     */
    protected function initialize(Application $app)
    {
        parent::initialize($app);

        $this->TagTypeData = new TagTypeData($app);
        self::$tag_id = -1;
        try {
            // search for the config file in the template directory
            $cfg_file = $this->app['utils']->getTemplateFile('@phpManufaktur/flexContent/Template', 'admin/tag.type.list.json', '', true);
            $cfg = $this->app['utils']->readJSON($cfg_file);

            // get the columns to show in the list
            self::$columns = isset($cfg['columns']) ? $cfg['columns'] : $this->TagTypeData->getColumns();
            self::$rows_per_page = isset($cfg['list']['rows_per_page']) ? $cfg['list']['rows_per_page'] : 100;
            self::$order_by = isset($cfg['list']['order']['by']) ? $cfg['list']['order']['by'] : array('tag_name');
            self::$order_direction = isset($cfg['list']['order']['direction']) ? $cfg['list']['order']['direction'] : 'ASC';
        } catch (\Exception $e) {
            // the config file does not exists - use all available columns
            self::$columns = $this->TagTypeData->getColumns();
            self::$rows_per_page = 100;
            self::$order_by = array('tag_name');
            self::$order_direction = 'ASC';
        }
        self::$current_page = 1;
        self::$route =  array(
            'pagination' => '/flexcontent/editor/buzzword/list/page/{page}?order={order}&direction={direction}&usage='.self::$usage,
            'edit' => '/flexcontent/editor/buzzword/edit/id/{tag_id}?usage='.self::$usage,
            'create' => '/flexcontent/editor/buzzword/create?usage='.self::$usage,
            'edit_content' => '/flexcontent/editor/edit/id/{content_id}?usage='.self::$usage
        );

        $Configuration = new Configuration($app);
        self::$config = $Configuration->getConfiguration();

        self::$language = $this->app['request']->get('form[language]', self::$config['content']['language']['default'], true);
    }

    /**
     * Set the current page for the Tag Type list
     *
     * @param integer $page
     */
    public function setCurrentPage($page)
    {
        self::$current_page = $page;
    }

    /**
     * Get the Tag Type List for the given page and as defined in tag.type.list.json
     *
     * @param integer reference $list_page
     * @param integer $rows_per_page
     * @param integer reference $max_pages
     * @param array $order_by
     * @param string $order_direction
     * @return null|array list of the selected Tag Types
     */
    protected function getList(&$list_page, $rows_per_page, &$max_pages=null, $order_by=null, $order_direction='ASC')
    {
        // count rows
        $count_rows = $this->TagTypeData->count();

        if ($count_rows < 1) {
            // nothing to do ...
            return null;
        }

        $max_pages = ceil($count_rows/$rows_per_page);
        if ($list_page < 1) {
            $list_page = 1;
        }
        if ($list_page > $max_pages) {
            $list_page = $max_pages;
        }
        $limit_from = ($list_page * $rows_per_page) - $rows_per_page;

        return $this->TagTypeData->selectList($limit_from, $rows_per_page, $order_by, $order_direction, self::$columns);
    }


    /**
     * Get the TagType form
     *
     * @param array $data
     */
    protected function getTagTypeForm($data=array())
    {
        if (isset($data['language'])) {
            // set the language property from the tag type data
            self::$language = $data['language'];
        }

        // show the permalink URL
        $language = (isset($data['language'])) ? $data['language'] : self::$language;
        $permalink_url = CMS_URL.str_ireplace('{language}', strtolower($language), self::$config['content']['permalink']['directory']).'/buzzword/';

        $form = $this->app['form.factory']->createBuilder('form')
        ->add('tag_id', 'hidden', array(
            'data' => isset($data['tag_id']) ? $data['tag_id'] : -1
        ))
        ->add('tag_name', 'text', array(
            'data' => isset($data['tag_name']) ? $data['tag_name'] : ''
        ))
        ->add('language', 'hidden', array(
            'data' => $language
        ))
        ->add('tag_permalink', 'text', array(
            'data' => isset($data['tag_permalink']) ? $data['tag_permalink'] : '',
            'label' => 'Permalink'
        ))
        ->add('permalink_url', 'hidden', array(
            'data' => $permalink_url
        ))
        ->add('tag_image', 'hidden', array(
            'data' => isset($data['tag_image']) ? $data['tag_image'] : ''
        ))
        ->add('tag_description', 'textarea', array(
            'data' => isset($data['tag_description']) ? $data['tag_description'] : '',
            'required' => false
        ))
        ->add('delete', 'checkbox', array(
            'required' => false
        ))
        ;
        return $form->getForm();
    }

    /**
     * Render the form and return the complete dialog
     *
     * @param Form Factory $form
     */
    protected function renderTagTypeForm($form)
    {
        return $this->app['twig']->render($this->app['utils']->getTemplateFile(
            '@phpManufaktur/flexContent/Template', 'admin/tag.type.edit.twig'),
            array(
                'usage' => self::$usage,
                'toolbar' => $this->getToolbar('tags'),
                'alert' => $this->getAlert(),
                'form' => $form->createView(),
                'config' => self::$config
            ));
    }

    /**
     * Check the submitted form data and insert or update a record
     *
     * @param array reference $data
     * @return boolean
     */
    protected function checkTagTypeForm(&$data=array())
    {
        // get the form
        $form = $this->getTagTypeForm();
        // get the requested data
        $form->bind($this->app['request']);

        if ($form->isValid()) {
            // the form is valid
            $tag = $form->getData();
            $data = array();

            self::$tag_id = $tag['tag_id'];
            $data['tag_id'] = self::$tag_id;

            if (isset($tag['delete']) && ($tag['delete'] == 1)) {
                // delete this tag type
                $this->TagTypeData->delete(self::$tag_id);
                $this->setAlert('The tag type %tag% was successfull deleted.',
                    array('%tag%' => $tag['tag_name']), self::ALERT_TYPE_SUCCESS);
                return true;
            }

            if (empty($tag['tag_name'])) {
                $this->setAlert('Please type in a name for the tag type.', array(), self::ALERT_TYPE_WARNING);
                return false;
            }

            // check for forbidden chars in the tag name
            foreach (TagTypeData::$forbidden_chars as $forbidden) {
                if (false !== strpos($tag['tag_name'], $forbidden)) {
                    $this->setAlert('The tag type name %tag% contains the forbidden character %char%, please change the name.',
                        array('%char%' => $forbidden, '%tag%' => $tag['tag_name']), self::ALERT_TYPE_WARNING);
                    return false;
                }
            }

            // check if the tag already exists
            if ((self::$tag_id < 1) && $this->TagTypeData->existsName($tag['tag_name'], $tag['language'])) {
                $this->setAlert('The tag type %tag% already exists and can not inserted!',
                    array('%tag%' => $tag['tag_name']), self::ALERT_TYPE_WARNING);
                return false;
            }

            // check the permalink
            if (empty($tag['tag_permalink'])) {
                $tag['tag_permalink'] = $tag['tag_name'];
            }

            $permalink = $this->app['utils']->sanitizeLink($tag['tag_permalink']);

            if (self::$tag_id < 1 && $this->TagTypeData->existsPermaLink($permalink, $tag['language'])) {
                // this PermaLink already exists!
                $this->setAlert('The permalink %permalink% is already in use, please select another one!',
                    array('%permalink%' => $permalink), self::ALERT_TYPE_WARNING);
                return false;
            }
            elseif ((self::$tag_id > 0) &&
                (false !== ($used_by = $this->TagTypeData->selectTagIDbyPermaLink($permalink, $tag['language']))) &&
                ($used_by != self::$tag_id)) {
                $this->setAlert('The permalink %permalink% is already in use by the tag type record %id%, please select another one!',
                    array('%permalink%' => $permalink, '%id%' => $used_by), self::ALERT_TYPE_WARNING);
                return false;
            }

            $data['tag_name'] = $tag['tag_name'];
            $data['tag_description'] = !empty($tag['tag_description']) ? $tag['tag_description'] : '';
            $data['language'] = $tag['language'];
            $data['tag_permalink'] = $tag['tag_permalink'];

            if (self::$tag_id < 1) {
                // create a new tag type record
                $this->TagTypeData->insert($data, self::$tag_id);
                // important: set the tag_id also in the $data array!
                $data['tag_id'] = self::$tag_id;
                $this->setAlert('Successfull create the new tag type %tag%.',
                    array('%tag%' => $data['tag_name']), self::ALERT_TYPE_SUCCESS);
            }
            else {
                // update an existing record
                $this->TagTypeData->update(self::$tag_id, $data);
                $this->setAlert('Updated the tag type %tag%',
                    array('%tag%' => $data['tag_name']), self::ALERT_TYPE_SUCCESS);
            }
            return true;
        }
        else {
            // general error (timeout, CSFR ...)
            $this->setAlert('The form is not valid, please check your input and try again!', array(),
                self::ALERT_TYPE_DANGER, true, array('form_errors' => $form->getErrorsAsString(),
                    'method' => __METHOD__, 'line' => __LINE__));
        }
        return false;
    }

    /**
     * Create the form to select the language desired to the flexContent
     *
     * @param array $data
     */
    protected function getLanguageForm($data=array())
    {
        $languages = array();
        foreach (self::$config['content']['language']['support'] as $language) {
            $languages[$language['code']] = $language['name'];
        }

        return $this->app['form.factory']->createBuilder('form')
        ->add('language', 'choice', array(
            'choices' => $languages,
            'empty_value' => '- please select -',
            'expanded' => false,
            'required' => self::$config['content']['field']['language']['required'],
            'data' => isset($data['language']) ? $data['language'] : self::$language,
        ))
        ->getForm();
    }

    /**
     * Select a language for the flexContent
     *
     * @return string dialog
     */
    protected function selectLanguage()
    {
        $form = $this->getLanguageForm();

        $this->setAlert('Please select the language for the new flexContent.');

        return $this->app['twig']->render($this->app['utils']->getTemplateFile(
            '@phpManufaktur/flexContent/Template', 'admin/select.language.twig'),
            array(
                'usage' => self::$usage,
                'toolbar' => $this->getToolbar('edit'),
                'alert' => $this->getAlert(),
                'form' => $form->createView(),
                'config' => self::$config,
                'action' => '/flexcontent/editor/buzzword/language/check'
            ));
    }

    /**
     * Controller to check the selected language and show the flexContent dialog
     *
     * @param Application $app
     */
    public function ControllerLanguageCheck(Application $app)
    {
        $this->initialize($app);

        // get the form
        $form = $this->getLanguageForm();
        // get the requested data
        $form->bind($this->app['request']);

        if ($form->isValid()) {
            // the form is valid
            $data = $form->getData();
            self::$language = $data['language'];
        }
        else {
            // general error (timeout, CSFR ...)
            $this->setAlert('The form is not valid, please check your input and try again!', array(),
                self::ALERT_TYPE_DANGER, true, array('form_errors' => $form->getErrorsAsString(),
                    'method' => __METHOD__, 'line' => __LINE__));
        }

        $form = $this->getTagTypeForm($data);
        return $this->renderTagTypeForm($form);
    }

    /**
     * Controller to create or edit a Tag Type record
     *
     * @param Application $app
     * @param integer $tag_id
     */
    public function ControllerEdit(Application $app, $tag_id=null)
    {
        $this->initialize($app);

        if (!is_null($tag_id)) {
            self::$tag_id = $tag_id;
        }

        if ((self::$tag_id < 1) && self::$config['content']['language']['select']) {
            return $this->selectLanguage();
        }

        $data = array();
        if ((self::$tag_id > 0) && (false === ($data = $this->TagTypeData->select(self::$tag_id)))) {
            $this->setAlert('The Tag Type record with the ID %id% does not exists!',
                array('%id%' => self::$tag_id), self::ALERT_TYPE_WARNING);
        }

        $form = $this->getTagTypeForm($data);
        return $this->renderTagTypeForm($form);
    }

    /**
     * Controller check the submitted form
     *
     * @param Application $app
     */
    public function ControllerEditCheck(Application $app)
    {
        $this->initialize($app);

        // check the form data and set self::$contact_id
        $data = array();
        if (!$this->checkTagTypeForm($data)) {
            // the check fails - show the form again
            $form = $this->getTagTypeForm($data);
            return $this->renderTagTypeForm($form);
        }

        // all fine - return to the tag type list
        return $this->ControllerList($app);
    }

    /**
     * Controller to select a image for the tag type
     *
     * @param Application $app
     */
    public function ControllerImage(Application $app)
    {
        $this->initialize($app);

        // check the form data and set self::$contact_id
        $data = array();
        if (!$this->checkTagTypeForm($data)) {
            // the check fails - show the form again
            $form = $this->getTagTypeForm($data);
            return $this->renderTagTypeForm($form);
        }

        // grant that the directory exists
        $app['filesystem']->mkdir(FRAMEWORK_PATH.self::$config['content']['images']['directory']['select']);

        // exec the MediaBrowser
        $subRequest = Request::create('/mediabrowser', 'GET', array(
            'usage' => self::$usage,
            'start' => self::$config['content']['images']['directory']['start'],
            'redirect' => '/flexcontent/editor/buzzword/image/check/id/'.self::$tag_id,
            'mode' => 'public',
            'directory' => self::$config['content']['images']['directory']['select']
        ));
        return $app->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    }

    /**
     * Controller check the submitted image
     *
     * @param Application $app
     * @param integer $tag_id
     * @return string
     */
    public function ControllerImageCheck(Application $app, $tag_id)
    {
        $this->initialize($app);

        self::$tag_id = $tag_id;

        // get the selected image
        if (null == ($image = $app['request']->get('file'))) {
            $this->setAlert('There was no image selected.', array(), self::ALERT_TYPE_INFO);
        }
        else {
            // udate the flexContent record
            $data = array(
                'tag_image' => $image
            );
            $this->TagTypeData->update(self::$tag_id, $data);
            $this->setAlert('The image %image% was successfull inserted.',
                array('%image%' => basename($image)), self::ALERT_TYPE_SUCCESS);
        }

        if (false === ($data = $this->TagTypeData->select(self::$tag_id))) {
            $this->setAlert('The Tag Type record with the ID %id% does not exists!',
                array('%id%' => self::$tag_id), self::ALERT_TYPE_WARNING);
        }
        $form = $this->getTagTypeForm($data);
        return $this->renderTagTypeForm($form);
    }

    /**
     * Controller to remove a image from the Hashtag description
     *
     * @param Application $app
     * @param integer $tag_id
     */
    public function ControllerImageRemove(Application $app, $tag_id)
    {
        $this->initialize($app);

        self::$tag_id = $tag_id;

        // udate the flexContent record
        $data = array(
            'tag_image' => '' // empty field == no image
        );
        $this->TagTypeData->update(self::$tag_id, $data);
        $this->setAlert('The image was successfull removed.',
            array(), self::ALERT_TYPE_SUCCESS);

        if (false === ($data = $this->TagTypeData->select(self::$tag_id))) {
            $this->setAlert('The Tag Type record with the ID %id% does not exists!',
                array('%id%' => self::$tag_id), self::ALERT_TYPE_WARNING);
        }
        $form = $this->getTagTypeForm($data);
        return $this->renderTagTypeForm($form);
    }

    /**
     * Controller to show a list with all TAG Types
     *
     * @param Application $app
     * @param string $page
     */
    public function ControllerList(Application $app, $page=null)
    {
        $this->initialize($app);

        if (!is_null($page)) {
            $this->setCurrentPage($page);
        }

        $order_by = explode(',', $app['request']->get('order', implode(',', self::$order_by)));
        $order_direction = $app['request']->get('direction', self::$order_direction);

        $tags = $this->getList(self::$current_page, self::$rows_per_page, self::$max_pages, $order_by, $order_direction);

        return $this->app['twig']->render($this->app['utils']->getTemplateFile(
            '@phpManufaktur/flexContent/Template', 'admin/tag.type.list.twig'),
            array(
                'usage' => self::$usage,
                'toolbar' => $this->getToolbar('tags'),
                'alert' => $this->getAlert(),
                'tags' => $tags,
                'columns' => self::$columns,
                'current_page' => self::$current_page,
                'route' => self::$route,
                'order_by' => $order_by,
                'order_direction' => strtolower($order_direction),
                'last_page' => self::$max_pages
            ));
    }


}

