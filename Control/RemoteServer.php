<?php

/**
 * flexContent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/event
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

    protected function ResponseList()
    {
        $ContentData = new Content($this->app);
        $CategoryData = new Category($this->app);
        $TagData = new Tag($this->app);
        $Tools = new Tools($this->app);

        if (false !== ($contents = $ContentData->selectContentList(
            self::$locale,
            $this->app['request']->request->get('content_limit', 100),
            self::$categories,
            array(),
            $this->app['request']->request->get('status', array('PUBLISHED','BREAKING','HIDDEN','ARCHIVED')),
            $this->app['request']->request->get('order_by', 'publish_from'),
            $this->app['request']->request->get('order_direction', 'DESC'),
            $this->app['request']->request->get('category_type', 'DEFAULT'),
            0, // PAGING_FROM is disabled for remote access
            0  // PAGING_TO is disabled for remote access
        ))) {
            for ($i=0; $i < sizeof($contents); $i++) {
                $contents[$i]['categories'] = $CategoryData->selectCategoriesByContentID($contents[$i]['content_id']);
                $contents[$i]['tags'] = $TagData->selectTagArrayForContentID($contents[$i]['content_id']);
                // replace #tags
                $Tools->linkTags($contents[$i]['teaser'], self::$locale);
                $Tools->linkTags($contents[$i]['content'], self::$locale);
            }
            return $contents;
        }
        return array();
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

        self::$config = $app['utils']->readJSON(MANUFAKTUR_PATH.'/flexcontent/config.flexcontent.json');

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
            case 'info':
                $response = $this->ResponseInfo();
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
