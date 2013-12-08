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
use phpManufaktur\Basic\Data\CMS\Page;
use phpManufaktur\flexContent\Data\Content\CategoryType as CategoryTypeData;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ContentCategory extends Backend
{

    protected static $category_id = null;
    protected $CategoryTypeData = null;
    protected $CMSPage = null;

    protected static $route = null;
    protected static $columns = null;
    protected static $rows_per_page = null;
    protected static $order_by = null;
    protected static $order_direction = null;
    protected static $current_page = null;
    protected static $max_pages = null;

    /**
     * (non-PHPdoc)
     * @see \phpManufaktur\flexContent\Control\Backend\Backend::initialize()
     */
    protected function initialize(Application $app)
    {
        parent::initialize($app);

        $this->CategoryTypeData = new CategoryTypeData($app);
        $this->CMSPage = new Page($app);
        self::$category_id = -1;

        try {
            // search for the config file in the template directory
            $cfg_file = $this->app['utils']->getTemplateFile('@phpManufaktur/flexContent/Template', 'backend/category.type.list.json', '', true);
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
            'pagination' => '/admin/flexcontent/category/list/page/{page}?order={order}&direction={direction}&usage='.self::$usage,
            'edit' => '/admin/flexcontent/category/edit/id/{category_id}?usage='.self::$usage,
            'create' => '/admin/flexcontent/category/create?usage='.self::$usage,
            'edit_content' => '/admin/flexcontent/edit/id/{content_id}?usage='.self::$usage
        );
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
    protected function getCategoryTypeForm($data = array())
    {
        $pagelist = $this->CMSPage->getPageLinkList();
        $links = array();
        foreach ($pagelist as $link) {
            $links[$link['complete_link']] = $link['complete_link'];
        }

        $form = $this->app['form.factory']->createBuilder('form')
        ->add('category_id', 'hidden', array(
            'data' => isset($data['category_id']) ? $data['category_id'] : -1
        ))
        ->add('category_name', 'text', array(
            'label' => 'Name',
            'data' => isset($data['category_name']) ? $data['category_name'] : ''
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
            '@phpManufaktur/flexContent/Template', 'backend/category.type.edit.twig'),
            array(
                'usage' => self::$usage,
                'toolbar' => $this->getToolbar('categories'),
                'message' => $this->getMessage(),
                'form' => $form->createView()
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
                $this->CategoryTypeData->delete(self::$category_id);
                $this->setMessage('The category type %category% was successfull deleted.',
                    array('%category%' => $category['category_name']));
                return true;
            }

            if (empty($category['category_name'])) {
                $this->setMessage('Please type in a name for the category type.');
                return false;
            }

            // check for forbidden chars in the category name
            foreach (CategoryTypeData::$forbidden_chars as $forbidden) {
                if (false !== strpos($category['category_name'], $forbidden)) {
                    $this->setMessage('The category type name %category% contains the forbidden character %char%, please change the name.',
                        array('%char%' => $forbidden, '%category%' => $category['tag_name']));
                    return false;
                }
            }

            // check if the category already exists
            if ((self::$category_id < 1) && $this->CategoryTypeData->existsName($category['category_name'])) {
                $this->setMessage('The category type %category% already exists and can not inserted!',
                    array('%category%' => $category['category_name']));
                return false;
            }

            $data['category_name'] = $category['category_name'];
            $data['category_description'] = !empty($category['category_description']) ? $category['category_description'] : '';
            $data['target_url'] = $category['target_url'];

            if (self::$category_id < 1) {
                // create a new category type record
                $this->CategoryTypeData->insert($data, self::$category_id);
                // important: set the category_id also in the $data array!
                $data['category_id'] = self::$category_id;
                $this->setMessage('Successfull create the new category type %category%.',
                    array('%category%' => $data['category_name']));
            }
            else {
                // update an existing record
                $this->CategoryTypeData->update(self::$category_id, $data);
                $this->setMessage('Updated the category type %category%',
                    array('%category%' => $data['category_name']));
            }
            return true;
        }
        else {
            // general error (timeout, CSFR ...)
            $this->setMessage('The form is not valid, please check your input and try again!');
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
            '@phpManufaktur/flexContent/Template', 'backend/category.type.list.twig'),
            array(
                'usage' => self::$usage,
                'toolbar' => $this->getToolbar('categories'),
                'message' => $this->getMessage(),
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

        $data = array();
        if ((self::$category_id > 0) && (false === ($data = $this->CategoryTypeData->select(self::$category_id)))) {
            $this->setMessage('The Category Type record with the ID %id% does not exists!',
                array('%id%' => self::$category_id));
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

        // check the form data and set self::$contact_id
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
        $subRequest = Request::create('/admin/mediabrowser', 'GET', array(
            'usage' => self::$usage,
            'start' => self::$config['content']['images']['directory']['start'],
            'redirect' => '/admin/flexcontent/category/image/check/id/'.self::$category_id,
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
            $this->setMessage('There was no image selected.');
        }
        else {
            // udate the Category record
            $data = array(
                'category_image' => $image
            );
            $this->CategoryTypeData->update(self::$category_id, $data);
            $this->setMessage('The image %image% was successfull inserted.',
                array('%image%' => basename($image)));
        }

        if (false === ($data = $this->CategoryTypeData->select(self::$category_id))) {
            $this->setMessage('The Category Type record with the ID %id% does not exists!',
                array('%id%' => self::$category_id));
        }
        $form = $this->getCategoryTypeForm($data);
        return $this->renderCategoryTypeForm($form);
    }

}
