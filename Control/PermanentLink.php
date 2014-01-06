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
use phpManufaktur\flexContent\Data\Content\Content;
use phpManufaktur\flexContent\Data\Content\Category;
use phpManufaktur\Basic\Data\CMS\Page;
use phpManufaktur\flexContent\Data\Content\CategoryType;
use phpManufaktur\flexContent\Data\Content\TagType;
use phpManufaktur\flexContent\Data\Content\Tag;

class PermanentLink
{
    protected $ContentData = null;
    protected $CategoryData = null;
    protected $CategoryTypeData = null;
    protected $TagData = null;
    protected $TagTypeData = null;
    protected $PageData = null;
    protected $app = null;

    protected static $content_id = null;
    protected static $language = null;
    protected static $config = null;
    protected static $category_id = null;
    protected static $tag_id = null;

    /**
     * Initialize the class
     *
     * @param Application $app
     */
    protected function initialize(Application $app)
    {
        $this->app = $app;

        $this->ContentData = new Content($app);

        $Config = new Configuration($app);
        self::$config = $Config->getConfiguration();

        $this->CategoryData = new Category($app);
        $this->CategoryTypeData = new CategoryType($app);
        $this->PageData = new Page($app);
        $this->TagData = new Tag($app);
        $this->TagTypeData = new TagType($app);
    }

    /**
     * Execute cURL to catch the CMS content into the permanent link
     *
     * @param string $url
     * @return mixed
     */
    protected function cURLexec($url)
    {
        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => 'kitFramework::flexContent',
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false
        );
        $ch = curl_init();
        curl_setopt_array($ch, $options);

        // set proxy if needed
        $this->app['utils']->setCURLproxy($ch);

        if (false === ($result = curl_exec($ch))) {
            // cURL error
            $error = 'cURL error: '.curl_error($ch);
            $this->app['monolog']->addError($error, array(__METHOD__, __LINE__));
            return $this->app['twig']->render($this->app['utils']->getTemplateFile(
                '@phpManufaktur/Basic/Template', 'kitcommand/bootstrap/noframe/alert.twig'),
                array(
                    'content' => $error,
                    'type' => 'alert-danger'));
        }
        if (!curl_errno($ch)) {
            $info = curl_getinfo($ch);
            if ($info['http_code'] > 308) {
                // bad request
                $error = 'Error - HTTP Status Code: '.$info['http_code'].' - '.$url;
                $this->app['monolog']->addError($error, array(__METHOD__, __LINE__));
                return $this->app['twig']->render($this->app['utils']->getTemplateFile(
                    '@phpManufaktur/Basic/Template', 'kitcommand/bootstrap/noframe/alert.twig'),
                    array(
                        'content' => $error,
                        'type' => 'alert-danger'));
            }
        }
        curl_close($ch);
        return $result;
    }

    /**
     * Redirect to the target URL to show there the desired content
     *
     * @return string
     */
    protected function redirectToContentID()
    {

        if (false === ($content = $this->ContentData->select(self::$content_id, self::$language))) {
            // flexContent ID does not exists
            $this->app['monolog']->addError('The flexContent ID '.self::$content_id." does not exists.",
                array(__METHOD__, __LINE__));
            return $this->app['twig']->render($this->app['utils']->getTemplateFile(
                '@phpManufaktur/Basic/Template', 'kitcommand/bootstrap/noframe/alert.twig'),
                array(
                    'content' => $this->app['translator']->trans('There is no content assigned to this pemanent link!'),
                    'type' => 'alert-danger'));
        }

        if (!empty($content['redirect_url'])) {
            // do not show content, redirect to another URL!
            return $this->app->redirect($content['redirect_url'], 302);
        }

        if (false === ($target = $this->CategoryData->selectTargetURLbyContentID(self::$content_id))) {
            // missing the target URL
            $this->app['monolog']->addError('Missing the target URL for flexContent ID '.self::$content_id, array(__METHOD__, __LINE__));
            return $this->app['twig']->render($this->app['utils']->getTemplateFile(
                '@phpManufaktur/Basic/Template', 'kitcommand/bootstrap/noframe/alert.twig'),
                array(
                    'content' => $this->app['translator']->trans('There is no target URL assigned to this pemanent link!'),
                    'type' => 'alert-danger'));
        }

        // get the CMS page link from the target link
        $link = substr($target, strlen($this->PageData->getPageDirectory()), (strlen($this->PageData->getPageExtension()) * -1));

        if (false === ($page_id = $this->PageData->getPageIDbyPageLink($link))) {
            // the page does not exists!
            $this->app['monolog']->addError('The CMS page for the page link '.$link.' does not exists!', array(__METHOD__, __LINE__));
            return $this->app['twig']->render($this->app['utils']->getTemplateFile(
                '@phpManufaktur/Basic/Template', 'kitcommand/bootstrap/noframe/alert.twig'),
                array(
                    'content' => $this->app['translator']->trans('The target URL assigned to this permanent link does not exists!'),
                    'type' => 'alert-danger'));
        }

        if ((false === ($lang_code = $this->PageData->getPageLanguage($page_id))) || (self::$language != strtolower($lang_code))) {
            // the page does not support the needed language!
            $error = 'The CMS target page does not support the needed language <strong>'.self::$language.'</strong> for this permanent link!';
            $this->app['monolog']->addError(strip_tags($error), array(__METHOD__, __LINE__, self::$content_id));
            return $this->app['twig']->render($this->app['utils']->getTemplateFile(
                '@phpManufaktur/Basic/Template', 'kitcommand/bootstrap/noframe/alert.twig'),
                array(
                    'content' => $error,
                    'type' => 'alert-danger'));
        }

        if (!$this->PageData->existsCommandAtPageID('flexcontent', $page_id)) {
            // the page exists but does not contain the needed kitCommand
            $this->app['monolog']->addError('The CMS target URL does not contain the needed kitCommand!', array(__METHOD__, __LINE__));
            return $this->app['twig']->render($this->app['utils']->getTemplateFile(
                '@phpManufaktur/Basic/Template', 'kitcommand/bootstrap/noframe/alert.twig'),
                array(
                    'content' => $this->app['translator']->trans('The CMS target URL does not contain the needed kitCommand!'),
                    'type' => 'alert-danger'));
        }

        // create the parameter array
        $parameter = array(
            'command' => 'flexcontent',
            'action' => 'view',
            'content_id' => self::$content_id,
            'set_header' => self::$content_id,
            'language' => strtolower(self::$language)
        );

        if (null !== ($highlight = $this->app['request']->query->get('highlight'))) {
            // add highlight search results
            $parameter['highlight'] = $highlight;
        }

        // create the target URL and set the needed parameters
        $target_url = CMS_URL.$target.'?'.http_build_query($parameter);

        return $this->cURLexec($target_url);
    }

    /**
     * Redirect to the target URL to show the category content
     *
     * @return string
     */
    protected function redirectToCategoryID()
    {
        if (false === ($category = $this->CategoryTypeData->select(self::$category_id, self::$language))) {
            // the category ID does not exists!
            $this->app['monolog']->addError('The flexContent category ID '.self::$category_id." does not exists.",
                array(__METHOD__, __LINE__));
            return $this->app['twig']->render($this->app['utils']->getTemplateFile(
                '@phpManufaktur/Basic/Template', 'kitcommand/bootstrap/noframe/alert.twig'),
                array(
                    'content' => $this->app['translator']->trans('There is no category assigned to this pemanent link!'),
                    'type' => 'alert-danger'));
        }

        // get the CMS page link from the target link
        $link = substr($category['target_url'], strlen($this->PageData->getPageDirectory()), (strlen($this->PageData->getPageExtension()) * -1));

        if (false === ($page_id = $this->PageData->getPageIDbyPageLink($link))) {
            // the page does not exists!
            $this->app['monolog']->addError('The CMS page for the page link '.$link.' does not exists!', array(__METHOD__, __LINE__));
            return $this->app['twig']->render($this->app['utils']->getTemplateFile(
                '@phpManufaktur/Basic/Template', 'kitcommand/bootstrap/noframe/alert.twig'),
                array(
                    'content' => $this->app['translator']->trans('The target URL assigned to this permanent link does not exists!'),
                    'type' => 'alert-danger'));
        }

        if ((false === ($lang_code = $this->PageData->getPageLanguage($page_id))) || (self::$language != strtolower($lang_code))) {
            // the page does not support the needed language!
            $error = 'The CMS target page does not support the needed language <strong>'.self::$language.'</strong> for this permanent link!';
            $this->app['monolog']->addError(strip_tags($error), array(__METHOD__, __LINE__, self::$content_id));
            return $this->app['twig']->render($this->app['utils']->getTemplateFile(
                '@phpManufaktur/Basic/Template', 'kitcommand/bootstrap/noframe/alert.twig'),
                array(
                    'content' => $error,
                    'type' => 'alert-danger'));
        }

        if (!$this->PageData->existsCommandAtPageID('flexcontent', $page_id)) {
            // the page exists but does not contain the needed kitCommand
            $this->app['monolog']->addError('The CMS target URL does not contain the needed kitCommand!', array(__METHOD__, __LINE__));
            return $this->app['twig']->render($this->app['utils']->getTemplateFile(
                '@phpManufaktur/Basic/Template', 'kitcommand/bootstrap/noframe/alert.twig'),
                array(
                    'content' => $this->app['translator']->trans('The CMS target URL does not contain the needed kitCommand!'),
                    'type' => 'alert-danger'));
        }

        // create the parameter array
        $parameter = array(
            'command' => 'flexcontent',
            'action' => 'category',
            'category_id' => self::$category_id,
            'content_id' => self::$content_id,
            'language' => strtolower(self::$language)
        );

        if (null !== ($highlight = $this->app['request']->query->get('highlight'))) {
            // add search results
            $parameter['highlight'] = $highlight;
        }

        // create the target URL and set the needed parameters
        $target_url = CMS_URL.$category['target_url'].'?'.http_build_query($parameter);

        return $this->cURLexec($target_url);
    }

    protected function redirectToTagID()
    {
        if (false === ($tag = $this->TagTypeData->select(self::$tag_id, self::$language))) {
            // the TAG ID does not exists!
            $this->app['monolog']->addError('The flexContent tag ID '.self::$tag_id." does not exists.",
                array(__METHOD__, __LINE__));
            return $this->app['twig']->render($this->app['utils']->getTemplateFile(
                '@phpManufaktur/Basic/Template', 'kitcommand/bootstrap/noframe/alert.twig'),
                array(
                    'content' => $this->app['translator']->trans('There is no tag assigned to this pemanent link!'),
                    'type' => 'alert-danger'));
        }

        // TAGs have no own target URL - we need a category to get one!
        if (self::$category_id > 0) {
            // get the URL from the submitted category ID
            if (false === ($target_url = $this->CategoryData->selectTargetURLbyCategoryID(self::$category_id))) {
                // this TAG ID is not in use ...
                $this->app['monolog']->addDebug('The tag '.$tag['tag_name'].' is not assigned to any content!', array(__METHOD__, __LINE__));
                return $this->app['twig']->render($this->app['utils']->getTemplateFile(
                    '@phpManufaktur/Basic/Template', 'kitcommand/bootstrap/noframe/alert.twig'),
                    array(
                        'content' => $this->app['translator']->trans('The tag %name% is not assigned to any content!',
                            array('%name%' => $tag['tag_name'])),
                        'type' => 'alert-danger'));
            }
        }
        // try to get the category and the assigned URL ...
        elseif (false === ($target_url = $this->TagData->selectTargetURLbyTagID(self::$tag_id, self::$category_id, self::$content_id))) {
            // this TAG ID is not in use ...
            $this->app['monolog']->addDebug('The tag '.$tag['tag_name'].' is not assigned to any content!', array(__METHOD__, __LINE__));
            return $this->app['twig']->render($this->app['utils']->getTemplateFile(
                '@phpManufaktur/Basic/Template', 'kitcommand/bootstrap/noframe/alert.twig'),
                array(
                    'content' => $this->app['translator']->trans('The tag %name% is not assigned to any content!',
                        array('%name%' => $tag['tag_name'])),
                    'type' => 'alert-danger'));
        }

        // get the CMS page link from the target link
        $link = substr($target_url, strlen($this->PageData->getPageDirectory()), (strlen($this->PageData->getPageExtension()) * -1));

        if (false === ($page_id = $this->PageData->getPageIDbyPageLink($link))) {
            // the page does not exists!
            $this->app['monolog']->addError('The CMS page for the page link '.$link.' does not exists!', array(__METHOD__, __LINE__));
            return $this->app['twig']->render($this->app['utils']->getTemplateFile(
                '@phpManufaktur/Basic/Template', 'kitcommand/bootstrap/noframe/alert.twig'),
                array(
                    'content' => $this->app['translator']->trans('The target URL assigned to this permanent link does not exists!'),
                    'type' => 'alert-danger'));
        }

        if ((false === ($lang_code = $this->PageData->getPageLanguage($page_id))) || (self::$language != strtolower($lang_code))) {
            // the page does not support the needed language!
            $error = 'The CMS target page does not support the needed language <strong>'.self::$language.'</strong> for this permanent link!';
            $this->app['monolog']->addError(strip_tags($error), array(__METHOD__, __LINE__, self::$content_id));
            return $this->app['twig']->render($this->app['utils']->getTemplateFile(
                '@phpManufaktur/Basic/Template', 'kitcommand/bootstrap/noframe/alert.twig'),
                array(
                    'content' => $error,
                    'type' => 'alert-danger'));
        }

        if (!$this->PageData->existsCommandAtPageID('flexcontent', $page_id)) {
            // the page exists but does not contain the needed kitCommand
            $this->app['monolog']->addError('The CMS target URL does not contain the needed kitCommand!', array(__METHOD__, __LINE__));
            return $this->app['twig']->render($this->app['utils']->getTemplateFile(
                '@phpManufaktur/Basic/Template', 'kitcommand/bootstrap/noframe/alert.twig'),
                array(
                    'content' => $this->app['translator']->trans('The CMS target URL does not contain the needed kitCommand!'),
                    'type' => 'alert-danger'));
        }

        $parameter = array(
            'command' => 'flexcontent',
            'action' => 'tag',
            'category_id' => self::$category_id,
            'content_id' => self::$content_id,
            'tag_id' => self::$tag_id,
            'language' => strtolower(self::$language)
        );

        if (null !== ($highlight = $this->app['request']->query->get('highlight'))) {
            // add search results
            $parameter['highlight'] = $highlight;
        }

        // create the target URL and set the needed parameters
        $target_url = CMS_URL.$target_url.'?'.http_build_query($parameter);

        return $this->cURLexec($target_url);
    }

    /**
     * Controller to handle permanent links to content names
     *
     * @param Application $app
     * @param string $name
     */
    public function ControllerContentName(Application $app, $name, $language)
    {
        $this->initialize($app);
        self::$language = $language;

        if (false !== (self::$content_id = filter_var($name, FILTER_VALIDATE_INT))) {
            // this is an integer - get the content by the given ID
            return $this->redirectToContentID();
        }

        if (false === (self::$content_id = $this->ContentData->selectContentIDbyPermaLink($name, self::$language))) {
            // this permalink does not exists
            $this->app['monolog']->addError('The permalink '.$name.' does not exists!', array(__METHOD__, __LINE__));
            return $this->app['twig']->render($this->app['utils']->getTemplateFile(
                '@phpManufaktur/Basic/Template', 'kitcommand/bootstrap/noframe/alert.twig'),
                array(
                    'content' => $this->app['translator']->trans('The permalink <b>%permalink%</b> does not exists!',
                        array('%permalink%' => $name)),
                    'type' => 'alert-danger'
                ));
        }

        // handle the content ID
        return $this->redirectToContentID();
    }

    /**
     * Controller to handle permanent links to categories
     *
     * @param Application $app
     * @param string $name
     * @param string $language
     * @return string
     */
    public function ControllerCategoryName(Application $app, $name, $language)
    {
        $this->initialize($app);
        self::$language = $language;

        // get the content ID from parameter
        self::$content_id = $app['request']->query->get('i', -1);

        if (false !== (self::$category_id = filter_var($name, FILTER_VALIDATE_INT))) {
            // this is an integer - get the category by the given ID
            return $this->redirectToCategoryID();
        }

        if (false === (self::$category_id = $this->CategoryTypeData->selectCategoryIDbyPermaLink($name, self::$language))) {
            // this permalink does not exists
            $this->app['monolog']->addError('The permalink /category/'.$name.' does not exists!', array(__METHOD__, __LINE__));
            return $this->app['twig']->render($this->app['utils']->getTemplateFile(
                '@phpManufaktur/Basic/Template', 'kitcommand/bootstrap/noframe/alert.twig'),
                array(
                    'content' => $this->app['translator']->trans('The permalink <b>/category/%permalink%</b> does not exists!',
                        array('%permalink%' => $name)),
                    'type' => 'alert-danger'
                ));
        }

        return $this->redirectToCategoryID();
    }

    /**
     * Controller to handle permanent links to tags
     *
     * @param Application $app
     * @param string $name
     * @param string $language
     * @return string
     */
    public function ControllerTagName(Application $app, $name, $language)
    {
        $this->initialize($app);
        self::$language = $language;

        // get the category ID from parameter
        self::$category_id = $app['request']->query->get('c', -1);
        // get the content ID from parameter
        self::$content_id = $app['request']->query->get('i', -1);

        if (false !== (self::$tag_id = filter_var($name, FILTER_VALIDATE_INT))) {
            // this is an integer - get the tag by the given ID
            return $this->redirectToTagID();
        }

        if (false === (self::$tag_id = $this->TagTypeData->selectTagIDbyPermaLink($name, self::$language))) {
            // this permalink does not exists
            $this->app['monolog']->addError('The permalink /tag/'.$name.' does not exists!', array(__METHOD__, __LINE__));
            return $this->app['twig']->render($this->app['utils']->getTemplateFile(
                '@phpManufaktur/Basic/Template', 'kitcommand/bootstrap/noframe/alert.twig'),
                array(
                    'content' => $this->app['translator']->trans('The permalink <b>/tag/%permalink%</b> does not exists!',
                        array('%permalink%' => $name)),
                    'type' => 'alert-danger'
                ));
        }

        return $this->redirectToTagID();
    }

}

