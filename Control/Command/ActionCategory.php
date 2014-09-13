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
use phpManufaktur\flexContent\Data\Content\Category;
use phpManufaktur\flexContent\Data\Content\CategoryType;
use phpManufaktur\flexContent\Data\Content\Content;
use phpManufaktur\flexContent\Data\Content\Tag;
use phpManufaktur\flexContent\Control\RemoteClient;

class ActionCategory extends Basic
{
    protected static $parameter = null;
    protected static $config = null;
    protected static $language = null;

    protected $CategoryData = null;
    protected $CategoryTypeData = null;
    protected $ContentData = null;
    protected $TagData = null;
    protected $Tools = null;

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

        $this->CategoryData = new Category($app);
        $this->CategoryTypeData = new CategoryType($app);
        $this->ContentData = new Content($app);
        $this->TagData = new Tag($app);
        $this->Tools = new Tools($app);
    }

    /**
     * Collect the category data and show the category overview
     *
     * @return \phpManufaktur\Basic\Control\Pattern\rendered
     */
    protected function showCategoryID()
    {
        if (isset(self::$parameter['remote'])) {
            // request the category from a remote server
            $Remote = new RemoteClient($this->app);
            if (false === ($response = $Remote->getContent(self::$parameter, self::$config, self::$language))) {
                // something went terribly wrong ...
                return $this->promptAlert();
            }
        }
        else {
            $response = array(
                'category' => array(),
                'contents' => array()
            );
            // default - query the local category data
            if (false === ($response['category'] = $this->CategoryTypeData->select(self::$parameter['category_id']))) {
                $this->setAlert('The Category with the <strong>ID %id%</strong> does not exists for the language <strong>%language%</strong>!',
                    array('%id%' => self::$parameter['category_id'], '%language%' => self::$language),
                    self::ALERT_TYPE_DANGER, true, array(__METHOD__, __LINE__));
                return $this->promptAlert();
            }

            // highlight search results?
            if (isset(self::$parameter['highlight']) && is_array(self::$parameter['highlight'])) {
                foreach (self::$parameter['highlight'] as $highlight) {
                    $this->Tools->highlightSearchResult($highlight, $response['category']['category_description']);
                }
            }
            // replace #hashtags
            $this->Tools->linkTags($response['category']['category_description'], self::$language);

            if (false === ($contents = $this->ContentData->selectContentsByCategoryID(self::$parameter['category_id'],
                self::$parameter['content_status'], self::$parameter['content_limit']))) {
                $this->setAlert('The Category %category_name% does not contain any active contents',
                    array('%category_name%' => $response['category']['category_name']), self::ALERT_TYPE_WARNING,
                    array(__METHOD__, __LINE__));
            }

            if (is_array($contents)) {
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

                    // replace #hashtags
                    $this->Tools->linkTags($contents[$i]['teaser'], self::$language);
                    $this->Tools->linkTags($contents[$i]['content'], self::$language);
                }
                $response['contents'] = $contents;
            }
        }
        $result = $this->app['twig']->render($this->app['utils']->getTemplateFile(
            '@phpManufaktur/flexContent/Template', 'command/category.twig',
            $this->getPreferredTemplateStyle()),
            array(
                'basic' => $this->getBasicSettings(),
                'config' => self::$config,
                'parameter' => self::$parameter,
                'permalink_base_url' => CMS_URL.str_ireplace('{language}', strtolower(self::$language), self::$config['content']['permalink']['directory']),
                'category' => $response['category'],
                'contents' => $response['contents']
            ));

        $params = array();
        $params['library'] = null;
        if (self::$parameter['check_jquery']) {
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
        $params['canonical'] = $this->Tools->getPermalinkBaseURL(self::$language).'/category/'.$response['category']['category_permalink'];
        return $this->app->json(array(
            'parameter' => $params,
            'response' => $result
        ));

    }

    /**
     * Controller to handle categories
     *
     * @param Application $app
     */
    public function ControllerCategory(Application $app)
    {
        $this->initParameters($app);

        // get the kitCommand parameters
        self::$parameter = $this->getCommandParameters();

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

        // access the default parameters for action -> category from the configuration
        $default_parameter = self::$config['kitcommand']['parameter']['action']['category'];

        // the category ID is always needed!
        self::$parameter['category_id'] = isset(self::$parameter['category_id']) ? self::$parameter['category_id'] : -1;

        if ((self::$parameter['category_id'] > 0) && ('FAQ' == $this->CategoryTypeData->selectType(self::$parameter['category_id']))) {
            // this is a FAQ not a CATEGORY!
            $FAQ = new ActionFAQ();
            return $FAQ->ControllerFAQ($app);
        }

        // optional: content ID
        self::$parameter['content_id'] = isset(self::$parameter['content_id']) ? self::$parameter['content_id'] : -1;

        // check wether to use the flexcontent.css or not
        self::$parameter['load_css'] = (isset(self::$parameter['load_css']) && ((self::$parameter['load_css'] == 0) || (strtolower(self::$parameter['load_css']) == 'false'))) ? false : $default_parameter['load_css'];
        // disable the jquery check?
        self::$parameter['check_jquery'] = (isset(self::$parameter['check_jquery']) && ((self::$parameter['check_jquery'] == 0) || (strtolower(self::$parameter['check_jquery']) == 'false'))) ? false : $default_parameter['check_jquery'];

        // set the title level - default 1 = <h1>
        self::$parameter['title_level'] = (isset(self::$parameter['title_level']) && is_numeric(self::$parameter['title_level'])) ? self::$parameter['title_level'] : $default_parameter['title_level'];

        // show the category name above?
        self::$parameter['category_name'] = (isset(self::$parameter['category_name']) && ((self::$parameter['category_name'] == 0) || (strtolower(self::$parameter['category_name']) == 'false'))) ? false : $default_parameter['category_name'];

        // show the category description?
        self::$parameter['category_description'] = (isset(self::$parameter['category_description']) && ((self::$parameter['category_description'] == 0) || (strtolower(self::$parameter['category_description']) == 'false'))) ? false : $default_parameter['category_description'];

        // show the category image?
        self::$parameter['category_image'] = (isset(self::$parameter['category_image']) && ((self::$parameter['category_image'] == 0) || (strtolower(self::$parameter['category_image']) == 'false'))) ? false : $default_parameter['category_image'];

        // maximum size for the category image
        self::$parameter['category_image_max_width'] = (isset(self::$parameter['category_image_max_width'])) ? intval(self::$parameter['category_image_max_width']) : $default_parameter['category_image_max_width'];
        self::$parameter['category_image_max_height'] = (isset(self::$parameter['category_image_max_height'])) ? intval(self::$parameter['category_image_max_height']) : $default_parameter['category_image_max_height'];

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

        // limit for the content items
        self::$parameter['content_limit'] = (isset(self::$parameter['content_limit'])) ? intval(self::$parameter['content_limit']) : $default_parameter['content_limit'];

        // expose content items?
        self::$parameter['content_exposed'] = (isset(self::$parameter['content_exposed'])) ? intval(self::$parameter['content_exposed']) : $default_parameter['content_exposed'];
        if (!in_array(self::$parameter['content_exposed'], array(0,1,2,3,4,6,12))) {
            self::$parameter['content_exposed'] = 2;
            $this->setAlert('Please check the parameter content_exposed, allowed values are only 0,1,2,3,4,6 or 12!', array(), self::ALERT_TYPE_WARNING);
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

        self::$parameter['content_view'] = (isset(self::$parameter['content_view'])) ? strtolower(self::$parameter['content_view']) : $default_parameter['content_view'];

        if (!in_array(self::$parameter['content_view'], self::$view_array)) {
            // unknown value for the view[] parameter
            $this->setAlert('The parameter <code>%parameter%[%value%]</code> for the kitCommand <code>~~ %command% ~~</code> is unknown, please check the parameter and the given value!',
                array('%parameter%' => 'content_view', '%value%' => self::$parameter['content_view'], '%command%' => 'flexContent'), self::ALERT_TYPE_DANGER,
                true, array(__METHOD__, __LINE__));
            return $this->promptAlert();
        }

        // show content description?
        self::$parameter['content_description'] = (isset(self::$parameter['content_description']) && ((self::$parameter['content_description'] == 0) || (strtolower(self::$parameter['content_description']) == 'false'))) ? false : $default_parameter['content_description'];

        // show content tags?
        self::$parameter['content_tags'] = (isset(self::$parameter['content_tags']) && ((self::$parameter['content_tags'] == 0) || (strtolower(self::$parameter['content_tags']) == 'false'))) ? false : $default_parameter['content_tags'];

        // show content categories?
        self::$parameter['content_categories'] = (isset(self::$parameter['content_categories']) && ((self::$parameter['content_categories'] == 1) || (strtolower(self::$parameter['content_categories']) == 'true'))) ? true : $default_parameter['content_categories'];

        // show content author?
        self::$parameter['content_author'] = (isset(self::$parameter['content_author']) && ((self::$parameter['content_author'] == 0) || (strtolower(self::$parameter['content_author']) == 'false'))) ? false : $default_parameter['content_author'];

        // show content date?
        self::$parameter['content_date'] = (isset(self::$parameter['content_date']) && ((self::$parameter['content_date'] == 0) || (strtolower(self::$parameter['content_date']) == 'false'))) ? false : $default_parameter['content_date'];


        if (self::$parameter['category_id'] > 0) {
            // show the category
            return $this->showCategoryID();
        }

        // Ooops ...
        $this->setAlert('Fatal error: Missing the category ID!', array(), self::ALERT_TYPE_DANGER, true, array(__METHOD__, __LINE__));
        return $this->promptAlert();
    }
}
