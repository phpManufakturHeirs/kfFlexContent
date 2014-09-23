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
use phpManufaktur\flexContent\Data\Content\Content as ContentData;
use phpManufaktur\flexContent\Data\Content\CategoryType as CategoryTypeData;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ContentList extends Admin
{
    protected $ContentData = null;
    protected $CategoryTypeData = null;
    protected static $route = null;
    protected static $columns = null;
    protected static $rows_per_page = null;
    protected static $select_status = null;
    protected static $order_by = null;
    protected static $order_direction = null;
    protected static $current_page = null;
    protected static $max_pages = null;
    protected static $ellipsis = null;



    /**
     * (non-PHPdoc)
     * @see \phpManufaktur\flexContent\Control\Backend\Backend::initialize()
     */
    protected function initialize(Application $app)
    {
        parent::initialize($app);

        $this->ContentData = new ContentData($app);
        $this->CategoryTypeData = new CategoryTypeData($app);

        try {
            // search for the config file in the template directory
            $cfg_file = $this->app['utils']->getTemplateFile('@phpManufaktur/flexContent/Template', 'admin/content.list.json', '', true);
            $cfg = $this->app['utils']->readJSON($cfg_file);

            // get the columns to show in the list
            self::$columns = isset($cfg['columns']) ? $cfg['columns'] : $this->ContentData->getColumns();
            self::$rows_per_page = isset($cfg['list']['rows_per_page']) ? $cfg['list']['rows_per_page'] : 100;
            self::$select_status = isset($cfg['list']['select_status']) ? $cfg['list']['select_status'] : array('UNPUBLISHED', 'PUBLISHED', 'BREAKING', 'HIDDEN');
            self::$order_by = isset($cfg['list']['order']['by']) ? $cfg['list']['order']['by'] : array('content_id');
            self::$order_direction = isset($cfg['list']['order']['direction']) ? $cfg['list']['order']['direction'] : 'DESC';
            self::$ellipsis = isset($cfg['list']['ellipsis']) ? $cfg['list']['ellipsis'] : 240;
        } catch (\Exception $e) {
            // the config file does not exists - use all available columns
            self::$columns = $this->ContentData->getColumns();
            self::$rows_per_page = 100;
            self::$select_status = array('UNPUBLISHED', 'PUBLISHED', 'BREAKING', 'HIDDEN');
            self::$order_by = array('content_id');
            self::$order_direction = 'DESC';
            self::$ellipsis = 240;
        }
        self::$current_page = 1;
        self::$route =  array(
            'pagination' => '/flexcontent/editor/list/page/{page}?order={order}&direction={direction}&usage='.self::$usage,
            'edit' => '/flexcontent/editor/edit/id/{content_id}?usage='.self::$usage,
            'search' => '/flexcontent/editor/list/search?usage='.self::$usage,
            'create' => '/flexcontent/editor/edit?usage='.self::$usage,
            'category' => '/flexcontent/editor/list/category?usage='.self::$usage
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
     * Get the flexContent List for the given page and as defined in list.json
     *
     * @param integer reference $list_page
     * @param integer $rows_per_page
     * @param array $select_status
     * @param integer reference $max_pages
     * @param array $order_by
     * @param string $order_direction
     * @param integer $category_id default null
     * @return null|array list of the selected flexContents
     */
    protected function getList(&$list_page, $rows_per_page, $select_status=null,
        &$max_pages=null, $order_by=null, $order_direction='ASC', $category_id=null)
    {
        // count rows
        $count_rows = $this->ContentData->count($select_status, $category_id);

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

        return $this->ContentData->selectList($limit_from, $rows_per_page, $select_status,
            $order_by, $order_direction, self::$columns, $category_id);
    }

    /**
     * Get the form to select a category
     *
     * @param integer $category_id
     */
    protected function formCategorySelect($category_id=null)
    {

        $event_categories = $this->CategoryTypeData->selectCategoriesByType('EVENT');
        $faq_categories = $this->CategoryTypeData->selectCategoriesByType('FAQ');
        $glossary_categories = $this->CategoryTypeData->selectCategoriesByType('GLOSSARY');

        $choices = array();
        $choices['ALL'] = '- all categories -';
        if ((is_array($event_categories) && count($event_categories) > 0) ||
            (is_array($faq_categories) && count($faq_categories) > 0) ||
            (is_array($glossary_categories) && count($glossary_categories) > 0)) {
            $choices['DEFAULT'] = '- Articles only -';
            if (is_array($event_categories) && count($event_categories) > 0) {
                $choices['EVENT'] = '- Event items only -';
            }
            if (is_array($faq_categories) && count($faq_categories) > 0) {
                $choices['FAQ'] = '- FAQ items only -';
            }
            if (is_array($glossary_categories) && count($glossary_categories) > 0) {
                $choices['GLOSSARY'] = '- Glossary items only -';
            }
        }
        $categories = $this->CategoryTypeData->getListForSelect(null);
        foreach ($categories as $key => $value) {
            $choices[$key] = $value;
        }

        $current = null;
        if (!is_null($category_id)) {
            if (is_array($category_id)) {
                // use the first category to get the category type
                $type = $this->CategoryTypeData->select($category_id[0]);
                $current = $type['category_type'];
            }
            else {
                // specific category selected
                $current = $category_id;
            }
        }

        return $this->app['form.factory']->createBuilder('form')
            ->add('category', 'choice', array(
                'choices' => $choices,
                'empty_value' => false, //'- all categories -',
                'expanded' => false,
                'required' => false,
                'data' => $current,
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->getForm();
    }

    /**
     * Controller to show a list with all flexContent records
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
//$this->app['session']->remove(self::SESSION_CATEGORY_ID);
        if (null === $app['session']->get(self::SESSION_CATEGORY_ID)) {
             $app['session']->set(self::SESSION_CATEGORY_ID,
                 $this->validateCategoryValue(self::$config['list']['category']['default']));
        }
        if (($category_id = $app['session']->get(self::SESSION_CATEGORY_ID)) < 1) {
            $category_id = null;
        }

        $order_by = explode(',', $app['request']->get('order', implode(',', self::$order_by)));
        $order_direction = $app['request']->get('direction', self::$order_direction);

        $contents = $this->getList(self::$current_page, self::$rows_per_page, self::$select_status,
            self::$max_pages, $order_by, $order_direction, $category_id);

        $categoryForm = $this->formCategorySelect($category_id);

        return $this->app['twig']->render($this->app['utils']->getTemplateFile(
            '@phpManufaktur/flexContent/Template', 'admin/content.list.twig'),
            array(
                'usage' => self::$usage,
                'toolbar' => $this->getToolbar('list'),
                'alert' => $this->getAlert(),
                'contents' => $contents,
                'columns' => self::$columns,
                'current_page' => self::$current_page,
                'route' => self::$route,
                'order_by' => $order_by,
                'order_direction' => strtolower($order_direction),
                'last_page' => self::$max_pages,
                'ellipsis' => self::$ellipsis,
                'config' => self::$config,
                'category_form' => $categoryForm->createView()
            ));
    }

    /**
     * Controller to search any content
     *
     * @param Application $app
     */
    public function ControllerListSearch(Application $app)
    {
        $this->initialize($app);

        $order_by = explode(',', $app['request']->get('order', implode(',', self::$order_by)));
        $order_direction = $app['request']->get('direction', self::$order_direction);

        if (null == ($search = $this->app['request']->get('search'))) {
            $contents = array();
            $this->setAlert('Please specify a search term!', array(), self::ALERT_TYPE_WARNING);
        }
        else {
            if (false === ($contents = $this->ContentData->SearchContent($search, $order_by, $order_direction))) {
                $contents = array();
                $this->setAlert('No hits for the search term <i>%search%</i>!',
                    array('%search%' => $search), self::ALERT_TYPE_WARNING);
            }
            else {
                $this->setAlert('%count% hits for the search term </i>%search%</i>.',
                    array('%count%' => count($contents), '%search%' => $search), self::ALERT_TYPE_SUCCESS);
            }
        }

        self::$route['order'] = '/flexcontent/editor/list/search?search='.urlencode($search).'&order={order}&direction={direction}&usage='.self::$usage;

        return $this->app['twig']->render($this->app['utils']->getTemplateFile(
            '@phpManufaktur/flexContent/Template', 'admin/content.list.search.twig'),
            array(
                'usage' => self::$usage,
                'toolbar' => $this->getToolbar('list'),
                'alert' => $this->getAlert(),
                'contents' => $contents,
                'columns' => self::$columns,
                'current_page' => self::$current_page,
                'route' => self::$route,
                'order_by' => $order_by,
                'order_direction' => strtolower($order_direction),
                'last_page' => self::$max_pages,
                'ellipsis' => self::$ellipsis,
                'search' => $search,
                'config' => self::$config
            ));
    }

    /**
     * Validate the category value for usage with the SESSION
     *
     * @param mixed $value
     * @return number|array
     */
    protected function validateCategoryValue($value)
    {
        $value = strtoupper($value);
        if ($value == 'ARTICLE') {
            $value = 'DEFAULT';
        }
        switch ($value) {
            case 'ALL':
                return -1;
            case 'DEFAULT':
            case 'EVENT':
            case 'FAQ':
            case 'GLOSSARY':
                $items = $this->CategoryTypeData->selectCategoriesByType($value);
                $categories = array();
                foreach ($items as $item) {
                    $categories[] = $item['category_id'];
                }
                return $categories;
            default:
                return $value;
        }
    }

    /**
     * Controller for the category selection
     *
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function ControllerListCategory(Application $app)
    {
        $this->initialize($app);

        $form = $this->formCategorySelect();
        // get the requested data
        $form->bind($this->app['request']);

        if ($form->isValid()) {
            // the form is valid
            $data = $form->getData();
            if (!is_null($data['category'])) {
                $app['session']->set(self::SESSION_CATEGORY_ID, $this->validateCategoryValue($data['category']));
            }
            else {
                $app['session']->set(self::SESSION_CATEGORY_ID, 'ALL');  //remove(self::SESSION_CATEGORY_ID);
            }
        }
        else {
            // general error (timeout, CSFR ...)
            $this->setAlert('The form is not valid, please check your input and try again!', array(),
                self::ALERT_TYPE_DANGER, true, array('form_errors' => $form->getErrorsAsString(),
                    'method' => __METHOD__, 'line' => __LINE__));
        }

        $subRequest = Request::create('/flexcontent/editor/list', 'GET', array(
            'usage' => self::$usage
        ));
        return $app->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    }
}
