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
use phpManufaktur\flexContent\Data\Content\Content;
use phpManufaktur\flexContent\Control\Configuration;
use phpManufaktur\flexContent\Data\Content\Category;
use phpManufaktur\flexContent\Data\Content\CategoryType;
use phpManufaktur\flexContent\Data\Content\Tag;
use phpManufaktur\flexContent\Control\RemoteClient;

class ActionFAQ extends Basic
{
    protected $ContentData = null;
    protected $CategoryData = null;
    protected $CategoryTypeData = null;
    protected $TagData = null;
    protected $Tools = null;

    protected static $parameter = null;
    protected static $config = null;
    protected static $language = null;

    protected static $allowed_status_array = array('PUBLISHED', 'BREAKING', 'HIDDEN', 'ARCHIVED');

    /**
     * (non-PHPdoc)
     * @see \phpManufaktur\Basic\Control\kitCommand\Basic::initParameters()
    */
    protected function initParameters(Application $app, $parameter_id=-1)
    {
        parent::initParameters($app);

        $this->ContentData = new Content($app);
        $this->CategoryData = new Category($app);
        $this->CategoryTypeData = new CategoryType($app);
        $this->TagData = new Tag($app);
        $this->Tools = new Tools($app);

        $ConfigurationData = new Configuration($app);
        self::$config = $ConfigurationData->getConfiguration();

        self::$language = strtoupper($this->getCMSlocale());
    }

    /**
     * Return the content prepared as FAQ
     *
     * @return string
     */
    protected function showFAQ()
    {
        $faqs = array();

        $response = array(
            'faq' => array(),
            'category' => array()
        );

        if (isset(self::$parameter['remote'])) {
            // request the FAQ from a remote server
            $Remote = new RemoteClient($this->app);
            if (false === ($response = $Remote->getContent(self::$parameter, self::$config, self::$language))) {
                // something went terribly wrong ...
                return $this->promptAlert();
            }
        }
        else {
            if (!empty(self::$parameter['faq_ids'])) {
                // get the FAQs by the given content IDs
                foreach (self::$parameter['faq_ids'] as $id) {
                    if (!is_null(self::$parameter['content_exclude']) && in_array($id, self::$parameter['content_exclude'])) {
                        // exclude this content ID
                        continue;
                    }
                    if (false === ($content = $this->ContentData->select($id, self::$language))) {
                        $this->setAlert('The flexContent record with the <strong>ID %id%</strong> does not exists for the language <strong>%language%</strong>!',
                            array('%id%' => $id, '%language%' => self::$language),
                            self::ALERT_TYPE_DANGER, true, array(__METHOD__, __LINE__));
                        return $this->promptAlert();
                    }
                    // create links for the tags
                    $this->Tools->linkTags($content['teaser'], self::$language);
                    $this->Tools->linkTags($content['content'], self::$language);
                    // get the categories for this content ID
                    $content['categories'] = $this->CategoryData->selectCategoriesByContentID($id);

                    // get the tags for this content ID
                    $content['tags'] = $this->TagData->selectTagArrayForContentID($id);

                    // get the author name
                    $content['author'] = $this->app['account']->getDisplayNameByUsername($content['author_username']);

                    $response['faqs'][] = $content;
                }
            }
            elseif (self::$parameter['category_id'] > 0) {
                // get the FAQs from the given category
                if (false !== ($contents = $this->ContentData->selectContentsByCategoryID(
                        self::$parameter['category_id'],
                        self::$parameter['content_status'],
                        self::$parameter['content_limit'],
                        self::$parameter['order_by'],
                        self::$parameter['order_direction'],
                        self::$parameter['content_exclude']))) {
                    foreach ($contents as $content) {
                        // create links for the tags
                        $this->Tools->linkTags($content['teaser'], self::$language);
                        $this->Tools->linkTags($content['content'], self::$language);
                        // get the categories for this content ID
                        $content['categories'] = $this->CategoryData->selectCategoriesByContentID($content['content_id']);

                        // get the tags for this content ID
                        $content['tags'] = $this->TagData->selectTagArrayForContentID($content['content_id']);

                        // get the author name
                        $content['author'] = $this->app['account']->getDisplayNameByUsername($content['author_username']);

                        $response['faqs'][] = $content;
                    }
                }
                else {
                    if (self::$parameter['hide_if_empty']) {
                        // hide the FAQ if nothing is available
                        return $this->app->json(array(
                            'parameter' => null,
                            'response' => ''
                        ));
                    }
                    else {
                        // this category has no active contents
                        $this->setAlert('The Category %category_name% does not contain any active contents',
                            array('%category_name%' => self::$parameter['category_id']), self::ALERT_TYPE_WARNING);
                        return $this->promptAlert();
                    }
                }
            }

            $category = array();
            if (self::$parameter['category_id'] > 0) {
                if (false === ($response['category'] = $this->CategoryTypeData->select(self::$parameter['category_id']))) {
                    $this->setAlert('The Category with the <strong>ID %id%</strong> does not exists for the language <strong>%language%</strong>!',
                        array('%id%' => self::$parameter['category_id'], '%language%' => self::$language),
                        self::ALERT_TYPE_DANGER, true, array(__METHOD__, __LINE__));
                    return $this->promptAlert();
                }
            }
        }
        $result = $this->app['twig']->render($this->app['utils']->getTemplateFile(
            '@phpManufaktur/flexContent/Template', 'command/faq.twig',
            $this->getPreferredTemplateStyle()),
            array(
                'category' => $response['category'],
                'faqs' => $response['faqs'],
                'basic' => $this->getBasicSettings(),
                'parameter' => self::$parameter,
                'permalink_base_url' => $this->Tools->getPermalinkBaseURL(self::$language),
                'config' => self::$config
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
        $params['canonical'] = $this->Tools->getPermalinkBaseURL(self::$language).'/faq/'.$response['category']['category_permalink'];
        return $this->app->json(array(
            'parameter' => $params,
            'response' => $result
        ));
    }

    /**
     * Controller for the flexContent parameter action[view]
     *
     * @param Application $app
     * @return string
     */
    public function ControllerFAQ(Application $app)
    {
        $this->initParameters($app);

        // get the kitCommand parameters
        self::$parameter = $this->getCommandParameters();

        // access the default parameters for action -> view from the configuration
        $default_parameter = self::$config['kitcommand']['parameter']['action']['faq'];


        // check the CMS GET parameters
        $GET = $this->getCMSgetParameters();
        if (isset($GET['command']) && ($GET['command'] == 'flexcontent')) {
            // the command and parameters are set as GET from the CMS
            foreach ($GET as $key => $value) {
                if ($key == 'command') continue;
                self::$parameter[$key] = $value;
            }
            $this->setCommandParameters(self::$parameter);
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

        // FAQ category
        self::$parameter['category_id'] = (isset(self::$parameter['category_id']) && is_numeric(self::$parameter['category_id'])) ? self::$parameter['category_id'] : -1;
        self::$parameter['category_name'] = (isset(self::$parameter['category_name']) && ((strtolower(self::$parameter['category_name'] == 'false') || (self::$parameter['category_name'] == 0)))) ? false : $default_parameter['category_name'];
        self::$parameter['category_description'] = (isset(self::$parameter['category_description']) && ((strtolower(self::$parameter['category_description'] == 'false') || (self::$parameter['category_description'] == 0)))) ? false : $default_parameter['category_description'];
        self::$parameter['category_image'] = (isset(self::$parameter['category_image']) && ((strtolower(self::$parameter['category_image'] == 'false') || (self::$parameter['category_image'] == 0)))) ? false : $default_parameter['category_image'];
        self::$parameter['category_image_max_width'] = (isset(self::$parameter['category_image_max_width']) && is_numeric(self::$parameter['category_image_max_width'])) ? self::$parameter['category_image_max_width'] : $default_parameter['category_image_max_width'];
        self::$parameter['category_image_max_height'] = (isset(self::$parameter['category_image_max_height']) && is_numeric(self::$parameter['category_image_max_height'])) ? self::$parameter['category_image_max_height'] : $default_parameter['category_image_max_height'];


        // are FAQ IDs given?
        if (isset(self::$parameter['faq_ids']) && !empty(self::$parameter['faq_ids'])) {
            $faq_ids = array();
            if (strpos(self::$parameter['faq_ids'], ',')) {
                $items = explode(',', self::$parameter['faq_ids']);
                foreach ($items as $item) {
                    $item = trim($item);
                    if (is_numeric($item)) {
                        $faq_ids[] = $item;
                    }
                }
            }
            elseif (is_numeric(trim(self::$parameter['faq_ids']))) {
                $faq_ids = array(trim(self::$parameter['faq_ids']));
            }
            self::$parameter['faq_ids'] = $faq_ids;
        }
        else {
            self::$parameter['faq_ids'] = array();
        }

        // show the permanent link to this content?
        self::$parameter['faq_permalink'] = (isset(self::$parameter['faq_permalink']) && ((self::$parameter['faq_permalink'] == 0) || (strtolower(self::$parameter['faq_permalink']) == 'false'))) ? false : $default_parameter['faq_permalink'];
        // show the previous - overview - next control?
        self::$parameter['faq_control'] = (isset(self::$parameter['faq_control']) && ((self::$parameter['faq_control'] == 0) || (strtolower(self::$parameter['faq_control']) == 'false'))) ? false : $default_parameter['faq_control'];
        // show a FAQ rating?
        self::$parameter['faq_rating'] = (isset(self::$parameter['faq_rating']) && ((self::$parameter['faq_rating'] == 0) || (strtolower(self::$parameter['faq_rating']) == 'false'))) ? false : $default_parameter['faq_rating']['enabled'];
        // show a FAQ comments?
        self::$parameter['faq_comments'] = (isset(self::$parameter['faq_comments']) && ((self::$parameter['faq_comments'] == 0) || (strtolower(self::$parameter['faq_comments']) == 'false'))) ? false : $default_parameter['faq_comments']['enabled'];
        self::$parameter['comments_message'] = (isset($GET['message']) && !empty($GET['message'])) ? $GET['message'] : '';
        // sorting the FAQs
        self::$parameter['order_by'] = (isset(self::$parameter['order_by']) && !empty(self::$parameter['order_by'])) ? strtolower(trim(self::$parameter['order_by'])) : $default_parameter['order_by'];
        self::$parameter['order_direction'] = (isset(self::$parameter['order_direction']) && (strtoupper(trim(self::$parameter['order_direction'])) == 'DESC')) ? 'DESC' : $default_parameter['order_direction'];

        // exists a limit?
        self::$parameter['content_limit'] = (isset(self::$parameter['content_limit']) && is_numeric(self::$parameter['content_limit'])) ? intval(self::$parameter['content_limit']) : $default_parameter['content_limit'];

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

        // expose content items?
        self::$parameter['content_exposed'] = 0; // disabled for FAQ!

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
        // show date?
        self::$parameter['content_date'] = (isset(self::$parameter['content_date']) && ((self::$parameter['content_date'] == 1) || (strtolower(self::$parameter['content_date']) == 'true'))) ? true : $default_parameter['content_date'];
        // show author name?
        self::$parameter['content_author'] = (isset(self::$parameter['content_author']) && ((self::$parameter['content_author'] == 1) || (strtolower(self::$parameter['content_author']) == 'true'))) ? true : $default_parameter['content_author'];
        // show teaser or try to show content?
        self::$parameter['content_view'] = (isset(self::$parameter['content_view'])) ? strtolower(self::$parameter['content_view']) : $default_parameter['content_view'];
        // show the associated categories?
        self::$parameter['content_categories'] = (isset(self::$parameter['categories']) && ((self::$parameter['content_categories'] == 1) || (strtolower(self::$parameter['content_categories']) == 'true'))) ? false : $default_parameter['content_categories'];
        // show the associated tags?
        self::$parameter['content_tags'] = (isset(self::$parameter['content_tags']) && ((self::$parameter['content_tags'] == 0) || (strtolower(self::$parameter['content_tags']) == 'false'))) ? false : $default_parameter['content_tags'];
        // show the associated image?
        self::$parameter['content_image'] = (isset(self::$parameter['content_image']) && ((self::$parameter['content_image'] == 0) || (strtolower(self::$parameter['content_image']) == 'false'))) ? false : $default_parameter['content_image'];
        // image size
        self::$parameter['content_image_max_width'] = (isset(self::$parameter['category_image_max_width']) && is_numeric(self::$parameter['category_image_max_width'])) ? self::$parameter['category_image_max_width'] : $default_parameter['category_image_max_width'];
        self::$parameter['content_image_max_height'] = (isset(self::$parameter['category_image_max_height']) && is_numeric(self::$parameter['category_image_max_height'])) ? self::$parameter['category_image_max_height'] : $default_parameter['category_image_max_height'];
        // show rating?
        self::$parameter['content_rating'] = (isset(self::$parameter['content_rating']) && ((self::$parameter['content_rating'] == 1) || (strtolower(self::$parameter['content_rating']) == 'true'))) ? true : $default_parameter['content_rating']['enabled'];

        // hide empty result?
        self::$parameter['hide_if_empty'] = (isset(self::$parameter['hide_if_empty']) && (empty(self::$parameter['hide_if_empty']) || (strtolower(self::$parameter['hide_if_empty']) === 'true'))) ? true : false;

        if (!empty(self::$parameter['faq_ids']) || (self::$parameter['category_id'] > 0)) {
            return $this->showFAQ();
        }
        else {
            $this->setAlert('Fatal error: Missing FAQ IDs or a Category ID!', array(), self::ALERT_TYPE_DANGER, true, array(__METHOD__, __LINE__));
            return $this->promptAlert();
        }
    }


}
