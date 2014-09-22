<?php

/**
 * flexContent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/flexContent
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\flexContent\Control\Command;

use phpManufaktur\Basic\Control\kitCommand\Basic;
use Silex\Application;
use phpManufaktur\flexContent\Control\Configuration;
use phpManufaktur\flexContent\Data\Content\Content;
use phpManufaktur\flexContent\Data\Content\Category;
use phpManufaktur\flexContent\Data\Content\Tag;
use phpManufaktur\flexContent\Control\RemoteClient;

class ActionList extends Basic
{
    protected static $parameter = null;
    protected static $config = null;
    protected static $language = null;

    protected $ContentData = null;
    protected $CategoryData = null;
    protected $TagData = null;
    protected $Tools = null;
    protected $Remote = null;

    protected static $view_array = array('content', 'teaser','none');

    /**
     * (non-PHPdoc)
     * @see \phpManufaktur\Basic\Control\kitCommand\Basic::initParameters()
     */
    protected function initParameters(Application $app, $parameter_id=-1)
    {
        parent::initParameters($app, $parameter_id);

        $Config = new Configuration($app);
        self::$config = $Config->getConfiguration();

        self::$language = $this->getCMSlocale();

        $this->ContentData = new Content($app);
        $this->CategoryData = new Category($app);
        $this->TagData = new Tag($app);
        $this->Tools = new Tools($app);
        $this->Remote = new RemoteClient($app);
    }

    /**
     * Generate a list with contents and return the dialog
     *
     * @return string
     */
    protected function showList()
    {
        if (!isset(self::$parameter['remote']) && (self::$parameter['paging'] > 0)) {
            // PAGING is enabled and REMOTE is not active
            if (isset(self::$parameter['page'])) {
                self::$parameter['previous_page'] = self::$parameter['page']-1;
                self::$parameter['next_page'] = self::$parameter['page']+1;
                $paging_from = self::$parameter['paging'] * (self::$parameter['page']-1);
            }
            else {
                self::$parameter['previous_page'] = 0;
                self::$parameter['next_page'] = 2;
                $paging_from = 0;
            }
        }
        else {
            // PAGING is OFF
            self::$parameter['previous_page'] = 0;
            self::$parameter['next_page'] = 0;
            $paging_from = 0;
            // set the paging explicit to zero (in case REMOTE is active)
            self::$parameter['paging'] = 0;
        }

        $type = (strtoupper(self::$parameter['type']) == 'EVENT') ? 'EVENT' : 'DEFAULT';
        $contents = $this->ContentData->selectContentList(self::$language, self::$parameter['content_limit'],
            self::$parameter['categories'], self::$parameter['categories_exclude'], self::$parameter['content_status'],
            self::$parameter['order_by'], self::$parameter['order_direction'], $type, $paging_from,
            self::$parameter['paging'], self::$parameter['content_exclude']);

        if (is_array($contents)) {
            // count the available contents
            $total = $this->ContentData->count(self::$parameter['content_status']);
            if ((self::$parameter['paging'] > 0) && ((sizeof($contents) < self::$parameter['paging']) ||
                (((self::$parameter['previous_page']+1) * self::$parameter['paging'])+self::$parameter['paging'] == $total)) ||
                (self::$parameter['paging'] == $total)) {
                // no next page available ...
                self::$parameter['next_page'] = 0;
            }

            for ($i=0; $i < sizeof($contents); $i++) {
                $contents[$i]['categories'] = $this->CategoryData->selectCategoriesByContentID($contents[$i]['content_id']);
                $contents[$i]['tags'] = $this->TagData->selectTagArrayForContentID($contents[$i]['content_id']);

                // highlight search results?
                if (isset(self::$parameter['highlight']) && is_array(self::$parameter['highlight'])) {
                    foreach (self::$parameter['highlight'] as $highlight) {
                        $this->Tools->highlightSearchResult($highlight, $contents[$i]['teaser']);
                        $this->Tools->highlightSearchResult($highlight, $contents[$i]['content']);
                        $this->Tools->highlightSearchResult($highlight, $contents[$i]['description']);
                    }
                }

                // replace #tags
                $this->Tools->linkTags($contents[$i]['teaser'], self::$language);
                $this->Tools->linkTags($contents[$i]['content'], self::$language);
            }
        }

        if (isset(self::$parameter['remote']) && (false !== ($remote_contents = $this->Remote->getContent(
            self::$parameter, self::$config, self::$language)))) {
            if (is_array($remote_contents) && !empty($remote_contents)) {
                if (is_array($contents)) {
                    // merge the contents
                    $contents = array_merge($contents, $remote_contents);
                    // order the merged array by the given order field
                    foreach ($contents as $index => $row) {
                        $order_by[$index] = $row[self::$parameter['order_by']];
                    }
                    $direction = (self::$parameter['order_direction'] == 'DESC') ? SORT_DESC : SORT_ASC;
                    array_multisort($order_by, $direction, $contents);
                    // limit the array to given content_limit
                    $contents = array_splice($contents, 0, self::$parameter['content_limit']);
                }
                else {
                    $contents = $remote_contents;
                }
            }
        }

        if (!is_array($contents) || empty($contents)) {
            if (self::$parameter['hide_if_empty']) {
                // return an empty result (hide)
                return $this->app->json(array(
                    'parameter' => null,
                    'response' => ''
                ));
            }
            else {
                $this->setAlert('This list does not contain any contents!');
            }
        }

        if (self::$parameter['type'] == 'default') {
            $template = 'command/list.twig';
        }
        else {
            $template = 'command/list.simple.twig';
        }

        $result = $this->app['twig']->render($this->app['utils']->getTemplateFile(
            '@phpManufaktur/flexContent/Template', $template,
            $this->getPreferredTemplateStyle()),
            array(
                'basic' => $this->getBasicSettings(),
                'config' => self::$config,
                'parameter' => self::$parameter,
                'permalink_base_url' => CMS_URL.str_ireplace('{language}', strtolower(self::$language), self::$config['content']['permalink']['directory']),
                'contents' => $contents
            ));

        $params = array();
        $params['library'] = null;
        if (self::$parameter['load_jquery']) {
            if (self::$config['kitcommand']['libraries']['enabled'] &&
                !empty(self::$config['kitcommand']['libraries']['jquery'])) {
                // load all predefined jQuery files for flexContent
                foreach (self::$config['kitcommand']['libraries']['jquery'] as $library) {
                    if (!empty($params['library'])) {
                        $params['library'] .= ',';
                    }
                    $params['library'] .= $library;
                }
            }
        }
        if (self::$parameter['load_css']) {
            if (self::$config['kitcommand']['libraries']['enabled'] &&
            !empty(self::$config['kitcommand']['libraries']['css'])) {
                // load all predefined CSS files for flexContent
                foreach (self::$config['kitcommand']['libraries']['css'] as $library) {
                    if (!empty($params['library'])) {
                        $params['library'] .= ',';
                    }
                    // attach to 'library' not to 'css' !!!
                    $params['library'] .= $library;
                }
            }

            // set the CSS parameter
            $params['css'] = 'flexContent,css/flexcontent.min.css,'.$this->getPreferredTemplateStyle();
        }

        return $this->app->json(array(
            'parameter' => $params,
            'response' => $result
        ));
    }

    /**
     * Controller to handle flexContents as a list independent from categories
     *
     * @param Application $app
     * @return string
     */
    public function ControllerList(Application $app)
    {
        $this->initParameters($app);

        // get the kitCommand parameters
        self::$parameter = $this->getCommandParameters();

        // check the CMS GET parameters
        $GET = $this->getCMSgetParameters();
        if (isset($GET['command']) && ($GET['command'] == 'flexcontent')
            && isset($GET['action']) && ($GET['action'] == 'list')) {
            // the command and parameters are set as GET from the CMS
            foreach ($GET as $key => $value) {
                if ($key == 'command') {
                    continue;
                }
                self::$parameter[$key] = $value;
            }
            $this->setCommandParameters(self::$parameter);
        }

        self::$parameter['type'] = isset(self::$parameter['type']) ? strtolower(self::$parameter['type']) : 'default';

        // access the default parameters
        if ((self::$parameter['type'] == 'simple') || (self::$parameter['type'] == 'event')) {
            $default_parameter = self::$config['kitcommand']['parameter']['action']['list_simple'];
        }
        else {
            $default_parameter = self::$config['kitcommand']['parameter']['action']['list'];
        }

        // load flexcontent.css?
        self::$parameter['load_css'] = (isset(self::$parameter['load_css']) && ((self::$parameter['load_css'] == 0) || (strtolower(self::$parameter['load_css']) == 'false'))) ? false : $default_parameter['load_css'];
        // load jquery?
        self::$parameter['load_jquery'] = (isset(self::$parameter['load_jquery']) && ((self::$parameter['load_jquery'] == 0) || (strtolower(self::$parameter['load_jquery']) == 'false'))) ? false : $default_parameter['load_jquery'];

        if (isset(self::$parameter['check_jquery'])) {
            $this->setAlert('The parameter <var>check_jquery[]</var> is no longer available, use <var>load_jquery[]</var> instead.',
                array(), self::ALERT_TYPE_WARNING);
        }

        // set the title level - default 1 = <h1>
        self::$parameter['title_level'] = (isset(self::$parameter['title_level']) && is_numeric(self::$parameter['title_level'])) ? self::$parameter['title_level'] : $default_parameter['title_level'];

        // show only specified categories?
        if (isset(self::$parameter['categories']) && !empty(self::$parameter['categories'])) {
            if (strpos(self::$parameter['categories'], ',')) {
                $explode = explode(',', self::$parameter['categories']);
                $categories = array();
                foreach ($explode as $item) {
                    $categories[] = trim($item);
                }
                self::$parameter['categories'] = $categories;
            }
            else {
                self::$parameter['categories'] = array(trim(self::$parameter['categories']));
            }
        }
        else {
            self::$parameter['categories'] = $default_parameter['categories'];
        }

        // exclude some specified categories?
        if (isset(self::$parameter['categories_exclude']) && !empty(self::$parameter['categories_exclude'])) {
            if (strpos(self::$parameter['categories_exclude'], ',')) {
                $explode = explode(',', self::$parameter['categories_exclude']);
                $categories = array();
                foreach ($explode as $item) {
                    $categories[] = trim($item);
                }
                self::$parameter['categories_exclude'] = $categories;
            }
            else {
                self::$parameter['categories_exclude'] = array(trim(self::$parameter['categories_exclude']));
            }
        }
        else {
            self::$parameter['categories_exclude'] = $default_parameter['categories_exclude'];
        }

        if (self::$parameter['action'] == 'archive') {
            // ARCHIVE MODE!
            self::$parameter['content_status'] = array('ARCHIVED');
        }
        else {
            // status for the contents specified?
            if (isset(self::$parameter['content_status']) && !empty(self::$parameter['content_status'])) {
                $status_string = strtoupper(self::$parameter['content_status']);
                if (strpos($status_string, ',')) {
                    $explode = explode(',', $status_string);
                    $status = array();
                    foreach ($explode as $item) {
                        $status[] = trim($item);
                    }
                    self::$parameter['content_status'] = $status;
                }
                else {
                    self::$parameter['content_status'] = array(trim(self::$parameter['content_status']));
                }
            }
            else {
                self::$parameter['content_status'] = $default_parameter['content_status'];
            }
        }

        // order by
        self::$parameter['order_by'] = (isset(self::$parameter['order_by'])) ? strtolower(self::$parameter['order_by']) : $default_parameter['order_by'];
        // order direction
        self::$parameter['order_direction'] = (isset(self::$parameter['order_direction'])) ? strtoupper(self::$parameter['order_direction']) : $default_parameter['order_direction'];


        // limit for the content items
        if (isset(self::$parameter['content_limit'])) {
            if (false !== ($limit = filter_var(self::$parameter['content_limit'], FILTER_VALIDATE_INT))) {
                self::$parameter['content_limit'] = $limit;
            }
            elseif (strtolower(self::$parameter['content_limit']) == 'null') {
                self::$parameter['content_limit'] = null;
            }
            else {
                self::$parameter['content_limit'] = $default_parameter['content_limit'];
            }
        }
        else {
            self::$parameter['content_limit'] = $default_parameter['content_limit'];
        }


        if (self::$parameter['type'] == 'default') {
            // expose content items?
            self::$parameter['content_exposed'] = (isset(self::$parameter['content_exposed'])) ? intval(self::$parameter['content_exposed']) : $default_parameter['content_exposed'];
            if (!in_array(self::$parameter['content_exposed'], array(0,1,2,3,4,6,12))) {
                self::$parameter['content_exposed'] = 2;
                $this->setAlert('Please check the parameter content_exposed, allowed values are only 0,1,2,3,4,6 or 12!', array(), self::ALERT_TYPE_WARNING);
            }
        }
        else {
            self::$parameter['content_exposed'] = 0;
        }

        // exclude specified content IDs?
        if (isset(self::$parameter['content_exclude']) && !empty(self::$parameter['content_exclude'])) {
            if (strpos(self::$parameter['content_exclude'], ',')) {
                $explode = explode(',', self::$parameter['content_exclude']);
                $contents = array();
                foreach ($explode as $item) {
                    $contents[] = intval($item);
                }
                self::$parameter['content_exclude'] = $contents;
            }
            else {
                self::$parameter['content_exclude'] = array(intval(self::$parameter['content_exclude']));
            }
        }
        else {
            self::$parameter['content_exclude'] = null;
        }

        // show the content image?
        self::$parameter['content_image'] = (isset(self::$parameter['content_image']) && ((self::$parameter['content_image'] == 0) || (strtolower(self::$parameter['content_image']) == 'false'))) ? false : $default_parameter['content_image'];

        // maximum size for the category image
        self::$parameter['content_image_max_width'] = (isset(self::$parameter['content_image_max_width'])) ? intval(self::$parameter['content_image_max_width']) : $default_parameter['content_image_max_width'];
        self::$parameter['content_image_max_height'] = (isset(self::$parameter['content_image_max_height'])) ? intval(self::$parameter['content_image_max_height']) : $default_parameter['content_image_max_height'];

        // maximum size for the SMALL category image
        self::$parameter['content_image_small_max_width'] = (isset(self::$parameter['content_image_small_max_width'])) ? intval(self::$parameter['content_image_small_max_width']) : $default_parameter['content_image_small_max_width'];
        self::$parameter['content_image_small_max_height'] = (isset(self::$parameter['content_image_small_max_height'])) ? intval(self::$parameter['content_image_small_max_height']) : $default_parameter['content_image_small_max_height'];

        // show content title?
        self::$parameter['content_title'] = (isset(self::$parameter['content_title']) && ((self::$parameter['content_title'] == 0) || (strtolower(self::$parameter['content_title']) == 'false'))) ? false : $default_parameter['content_title'];

        // show content description?
        self::$parameter['content_description'] = (isset(self::$parameter['content_description']) && ((self::$parameter['content_description'] == 0) || (strtolower(self::$parameter['content_description']) == 'false'))) ? false : $default_parameter['content_description'];

        // show content description?
        self::$parameter['content_description'] = (isset(self::$parameter['content_description']) && ((self::$parameter['content_description'] == 0) || (strtolower(self::$parameter['content_description']) == 'false'))) ? false : $default_parameter['content_description'];

        self::$parameter['content_view'] = (isset(self::$parameter['content_view'])) ? strtolower(self::$parameter['content_view']) : $default_parameter['content_view'];

        if (!in_array(self::$parameter['content_view'], self::$view_array)) {
            // unknown value for the view[] parameter
            $this->setAlert('The parameter <code>%parameter%[%value%]</code> for the kitCommand <code>~~ %command% ~~</code> is unknown, please check the parameter and the given value!',
                array('%parameter%' => 'content_view', '%value%' => self::$parameter['content_view'], '%command%' => 'flexContent'), self::ALERT_TYPE_DANGER,
                true, array(__METHOD__, __LINE__));
            return $this->promptAlert();
        }

        // show content tags?
        self::$parameter['content_tags'] = (isset(self::$parameter['content_tags']) && ((self::$parameter['content_tags'] == 0) || (strtolower(self::$parameter['content_tags']) == 'false'))) ? false : $default_parameter['content_tags'];

        // show content categories?
        self::$parameter['content_categories'] = (isset(self::$parameter['content_categories']) && ((self::$parameter['content_categories'] == 0) || (strtolower(self::$parameter['content_categories']) == 'false'))) ? false : $default_parameter['content_categories'];

        // show content author?
        self::$parameter['content_author'] = (isset(self::$parameter['content_author']) && ((self::$parameter['content_author'] == 0) || (strtolower(self::$parameter['content_author']) == 'false'))) ? false : $default_parameter['content_author'];

        // show content date?
        self::$parameter['content_date'] = (isset(self::$parameter['content_date']) && ((self::$parameter['content_date'] == 0) || (strtolower(self::$parameter['content_date']) == 'false'))) ? false : $default_parameter['content_date'];

        // use paging?
        self::$parameter['paging'] = (isset(self::$parameter['paging']) && is_numeric(self::$parameter['paging'])) ? (int) self::$parameter['paging'] : $default_parameter['paging'];

        // hide empty result?
        self::$parameter['hide_if_empty'] = (isset(self::$parameter['hide_if_empty']) && (empty(self::$parameter['hide_if_empty']) || (strtolower(self::$parameter['hide_if_empty']) === 'true'))) ? true : false;

        return $this->showList();
    }
}
