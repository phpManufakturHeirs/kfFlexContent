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
use phpManufaktur\Basic\Data\CMS\Page;
use phpManufaktur\flexContent\Data\Content\CategoryType as CategoryTypeData;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use phpManufaktur\flexContent\Data\Import\WYSIWYG;
use phpManufaktur\flexContent\Data\Content\Category as CategoryData;
use phpManufaktur\flexContent\Data\Content\Content as ContentData;

class ContentCategory extends Admin
{

    protected static $category_id = null;
    protected $CategoryTypeData = null;
    protected $CategoryData = null;
    protected $ContentData = null;
    protected $CMSPage = null;
    protected $WYSIWYG = null;

    protected static $route = null;
    protected static $columns = null;
    protected static $rows_per_page = null;
    protected static $order_by = null;
    protected static $order_direction = null;
    protected static $current_page = null;
    protected static $max_pages = null;
    protected static $language = null;

    /**
     * (non-PHPdoc)
     * @see \phpManufaktur\flexContent\Control\Admin\Admin::initialize()
     */
    protected function initialize(Application $app)
    {
        parent::initialize($app);

        $this->CategoryTypeData = new CategoryTypeData($app);
        $this->CategoryData = new CategoryData($app);
        $this->ContentData = new ContentData($app);
        $this->CMSPage = new Page($app);
        $this->WYSIWYG = new WYSIWYG($app);

        self::$category_id = -1;

        try {
            // search for the config file in the template directory
            $cfg_file = $this->app['utils']->getTemplateFile('@phpManufaktur/flexContent/Template', 'admin/category.type.list.json', '', true);
            $cfg = $this->app['utils']->readJSON($cfg_file);

            // get the columns to show in the list
            self::$columns = isset($cfg['columns']) ? $cfg['columns'] : $this->CategoryTypeData->getColumns();
            self::$rows_per_page = isset($cfg['list']['rows_per_page']) ? $cfg['list']['rows_per_page'] : 100;
            self::$order_by = isset($cfg['list']['order']['by']) ? $cfg['list']['order']['by'] : array('category_name');
            self::$order_direction = isset($cfg['list']['order']['direction']) ? $cfg['list']['order']['direction'] : 'ASC';
        } catch (\Exception $e) {
            // the config file does not exists - use all available columns
            self::$columns = $this->CategoryTypeData->getColumns();
            self::$rows_per_page = 100;
            self::$order_by = array('category_name');
            self::$order_direction = 'ASC';
        }
        self::$current_page = 1;
        self::$route =  array(
            'pagination' => '/flexcontent/editor/category/list/page/{page}?order={order}&direction={direction}&usage='.self::$usage,
            'edit' => '/flexcontent/editor/category/edit/id/{category_id}?usage='.self::$usage,
            'create' => '/flexcontent/editor/category/create?usage='.self::$usage,
            'edit_content' => '/flexcontent/editor/edit/id/{content_id}?usage='.self::$usage
        );

        self::$language = $this->app['request']->get('form[language]', self::$config['content']['language']['default'], true);
    }

    /**
     * Set the current page for the ContentList
     *
     * @param integer $page
     */
    public function setCurrentPage($page)
    {
        self::$current_page = $page;
    }

    /**
     * Get the Category Type List for the given page and as defined in category.type.list.json
     *
     * @param integer reference $list_page
     * @param integer $rows_per_page
     * @param integer reference $max_pages
     * @param array $order_by
     * @param string $order_direction
     * @return null|array list of the selected Category Types
     */
    protected function getList(&$list_page, $rows_per_page, &$max_pages=null, $order_by=null, $order_direction='ASC')
    {
        // count rows
        $count_rows = $this->CategoryTypeData->count();

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

        return $this->CategoryTypeData->selectList($limit_from, $rows_per_page, $order_by, $order_direction, self::$columns);
    }

    /**
     * Get the Category Type form
     *
     * @param array $data
     */
    protected function getCategoryTypeForm($data=array())
    {
        if (isset($data['language'])) {
            // set the language property from the category type data
            self::$language = $data['language'];
        }

        $pagelist = $this->CMSPage->getPageLinkList();
        $links = array();
        foreach ($pagelist as $link) {
            $links[$link['complete_link']] = $link['complete_link'];
        }

        // show the permalink URL
        $language = (isset($data['language'])) ? $data['language'] : self::$language;
        $permalink_url = CMS_URL.str_ireplace('{language}', strtolower($language), self::$config['content']['permalink']['directory']).'/category/';

        $check_kitcommand = false;
        if (isset($data['category_id']) && ($data['category_id'] > 0) &&
            isset($data['target_url']) && !empty($data['target_url'])) {
            // check if the flexContent kitCommand exists at the target
            $link = '/'.basename($data['target_url'], $this->CMSPage->getPageExtension());
            if (false !== ($page_id = $this->CMSPage->getPageIDbyPageLink($link))) {
                $check_kitcommand = (int) $this->WYSIWYG->checkPageIDforFlexContentCommand($page_id);
            }
        }

        $form = $this->app['form.factory']->createBuilder('form')
        ->add('check_kitcommand', 'hidden', array(
            'data' => $check_kitcommand
        ))
        ->add('category_id', 'hidden', array(
            'data' => isset($data['category_id']) ? $data['category_id'] : -1
        ))
        ->add('language', 'hidden', array(
            'data' => $language
        ))
        ->add('category_type', 'choice', array(
            'choices' => $this->CategoryTypeData->getTypesForSelect(),
            'empty_value' => '- please select -',
            'expanded' => false,
            'required' => true,
            'label' => 'Type',
            'data' => isset($data['category_type']) ? $data['category_type'] : 'DEFAULT'
        ))
        ->add('category_name', 'text', array(
            'label' => 'Name',
            'data' => isset($data['category_name']) ? $data['category_name'] : ''
        ))
        ->add('category_permalink', 'text', array(
            'label' => 'Permalink',
            'data' => isset($data['category_permalink']) ? $data['category_permalink'] : ''
        ))
        ->add('permalink_url', 'hidden', array(
            'data' => $permalink_url
        ))
        ->add('target_url', 'choice', array(
            'choices' => $links,
            'empty_value' => '- please select -',
            'expanded' => false,
            'required' => true,
            'label' => 'Target URL',
            'data' => isset($data['target_url']) ? $data['target_url'] : null
        ))
        ->add('category_image', 'hidden', array(
            'data' => isset($data['category_image']) ? $data['category_image'] : ''
        ))
        ->add('category_description', 'textarea', array(
            'label' => 'Description',
            'data' => isset($data['category_description']) ? $data['category_description'] : '',
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
    protected function renderCategoryTypeForm($form)
    {

        return $this->app['twig']->render($this->app['utils']->getTemplateFile(
            '@phpManufaktur/flexContent/Template', 'admin/category.type.edit.twig'),
            array(
                'usage' => self::$usage,
                'toolbar' => $this->getToolbar('categories'),
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
    protected function checkCategoryTypeForm(&$data=array())
    {
        // get the form
        $form = $this->getCategoryTypeForm();
        // get the requested data
        $form->bind($this->app['request']);

        if ($form->isValid()) {
            // the form is valid
            $category = $form->getData();
            $data = array();

            self::$category_id = $category['category_id'];
            $data['category_id'] = self::$category_id;

            if (isset($category['delete']) && ($category['delete'] == 1)) {
                // delete this tag type
                $content_ids = $this->CategoryData->selectByCategoryID(self::$category_id, true);
                foreach ($content_ids as $content_id) {
                    $this->ContentData->delete($content_id);
                }
                $this->CategoryTypeData->delete(self::$category_id);
                $this->setAlert('The category type %category% was successfull deleted.',
                    array('%category%' => $category['category_name']), self::ALERT_TYPE_SUCCESS);
                $this->app['session']->remove(self::SESSION_CATEGORY_ID);
                return true;
            }

            if (($category['category_type'] === 'EVENT') && !$this->app->offsetExists('contact')) {
                $this->setAlert('To use a category of type <var>EVENT</var> you need to install the kitFramework extension <strong>Contact</strong> first.',
                    array(), self::ALERT_TYPE_INFO);
                return false;
            }

            if (empty($category['category_name'])) {
                $this->setAlert('Please type in a name for the category type.', array(), self::ALERT_TYPE_WARNING);
                return false;
            }

            // check for forbidden chars in the category name
            foreach (CategoryTypeData::$forbidden_chars as $forbidden) {
                if (false !== strpos($category['category_name'], $forbidden)) {
                    $this->setAlert('The category type name %category% contains the forbidden character %char%, please change the name.',
                        array('%char%' => $forbidden, '%category%' => $category['category_name']), self::ALERT_TYPE_WARNING);
                    return false;
                }
            }

            // check if the category already exists
            if ((self::$category_id < 1) && $this->CategoryTypeData->existsName($category['category_name'], $category['language'])) {
                $this->setAlert('The category type %category% already exists and can not inserted!',
                    array('%category%' => $category['category_name']), self::ALERT_TYPE_WARNING);
                return false;
            }

            // check permalink
            if (empty($category['category_permalink'])) {
                $category['category_permalink'] = $category['category_name'];
            }
            $permalink = $this->app['utils']->sanitizeLink($category['category_permalink']);

            if (self::$category_id < 1 && $this->CategoryTypeData->existsPermaLink($permalink, $category['language'])) {
                // this PermaLink already exists!
                $this->setAlert('The permalink %permalink% is already in use, please select another one!',
                    array('%permalink%' => $permalink), self::ALERT_TYPE_WARNING);
                return false;
            }
            elseif ((self::$category_id > 0) &&
                (false !== ($used_by = $this->CategoryTypeData->selectCategoryIDbyPermaLink($permalink, $category['language']))) &&
                ($used_by != self::$category_id)) {
                $this->setAlert('The permalink %permalink% is already in use by the category type record %id%, please select another one!',
                    array('%permalink%' => $permalink, '%id%' => $used_by), self::ALERT_TYPE_WARNING);
                return false;
            }

            $data['category_name'] = $category['category_name'];
            $data['language'] = $category['language'];
            $data['category_permalink'] = $permalink;
            $data['category_description'] = !empty($category['category_description']) ? $category['category_description'] : '';
            $data['target_url'] = $category['target_url'];
            $data['category_type'] = $category['category_type'];

            if (self::$category_id < 1) {
                // create a new category type record
                $this->CategoryTypeData->insert($data, self::$category_id);
                // important: set the category_id also in the $data array!
                $data['category_id'] = self::$category_id;
                $this->setAlert('Successfull create the new category type %category%.',
                    array('%category%' => $data['category_name']), self::ALERT_TYPE_SUCCESS);
            }
            else {
                // update an existing record
                $this->CategoryTypeData->update(self::$category_id, $data);
                $this->setAlert('Updated the category type %category%',
                    array('%category%' => $data['category_name']), self::ALERT_TYPE_SUCCESS);
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
     * Controller to show a list with all CATEGORY Types
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

        $categories = $this->getList(self::$current_page, self::$rows_per_page, self::$max_pages, $order_by, $order_direction);

        return $this->app['twig']->render($this->app['utils']->getTemplateFile(
            '@phpManufaktur/flexContent/Template', 'admin/category.type.list.twig'),
            array(
                'usage' => self::$usage,
                'toolbar' => $this->getToolbar('categories'),
                'alert' => $this->getAlert(),
                'categories' => $categories,
                'columns' => self::$columns,
                'current_page' => self::$current_page,
                'route' => self::$route,
                'order_by' => $order_by,
                'order_direction' => strtolower($order_direction),
                'last_page' => self::$max_pages
            ));
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
                'action' => '/flexcontent/editor/category/language/check'
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

        $form = $this->getCategoryTypeForm($data);
        return $this->renderCategoryTypeForm($form);
    }


    /**
     * Controller to create or edit a Category Type record
     *
     * @param Application $app
     * @param integer $category_id
     */
    public function ControllerEdit(Application $app, $category_id=null)
    {
        $this->initialize($app);

        if (!is_null($category_id)) {
            self::$category_id = $category_id;
        }

        if ((self::$category_id < 1) && self::$config['content']['language']['select']) {
            // language selection is active - select language first!
            return $this->selectLanguage();
        }

        $data = array();
        if ((self::$category_id > 0) && (false === ($data = $this->CategoryTypeData->select(self::$category_id)))) {
            $this->setAlert('The Category Type record with the ID %id% does not exists!',
                array('%id%' => self::$category_id), self::ALERT_TYPE_WARNING);
        }

        $form = $this->getCategoryTypeForm($data);
        return $this->renderCategoryTypeForm($form);
    }

    /**
     * Controller check the submitted form
     *
     * @param Application $app
     */
    public function ControllerEditCheck(Application $app)
    {
        $this->initialize($app);

        // check the form data and set self::$category_id
        $data = array();
        if (!$this->checkCategoryTypeForm($data)) {
            // the check fails - show the form again
            $form = $this->getCategoryTypeForm($data);
            return $this->renderCategoryTypeForm($form);
        }

        // all fine - return to the tag type list
        return $this->ControllerList($app);
    }

    /**
     * Controller to select a image for the category type
     *
     * @param Application $app
     */
    public function ControllerImage(Application $app)
    {
        $this->initialize($app);

        // check the form data and set self::$contact_id
        $data = array();
        if (!$this->checkCategoryTypeForm($data)) {
            // the check fails - show the form again
            $form = $this->getCategoryTypeForm($data);
            return $this->renderCategoryTypeForm($form);
        }

        // grant that the directory exists
        $app['filesystem']->mkdir(FRAMEWORK_PATH.self::$config['content']['images']['directory']['select']);

        // exec the MediaBrowser
        $subRequest = Request::create('/mediabrowser', 'GET', array(
            'usage' => self::$usage,
            'start' => self::$config['content']['images']['directory']['start'],
            'redirect' => '/flexcontent/editor/category/image/check/id/'.self::$category_id,
            'mode' => 'public',
            'directory' => self::$config['content']['images']['directory']['select']
        ));
        return $app->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    }

    /**
     * Controller check the submitted image
     *
     * @param Application $app
     * @param integer $category_id
     * @return string
     */
    public function ControllerImageCheck(Application $app, $category_id)
    {
        $this->initialize($app);

        self::$category_id = $category_id;

        // get the selected image
        if (null == ($image = $app['request']->get('file'))) {
            $this->setAlert('There was no image selected.', array(), self::ALERT_TYPE_INFO);
        }
        else {
            // udate the Category record
            $data = array(
                'category_image' => $image
            );
            $this->CategoryTypeData->update(self::$category_id, $data);
            $this->setAlert('The image %image% was successfull inserted.',
                array('%image%' => basename($image)), self::ALERT_TYPE_SUCCESS);
        }

        if (false === ($data = $this->CategoryTypeData->select(self::$category_id))) {
            $this->setAlert('The Category Type record with the ID %id% does not exists!',
                array('%id%' => self::$category_id), self::ALERT_TYPE_WARNING);
        }
        $form = $this->getCategoryTypeForm($data);
        return $this->renderCategoryTypeForm($form);
    }

    /**
     * Controller to remove the category image
     *
     * @param Application $app
     * @param integer $category_id
     */
    public function ControllerImageRemove(Application $app, $category_id)
    {
        $this->initialize($app);

        self::$category_id = $category_id;

        // udate the Category record
        $data = array(
            'category_image' => '' // empty field == no image
        );
        $this->CategoryTypeData->update(self::$category_id, $data);
        $this->setAlert('The image was successfull removed.',
            array(), self::ALERT_TYPE_SUCCESS);

        if (false === ($data = $this->CategoryTypeData->select(self::$category_id))) {
            $this->setAlert('The Category Type record with the ID %id% does not exists!',
                array('%id%' => self::$category_id), self::ALERT_TYPE_WARNING);
        }
        $form = $this->getCategoryTypeForm($data);
        return $this->renderCategoryTypeForm($form);
    }

}
