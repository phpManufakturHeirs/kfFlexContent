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

class ContentList extends Admin
{
    protected $ContentData = null;
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
            'create' => '/flexcontent/editor/edit?usage='.self::$usage
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
     * @return null|array list of the selected flexContents
     */
    protected function getList(&$list_page, $rows_per_page, $select_status=null, &$max_pages=null, $order_by=null, $order_direction='ASC')
    {
        // count rows
        $count_rows = $this->ContentData->count($select_status);

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

        return $this->ContentData->selectList($limit_from, $rows_per_page, $select_status, $order_by, $order_direction, self::$columns);
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

        $order_by = explode(',', $app['request']->get('order', implode(',', self::$order_by)));
        $order_direction = $app['request']->get('direction', self::$order_direction);

        $contents = $this->getList(self::$current_page, self::$rows_per_page, self::$select_status, self::$max_pages, $order_by, $order_direction);

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
                'config' => self::$config
            ));
    }

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
}
