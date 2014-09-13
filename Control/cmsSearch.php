<?php

/**
 * flexContent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/flexContent
 * @copyright 2014 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\flexContent\Control;

use Silex\Application;
use phpManufaktur\Basic\Data\CMS\Page;
use phpManufaktur\flexContent\Data\Content\CategoryType;
use phpManufaktur\flexContent\Data\Content\Content;
use phpManufaktur\flexContent\Data\Content\TagType;

class cmsSearch
{
    protected static $config = null;
    protected $app = null;

    /**
     * Create a image for the search result
     *
     * @param unknown $image_path
     * @return string
     */
    protected function createImageForSearch($image_path)
    {
        $config_image = self::$config['search']['cms']['image'];
        if (!$config_image['enabled']) {
            // images for the search are disabled
            return '';
        }

        if (!$this->app['filesystem']->exists(FRAMEWORK_PATH.$image_path)) {
            // image does not exists
            return '';
        }

        // check if directory exists
        $target_path = CMS_MEDIA_PATH.'/cache/flexcontent/'.$config_image['max_width'].'x'.$config_image['max_height'].$image_path;
        if (!$this->app['filesystem']->exists($target_path)) {
            // create a resampled file
            $this->app['filesystem']->mkdir(dirname($target_path));

            $image_info = $this->app['image']->getImageInfo(FRAMEWORK_PATH.$image_path);

            $new_size = $this->app['image']->reCalculateImage($image_info['width'], $image_info['height'],
                $config_image['max_width'], $config_image['max_height']);

            $this->app['image']->resampleImage(FRAMEWORK_PATH.$image_path, $image_info['image_type'],
                $image_info['width'], $image_info['height'], $target_path, $new_size['width'], $new_size['height']);
        }
        // return the relative path to the image
        return substr($target_path, strlen(CMS_MEDIA_PATH));
    }

    /**
     * Controller for the search function within the CMS
     *
     * @param Application $app
     * @throws \Exception
     */
    public function controllerSearch(Application $app)
    {
        try {
            $this->app = $app;

            $Configuration = new Configuration($app);
            self::$config = $Configuration->getConfiguration();

            if (!self::$config['search']['cms']['enabled']) {
                // the search function is disabled
                return $app->json(array('search' => array('success' => false)));
            }

            $search = $app['request']->get('search');
            $cms = $app['request']->get('cms');

            // must get the locale from the page information
            $pageData = new Page($app);
            $language = $pageData->getPageLanguage($search['page']['id']);

            $target_link = substr($search['page']['url'], strlen(CMS_URL));

            if (strtolower($language) != strtolower($cms['locale'])) {
                // the page language does not match to the search language ...
                return $app->json(array('search' => array('success' => false)));
            }

            $CategoryTypeData = new CategoryType($app);
            if (false === ($category_ids = $CategoryTypeData->selectCategoryIDsByTargetLink($target_link, $language))) {
                // no category for this page ...
                return $app->json(array('search' => array('success' => false)));
            }

            $permanent_link_base_url = CMS_URL.str_ireplace('{language}', strtolower($language), self::$config['content']['permalink']['directory']);

            $ContentData = new Content($app);
            $TagTypeData = new TagType($app);

            $search_results = array();

            // show each tag only once!
            $tag_ids = array();

            // show each content only once!
            $content_ids = array();

            foreach ($category_ids as $category_id) {
                // loop through the categories and perform the search
                $category_info = $CategoryTypeData->select($category_id);

                // first search within the category itself
                if (false !== ($text = $CategoryTypeData->cmsSearch($category_id, $search['words'], ($search['match'] == 'any')))) {
                    $image_link = '';
                    if (!empty($category_info['category_image'])) {
                        $image_link = $this->createImageForSearch($category_info['category_image']);
                    }
                    if (self::$config['search']['category']['title']['prefix']['enabled']) {
                        // use a title prefix for the category!
                        $search_prefix = array('{prefix}','{title}');
                        $replace_prefix = array(
                            // we must tell the translator the language to use!
                            $app['translator']->trans(self::$config['search']['category']['title']['prefix']['prefix'],
                                array(), 'messages', strtolower($language)),
                            $category_info['category_name']
                        );
                        $title = str_replace($search_prefix, $replace_prefix,
                            self::$config['search']['category']['title']['prefix']['replacement']);
                    }
                    else {
                        $title = $category_info['category_name'];
                    }
                    $hit = array(
                        'text' => $text,
                        'success' => true,
                        'page' => array(
                            'title' => $title,
                            'url' => $permanent_link_base_url.'/category/'.$category_info['category_permalink']
                        ),
                        'image_link' => $image_link
                    );
                    $search_results[]['search'] = $hit;
                }

                // next search within the category contents
                if (false !== ($contents = $ContentData->cmsSearch($category_id, $search['words'], ($search['match'] == 'any'),
                    self::$config['search']['content']['status']))) {
                    foreach ($contents as $content) {
                        if (in_array($content['content_id'], $content_ids)) {
                            continue;
                        }
                        $content_ids[] = $content['content_id'];
                        $image_link = '';
                        if (!empty($content['teaser_image'])) {
                            $image_link = $this->createImageForSearch($content['teaser_image']);
                        }
                        // we need the user CMS account
                        $user_account = $app['account']->getUserCMSAccount($content['author_username']);
                        $hit = array(
                            'text' => $content['excerpt'],
                            'success' => true,
                            'page' => array(
                                'title' => $content['title'],
                                'description' => $content['description'],
                                'keywords' => $content['keywords'],
                                'modified_when' => strtotime($content['timestamp']),
                                'modified_by' => $user_account['user_id'],
                                'url' => $permanent_link_base_url.'/'.$content['permalink']
                            ),
                            'image_link' => $image_link
                        );
                        $search_results[]['search'] = $hit;
                    }
                }

                // next search within the tags which belong to the category
                if (false !== ($tag_types = $TagTypeData->cmsSearch($category_id, $search['words'], ($search['match'] == 'any'),
                    self::$config['search']['content']['status']))) {
                    foreach ($tag_types as $tag) {
                        if (in_array($tag['tag_id'], $tag_ids)) {
                            continue;
                        }
                        $tag_ids[] = $tag['tag_id'];

                        $image_link = '';
                        if (!empty($tag['tag_image'])) {
                            $image_link = $this->createImageForSearch($tag['tag_image']);
                        }

                        if (self::$config['search']['tag']['title']['prefix']['enabled']) {
                            // use a title prefix for the category!
                            $search_prefix = array('{prefix}','{title}');
                            $replace_prefix = array(
                                // we must tell the translator the language to use!
                                $app['translator']->trans(self::$config['search']['tag']['title']['prefix']['prefix'],
                                    array(), 'messages', strtolower($language)),
                                $tag['tag_name']
                            );
                            $title = str_replace($search_prefix, $replace_prefix,
                                self::$config['search']['tag']['title']['prefix']['replacement']);
                        }
                        else {
                            $title = $tag['tag_name'];
                        }

                        $hit = array(
                            'text' => $tag['excerpt'],
                            'success' => true,
                            'page' => array(
                                'title' => $title,
                                'url' => $permanent_link_base_url.'/buzzword/'.$tag['tag_permalink']
                            ),
                            'image_link' => $image_link
                        );
                        $search_results[]['search'] = $hit;
                    }
                }
            }

            if (empty($search_results)) {
                // no hits
                return $app->json(array('search' => array('success' => false)));
            }
            else {
 $app['monolog']->addDebug('RESULT', $search_results);
                return $app->json(array('search_results' => $search_results));
            }
        } catch (\Exception $e)  {
            // because the SearchFilter suppress all error messages we must report it at this point to get a info!
            $app['monolog']->addError($e->getMessage(), array($e->getFile(), $e->getLine()));
            throw new \Exception($e);
        }

    }
}
