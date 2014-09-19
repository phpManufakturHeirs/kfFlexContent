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

class ActionView extends Basic
{
    protected $ContentData = null;
    protected $CategoryData = null;
    protected $CategoryTypeData = null;
    protected $TagData = null;
    protected $Tools = null;

    protected static $parameter = null;
    protected static $config = null;
    protected static $language = null;

    protected static $view_array = array('content', 'teaser','none');
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
     * Check if the content can be shown at the frontend
     *
     * @param array $content active content record
     * @return boolean
     */
    protected function canShowContent($content)
    {
        if (strtotime($content['publish_from']) > time()) {
            // content is not published yet ...
            $this->setAlert('No active content available!', array(), self::ALERT_TYPE_WARNING, true, array(__METHOD__, __LINE__));
            return false;
        }

        if (!in_array($content['status'], self::$allowed_status_array)) {
            // it's not allowed to show content
            $this->setAlert('No active content available!', array(), self::ALERT_TYPE_WARNING, true, array(__METHOD__, __LINE__));
            return false;
        }

        if (!empty($content['redirect_url'])) {
            // can not handle a redirect within a iFrame!
            $this->setAlert('Can not handle the requested redirect at this place - use the <a href="%permalink%" target="_blank">permanent link</a> instead!',
                array('%permalink%' => CMS_URL.'/content/'.$content['permalink']), self::ALERT_TYPE_WARNING, true, array(__METHOD__, __LINE__));
            return false;
        }

        // can show content
        return true;
    }


    /**
     * Show the content assigned to the specified content ID
     *
     * @return \phpManufaktur\Basic\Control\Pattern\rendered
     */
    protected function showID()
    {
        if (isset(self::$parameter['remote']) && !empty(self::$parameter['remote'])) {
            // retrieve the content from a remote server
            $Remote = new RemoteClient($this->app);
            if (false === ($response = $Remote->getContent(self::$parameter, self::$config, self::$language))) {
                // something went terribly wrong ...
                return $this->promptAlert();
            }
        }
        else {
            // local access
            if (!empty(self::$parameter['permalink'])) {
                if (false === (self::$parameter['content_id'] = $this->ContentData->selectContentIDbyPermaLink(self::$parameter['permalink'], self::$language))) {
                    $this->setAlert('The permalink <b>%permalink%</b> does not exists!',
                        array('%permalink%' => self::$parameter['permalink']), self::ALERT_TYPE_DANGER,
                        true, array(__METHOD__, __LINE__));
                    return $this->promptAlert();
                }
            }
            if (false === ($content = $this->ContentData->select(self::$parameter['content_id'], self::$language))) {
                $this->setAlert('The flexContent record with the <strong>ID %id%</strong> does not exists for the language <strong>%language%</strong>!',
                    array('%id%' => self::$parameter['content_id'], '%language%' => self::$language),
                    self::ALERT_TYPE_DANGER, true, array(__METHOD__, __LINE__));
                return $this->promptAlert();
            }

            if (!$this->canShowContent($content)) {
                return $this->promptAlert();
            }

            // highlight search results?
            if (isset(self::$parameter['highlight']) && is_array(self::$parameter['highlight'])) {
                foreach (self::$parameter['highlight'] as $highlight) {
                    $this->Tools->highlightSearchResult($highlight, $content['teaser']);
                    $this->Tools->highlightSearchResult($highlight, $content['content']);
                    $this->Tools->highlightSearchResult($highlight, $content['description']);
                }
            }

            // create links for the tags
            $this->Tools->linkTags($content['teaser'], self::$language);
            $this->Tools->linkTags($content['content'], self::$language);

            // get the categories for this content ID
            $content['categories'] = $this->CategoryData->selectCategoriesByContentID(self::$parameter['content_id']);

            // get the tags for this content ID
            $content['tags'] = $this->TagData->selectTagArrayForContentID(self::$parameter['content_id']);

            // select the previous and the next content
            $previous_content = $this->ContentData->selectPreviousContentForID(self::$parameter['content_id'], self::$language);
            $next_content = $this->ContentData->selectNextContentForID(self::$parameter['content_id'], self::$language);

            // get the primary category
            $primary_category_id = $this->CategoryData->selectPrimaryCategoryIDbyContentID(self::$parameter['content_id']);
            $primary_category = $this->CategoryTypeData->select($primary_category_id);

            $content['author'] = $this->app['account']->getDisplayNameByUsername($content['author_username']);

            $response = array(
                'content' => $content,
                'control' => array(
                    'previous' => $previous_content,
                    'next' => $next_content,
                    'category' => $primary_category
                )
            );
        }

        $result = $this->app['twig']->render($this->app['utils']->getTemplateFile(
            '@phpManufaktur/flexContent/Template', 'command/content.twig',
            $this->getPreferredTemplateStyle()),
            array(
                'basic' => $this->getBasicSettings(),
                'config' => self::$config,
                'content' => $response['content'],
                'parameter' => self::$parameter,
                'permalink_base_url' => $this->Tools->getPermalinkBaseURL(self::$language),
                'control' => $response['control'],
                'author' => $response['content']['author']
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

            // for action[view] we can enable the additional loading of jQuery libraries for embedded kitCommands
            if (self::$config['kitcommand']['content']['kitcommand']['enabled'] &&
                self::$config['kitcommand']['content']['kitcommand']['libraries']['enabled'] &&
                !empty(self::$config['kitcommand']['content']['kitcommand']['libraries']['jquery'])) {
                // load additional jQuery libraries
                foreach (self::$config['kitcommand']['content']['kitcommand']['libraries']['jquery'] as $library) {
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

            // for action[view] we can enable the additional loading of jQuery libraries for embedded kitCommands
            if (self::$config['kitcommand']['content']['kitcommand']['enabled'] &&
                self::$config['kitcommand']['content']['kitcommand']['libraries']['enabled'] &&
                !empty(self::$config['kitcommand']['content']['kitcommand']['libraries']['css'])) {
                // load additional CSS files
                foreach (self::$config['kitcommand']['content']['kitcommand']['libraries']['css'] as $library) {
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
        $params['canonical'] = $this->Tools->getPermalinkBaseURL(self::$language).'/'.$response['content']['permalink'];
        $params['set_header'] = $response['content']['content_id'];
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
    public function controllerView(Application $app)
    {
        $this->initParameters($app);

        // get the kitCommand parameters
        self::$parameter = $this->getCommandParameters();

        // access the default parameters for action -> view from the configuration
        $default_parameter = self::$config['kitcommand']['parameter']['action']['view'];


        // check the CMS GET parameters
        $GET = $this->getCMSgetParameters();
        if (isset($GET['command']) && ($GET['command'] == 'flexcontent') &&
                isset($GET['action']) && ($GET['action'] == 'view')) {
            // the command and parameters are set as GET from the CMS
            foreach ($GET as $key => $value) {
                if ($key == 'command') continue;
                self::$parameter[$key] = $value;
            }
            $this->setCommandParameters(self::$parameter);
        }


        self::$parameter['content_view'] = (isset(self::$parameter['content_view'])) ? strtolower(self::$parameter['content_view']) : $default_parameter['content_view'];

        if (!in_array(self::$parameter['content_view'], self::$view_array)) {
            // unknown value for the view[] parameter
            $this->setAlert('The parameter <code>%parameter%[%value%]</code> for the kitCommand <code>~~ %command% ~~</code> is unknown, please check the parameter and the given value!',
                array('%parameter%' => 'content_view', '%value%' => self::$parameter['content_view'], '%command%' => 'flexContent'), self::ALERT_TYPE_DANGER,
                true, array(__METHOD__, __LINE__));
            return $this->promptAlert();
        }

        // load flexcontent.css?
        self::$parameter['load_css'] = (isset(self::$parameter['load_css']) && ((self::$parameter['load_css'] == 0) || (strtolower(self::$parameter['load_css']) == 'false'))) ? false : $default_parameter['load_css'];
        // load jquery?
        self::$parameter['load_jquery'] = (isset(self::$parameter['load_jquery']) && ((self::$parameter['load_jquery'] == 0) || (strtolower(self::$parameter['load_jquery']) == 'false'))) ? false : $default_parameter['load_jquery'];

        if (isset(self::$parameter['check_jquery'])) {
            $this->setAlert('The parameter <var>check_jquery[]</var> is no longer available, use <var>load_jquery[]</var> instead.',
                array(), self::ALERT_TYPE_WARNING);
        }

        self::$parameter['permalink'] = (isset(self::$parameter['permalink']) && !empty(self::$parameter['permalink'])) ? self::$parameter['permalink'] : '';

        self::$parameter['content_id'] = (isset(self::$parameter['content_id']) && is_numeric(self::$parameter['content_id'])) ? self::$parameter['content_id'] : -1;

        if ((self::$parameter['content_id'] > 0) && ('FAQ' == $this->ContentData->getContentType(self::$parameter['content_id']))) {
            // this article belong to a FAQ!
            if (false !== ($category_id = $this->CategoryData->selectPrimaryCategoryIDbyContentID(self::$parameter['content_id']))) {
                // return the FAQ instead of the article!
                $FAQ = new ActionFAQ();
                return $FAQ->ControllerFAQ($app);
            }
        }

        // set the title above the content?
        self::$parameter['content_title'] = (isset(self::$parameter['content_title']) && ((self::$parameter['content_title'] == 0) || (strtolower(self::$parameter['content_title']) == 'false'))) ? false : $default_parameter['content_title'];

        // set the title level - default 1 = <h1>
        self::$parameter['title_level'] = (isset(self::$parameter['title_level']) && is_numeric(self::$parameter['title_level'])) ? self::$parameter['title_level'] : $default_parameter['title_level'];

        // show the description as sub title?
        self::$parameter['content_description'] = (isset(self::$parameter['content_description']) && ((self::$parameter['content_description'] == 1) || (strtolower(self::$parameter['content_description']) == 'true'))) ? true : $default_parameter['content_description'];

        // show the associated categories?
        self::$parameter['content_categories'] = (isset(self::$parameter['content_categories']) && ((self::$parameter['content_categories'] == 0) || (strtolower(self::$parameter['content_categories']) == 'false'))) ? false : $default_parameter['content_categories'];

        // show the associated tags?
        self::$parameter['content_tags'] = (isset(self::$parameter['content_tags']) && ((self::$parameter['content_tags'] == 0) || (strtolower(self::$parameter['content_tags']) == 'false'))) ? false : $default_parameter['content_tags'];

        // show the permanent link to this content?
        self::$parameter['content_permalink'] = (isset(self::$parameter['content_permalink']) && ((self::$parameter['content_permalink'] == 0) || (strtolower(self::$parameter['content_permalink']) == 'false'))) ? false : $default_parameter['content_permalink'];

        // show the previous - overview - next control?
        self::$parameter['content_control'] = (isset(self::$parameter['content_control']) && ((self::$parameter['content_control'] == 0) || (strtolower(self::$parameter['content_control']) == 'false'))) ? false : $default_parameter['content_control'];

        // show author name?
        self::$parameter['content_author'] = (isset(self::$parameter['content_author']) && ((self::$parameter['content_author'] == 0) || (strtolower(self::$parameter['content_author']) == 'false'))) ? false : $default_parameter['content_author'];

        // show publish_from as date?
        self::$parameter['content_date'] = (isset(self::$parameter['content_date']) && ((self::$parameter['content_date'] == 0) || (strtolower(self::$parameter['content_date']) == 'false'))) ? false : $default_parameter['content_date'];

        // show a rating?
        self::$parameter['content_rating'] = (isset(self::$parameter['content_rating']) && ((self::$parameter['content_rating'] == 0) || (strtolower(self::$parameter['content_rating']) == 'false'))) ? false : $default_parameter['content_rating']['enabled'];

        // enable comments?
        self::$parameter['content_comments'] = (isset(self::$parameter['content_comments']) && ((self::$parameter['content_comments'] == 0) || (strtolower(self::$parameter['content_comments']) == 'false'))) ? false : $default_parameter['content_comments']['enabled'];
        self::$parameter['comments_message'] = (isset($GET['message']) && !empty($GET['message'])) ? $GET['message'] : '';

        if ((self::$parameter['content_id'] > 0) || !empty(self::$parameter['permalink'])) {
            return $this->showID();
        }

        // Ooops ...
        $this->setAlert('Fatal error: Missing the content ID!', array(), self::ALERT_TYPE_DANGER, true, array(__METHOD__, __LINE__));
        return $this->promptAlert();
    }


}
