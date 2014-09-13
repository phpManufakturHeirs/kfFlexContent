<?php

/**
 * flexContent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/flexContent
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\flexContent\Control;

use Silex\Application;
use phpManufaktur\flexContent\Data\Content\CategoryType;
use phpManufaktur\flexContent\Data\Content\Content;
use phpManufaktur\flexContent\Data\Content\Category;
use phpManufaktur\flexContent\Data\Content\Tag;
use phpManufaktur\flexContent\Control\Command\Tools;

class RemoteServer
{
    protected $app = null;
    protected static $locale = null;
    protected static $config = null;
    protected static $client_name = null;
    protected static $client_token = null;
    protected static $action = null;
    protected static $categories = null;
    protected static $allowed_status = array('PUBLISHED','BREAKING','HIDDEN','ARCHIVED');

    /**
     * Return an info about the available categories to the client
     *
     * @return string
     */
    protected function ResponseInfo()
    {
        $response = array();
        $CategoryType = new CategoryType($this->app);

        foreach (self::$categories as $category_id) {
            if (false !== ($category = $CategoryType->select($category_id))) {
                $response[] = $category;
            }
        }

        return $response;
    }

    /**
     * Return a list of articles for the categories specified for this connection
     *
     * @return array
     */
    protected function ResponseList()
    {
        $ContentData = new Content($this->app);
        $CategoryData = new Category($this->app);
        $TagData = new Tag($this->app);
        $Tools = new Tools($this->app);

        $wanted_status = $this->app['request']->request->get('content_status', array('PUBLISHED', 'BREAKING'));
        $status = array();
        foreach ($wanted_status as $allowed_status) {
            if (in_array($allowed_status, self::$allowed_status)) {
                $status[] = $allowed_status;
            }
        }
        if (empty($status)) {
            $status = array('PUBLISHED', 'BREAKING');
        }

        if (false !== ($contents = $ContentData->selectContentList(
            self::$locale,
            $this->app['request']->request->get('content_limit', 100),
            self::$categories, // use always the configured categories
            array(), // don't exclude any categories
            $status,
            $this->app['request']->request->get('order_by', 'publish_from'),
            $this->app['request']->request->get('order_direction', 'DESC'),
            $this->app['request']->request->get('category_type', 'DEFAULT'),
            0, // PAGING_FROM is disabled for remote access
            0  // PAGING_TO is disabled for remote access
        ))) {
            $permalink_base_url = CMS_URL.str_ireplace('{language}', self::$locale, self::$config['content']['permalink']['directory']);
            for ($i=0; $i < sizeof($contents); $i++) {
                // we need the full permalink URL's !
                $contents[$i]['permalink_url'] = $permalink_base_url.'/'.$contents[$i]['permalink'];
                $contents[$i]['teaser_image_url'] = (!empty($contents[$i]['teaser_image'])) ? FRAMEWORK_URL.$contents[$i]['teaser_image'] : '';

                $categories = $CategoryData->selectCategoriesByContentID($contents[$i]['content_id']);
                $contents[$i]['categories'] = array();
                foreach ($categories as $category) {
                    $category['category_permalink_url'] = $permalink_base_url.'/category/'.$category['category_permalink'];
                    $category['category_image_url'] = (!empty($category['category_image'])) ? FRAMEWORK_URL.$category['category_image'] : '';
                    $contents[$i]['categories'][] = $category;
                }
                $tags = $TagData->selectTagArrayForContentID($contents[$i]['content_id']);
                $contents[$i]['tags'] = array();
                foreach ($tags as $tag) {
                    $tag['tag_permalink_url'] = $permalink_base_url.'/buzzword/'.$tag['tag_permalink'];
                    $tag['tag_image_url'] = !empty($tag['tag_image']) ? FRAMEWORK_URL.$tag['tag_image'] : '';
                    $contents[$i]['tags'][] = $tag;
                }
                // replace #tags
                $Tools->linkTags($contents[$i]['teaser'], self::$locale);
                $Tools->linkTags($contents[$i]['content'], self::$locale);
            }
            return $contents;
        }
        return array();
    }

    /**
     * Return the category content to the remote client
     *
     * @return array
     */
    protected function ResponseCategory()
    {
        $CategoryTypeData = new CategoryType($this->app);

        $response = array(
            'category' => array(),
            'contents' => array()
        );

        $category_id = $this->app['request']->request->get('category_id', -1);
        if (!in_array($category_id, self::$categories)) {
            // the requested category is not supported
            return $response;
        }

        if (false === ($category = $CategoryTypeData->select($category_id))) {
            // no hit
            return $response;
        }
        $response['category'] = $category;

        $permalink_base_url = CMS_URL.str_ireplace('{language}', self::$locale, self::$config['content']['permalink']['directory']);

        $response['category']['category_permalink_url'] = $permalink_base_url.'/category/'.$category['category_permalink'];
        $response['category']['category_image_url'] = FRAMEWORK_URL.$category['category_image'];

        $Tools = new Tools($this->app);
        $ContentData = new Content($this->app);

        // replace #hashtags
        $Tools->linkTags($response['category']['category_description'], self::$locale);

        $wanted_status = $this->app['request']->request->get('content_status', array('PUBLISHED', 'BREAKING'));
        $status = array();
        foreach ($wanted_status as $allowed_status) {
            if (in_array($allowed_status, self::$allowed_status)) {
                $status[] = $allowed_status;
            }
        }
        if (empty($status)) {
            $status = array('PUBLISHED', 'BREAKING');
        }

        if (false === ($contents = $ContentData->selectContentsByCategoryID(
            $category_id,
            $status,
            $this->app['request']->request->get('content_limit', 100)))) {
            return $response;
        }

        if (is_array($contents)) {
            $CategoryData = new Category($this->app);
            $TagData = new Tag($this->app);

            for ($i=0; $i < sizeof($contents); $i++) {
                // we need the full permalink URL's !
                $contents[$i]['permalink_url'] = $permalink_base_url.'/'.$contents[$i]['permalink'];
                $contents[$i]['teaser_image_url'] = (!empty($contents[$i]['teaser_image'])) ? FRAMEWORK_URL.$contents[$i]['teaser_image'] : '';

                $categories = $CategoryData->selectCategoriesByContentID($contents[$i]['content_id']);
                $contents[$i]['categories'] = array();
                foreach ($categories as $category) {
                    $category['category_permalink_url'] = $permalink_base_url.'/category/'.$category['category_permalink'];
                    $category['category_image_url'] = (!empty($category['category_image'])) ? FRAMEWORK_URL.$category['category_image'] : '';
                    $contents[$i]['categories'][] = $category;
                }

                $tags = $TagData->selectTagArrayForContentID($contents[$i]['content_id']);
                $contents[$i]['tags'] = array();
                foreach ($tags as $tag) {
                    $tag['tag_permalink_url'] = $permalink_base_url.'/buzzword/'.$tag['tag_permalink'];
                    $tag['tag_image_url'] = !empty($tag['tag_image']) ? FRAMEWORK_URL.$tag['tag_image'] : '';
                    $contents[$i]['tags'][] = $tag;
                }

                // replace #hashtags
                $Tools->linkTags($contents[$i]['teaser'], self::$locale);
                $Tools->linkTags($contents[$i]['content'], self::$locale);
            }
            $response['contents'] = $contents;
        }

        return $response;
    }

    /**
     * Return FAQ content to the remote client
     *
     * @return array
     */
    protected function ResponseFAQ()
    {
        $response = array(
            'category' => array(),
            'faqs' => array()
        );

        $category_id = $this->app['request']->request->get('category_id', -1);
        if (($category_id > 0) && !in_array($category_id, self::$categories)) {
            // the requested category is not supported
            return $response;
        }

        $permalink_base_url = CMS_URL.str_ireplace('{language}', self::$locale, self::$config['content']['permalink']['directory']);

        $CategoryTypeData = new CategoryType($this->app);

        if ($category_id > 0) {
            if (false === ($category = $CategoryTypeData->select($category_id))) {
                // no hit
                return $response;
            }
            if ($category['category_type'] != 'FAQ') {
                // this is no FAQ!
                return $response;
            }
            $response['category'] = $category;
            $response['category']['category_permalink_url'] = $permalink_base_url.'/category/'.$category['category_permalink'];
            $response['category']['category_image_url'] = FRAMEWORK_URL.$category['category_image'];
        }

        $faq_ids = $this->app['request']->request->get('faq_ids', array());
        $faqs = array();

        $ContentData = new Content($this->app);
        $CategoryData = new Category($this->app);
        $TagData = new Tag($this->app);
        $Tools = new Tools($this->app);

        if (!empty($faq_ids)) {
            // get the FAQs by the given content IDs

            foreach ($faq_ids as $id) {
                if (false === ($content = $ContentData->select($id, self::$locale))) {
                    // no content for this ID
                    continue;
                }
                if (false === ($primary_category_id = $CategoryData->selectPrimaryCategoryIDbyContentID($id))) {
                    // cant find the primary category ID
                    continue;
                }
                if (!in_array($primary_category_id, self::$categories)) {
                    // ID is not within the allowed categories
                    continue;
                }
                if ((false === ($category_type = $CategoryTypeData->selectType($primary_category_id))) ||
                    ($category_type != 'FAQ')) {
                    // content does not belong to a FAQ
                    continue;
                }
                if (!in_array($content['status'], self::$allowed_status)) {
                    // content status is not within the allowed status
                    continue;
                }

                // create links for the tags
                $Tools->linkTags($content['teaser'], self::$locale);
                $Tools->linkTags($content['content'], self::$locale);

                // get the categories for this content ID
                $categories = $CategoryData->selectCategoriesByContentID($id);
                $content['categories'] = array();
                foreach ($categories as $category) {
                    $category['category_permalink_url'] = $permalink_base_url.'/category/'.$category['category_permalink'];
                    $category['category_image_url'] = (!empty($category['category_image'])) ? FRAMEWORK_URL.$category['category_image'] : '';
                    $content['categories'][] = $category;
                }

                // get the tags for this content ID
                $tags = $TagData->selectTagArrayForContentID($id);
                $content['tags'] = array();
                foreach ($tags as $tag) {
                    $tag['tag_permalink_url'] = $permalink_base_url.'/buzzword/'.$tag['tag_permalink'];
                    $tag['tag_image_url'] = !empty($tag['tag_image']) ? FRAMEWORK_URL.$tag['tag_image'] : '';
                    $content['tags'][] = $tag;
                }

                // get the author name
                $content['author'] = $this->app['account']->getDisplayNameByUsername($content['author_username']);

                $response['faqs'][] = $content;
            }
        }
        elseif ($category_id > 0) {
            // get the FAQs from the given category
            $wanted_status = $this->app['request']->request->get('content_status', array('PUBLISHED', 'BREAKING'));
            $status = array();
            foreach ($wanted_status as $allowed_status) {
                if (in_array($allowed_status, self::$allowed_status)) {
                    $status[] = $allowed_status;
                }
            }
            if (empty($status)) {
                $status = array('PUBLISHED', 'BREAKING');
            }

            if (false !== ($contents = $ContentData->selectContentsByCategoryID(
                $category_id,
                $status,
                $this->app['request']->request->get('content_limit', 100),
                $this->app['request']->request->get('order_by', 'publish_from'),
                $this->app['request']->request->get('order_direction', 'DESC')))) {

                foreach ($contents as $content) {
                    // create links for the tags
                    $Tools->linkTags($content['teaser'], self::$locale);
                    $Tools->linkTags($content['content'], self::$locale);

                    // get the categories for this content ID
                    $categories = $CategoryData->selectCategoriesByContentID($content['content_id']);
                    $content['categories'] = array();
                    foreach ($categories as $category) {
                        $category['category_permalink_url'] = $permalink_base_url.'/category/'.$category['category_permalink'];
                        $category['category_image_url'] = (!empty($category['category_image'])) ? FRAMEWORK_URL.$category['category_image'] : '';
                        $content['categories'][] = $category;
                    }

                    // get the tags for this content ID
                    $tags = $TagData->selectTagArrayForContentID($content['content_id']);
                    $content['tags'] = array();
                    foreach ($tags as $tag) {
                        $tag['tag_permalink_url'] = $permalink_base_url.'/buzzword/'.$tag['tag_permalink'];
                        $tag['tag_image_url'] = !empty($tag['tag_image']) ? FRAMEWORK_URL.$tag['tag_image'] : '';
                        $content['tags'][] = $tag;
                    }

                    // get the author name
                    $content['author'] = $this->app['account']->getDisplayNameByUsername($content['author_username']);

                    $response['faqs'][] = $content;
                }
            }
        }

        return $response;
    }

    protected function ResponseView()
    {
        $ContentData = new Content($this->app);
        $Tools = new Tools($this->app);
        $CategoryData = new Category($this->app);
        $CategoryTypeData = new CategoryType($this->app);
        $TagData = new Tag($this->app);

        if (false !== ($permalink = $this->app['request']->request->get('permalink', false))) {
            if (false === ($content_id = $ContentData->selectContentIDbyPermaLink($permalink, self::$locale))) {
                return $this->app['translator']->trans('The permalink <b>%permalink%</b> does not exists!',
                    array('%permalink%' => $permalink), 'messages', self::$locale);
            }
        }
        else {
            $content_id = $this->app['request']->request->get('content_id', -1);
        }

        if (false === ($content = $ContentData->select($content_id, self::$locale))) {
            return $this->app['translator']->trans('The flexContent record with the ID %id% does not exists!',
                array('%id%' => $content_id), 'messages', self::$locale);
        }

        if ((strtotime($content['publish_from']) > time()) || !in_array($content['status'], self::$allowed_status) ||
            !empty($content['redirect_url'])) {
            return $this->app['translator']->trans('No active content available!',
                array(), 'messages', self::$locale);
        }

        // create links for the tags
        $Tools->linkTags($content['teaser'], self::$locale);
        $Tools->linkTags($content['content'], self::$locale);

        // get the categories for this content ID
        $content['categories'] = $CategoryData->selectCategoriesByContentID($content_id);

        // get the tags for this content ID
        $content['tags'] = $TagData->selectTagArrayForContentID($content_id);

        // select the previous and the next content
        $previous_content = $ContentData->selectPreviousContentForID($content_id, self::$locale);
        $next_content = $ContentData->selectNextContentForID($content_id, self::$locale);

        // get the primary category
        $primary_category_id = $CategoryData->selectPrimaryCategoryIDbyContentID($content_id);
        $primary_category = $CategoryTypeData->select($primary_category_id);

        $content['author'] = $this->app['account']->getDisplayNameByUsername($content['author_username']);

        $response = array(
            'content' => $content,
            'control' => array(
                'previous' => $previous_content,
                'next' => $next_content,
                'category' => $primary_category
            )
        );
        return $response;
    }

    /**
     * Controller to response to flexContent Client Requests
     *
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function Controller(Application $app)
    {
        $this->app = $app;

        self::$locale = strtolower($app['request']->request->get('locale', 'en'));

        if (false === (self::$client_name = $app['request']->request->get('name', false))) {
            return $app->json(array(
                'status' => 403,
                'message' => $app['translator']->trans('Missing the parameter: %parameter%',
                    array('%parameter%' => 'name'), 'messages', self::$locale)
            ), 403);
        }
        if (false === (self::$client_token = $app['request']->request->get('token', false))) {
            return $app->json(array(
                'status' => 403,
                'message' => $app['translator']->trans('Missing the parameter: %parameter%',
                    array('%parameter%' => 'token'), 'messages', self::$locale)
            ), 403);
        }

        self::$config = $app['utils']->readJSON(MANUFAKTUR_PATH.'/flexContent/config.flexcontent.json');

        // check the name and token
        if (!isset(self::$config['remote']['server'][self::$client_name]) ||
            !isset(self::$config['remote']['server'][self::$client_name]['token']) ||
            (self::$config['remote']['server'][self::$client_name]['token'] != self::$client_token)) {
            // cant identify the remote client
            return $app->json(array(
                'status' => 403,
                'message' => $app['translator']->trans('Connection is not authenticated, please check name and token!',
                    array(), 'messages', self::$locale)
            ), 403);
        }

        if (false === (self::$action = strtolower($app['request']->request->get('action', false)))) {
            return $app->json(array(
                'status' => 400,
                'message' => $app['translator']->trans('Missing the parameter: %parameter%',
                    array('%parameter%' => 'action'), 'messages', self::$locale)
            ), 400);
        }

        if (!isset(self::$config['remote']['server'][self::$client_name]['categories']) ||
            !is_array(self::$config['remote']['server'][self::$client_name]['categories']) ||
            empty(self::$config['remote']['server'][self::$client_name]['categories'])) {
            // missing the definition for the categories
            return $app->json(array(
                'status' => 500,
                'message' => $app['translator']->trans('The server is missing the definition of the allowed categories for the client',
                    array(), 'messages', self::$locale)
            ), 500);
        }
        self::$categories = self::$config['remote']['server'][self::$client_name]['categories'];

        switch (self::$action) {
            case 'list':
                $response = $this->ResponseList();
                break;
            case 'category':
                $response = $this->ResponseCategory();
                break;
            case 'info':
                $response = $this->ResponseInfo();
                break;
            case 'faq':
                $response = $this->ResponseFAQ();
                break;
            case 'view':
                $response = $this->ResponseView();
                break;
            default:
                // don't now how to handle the action
                return $app->json(array(
                    'status' => 404,
                    'message' => $app['translator']->trans('The action: %action% is not supported!',
                        array('%action%' => self::$action), 'messages', self::$locale)
                ), 404);
        }

        return $app->json(array(
            'status' => 200,
            'message' => 'ok',
            'response' => $response
        ), 200);
    }
}
