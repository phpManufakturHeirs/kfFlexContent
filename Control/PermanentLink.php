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
use phpManufaktur\flexContent\Control\RSS\RSSChannel as RSSChannelControl;
use phpManufaktur\flexContent\Data\Content\RSSChannel as RSSChannelData;
use phpManufaktur\flexContent\Data\Content\RSSChannelCounter;
use phpManufaktur\flexContent\Data\Content\RSSViewCounter;
use Symfony\Component\HttpFoundation\Response;
use phpManufaktur\Basic\Data\kitCommandParameter;
use phpManufaktur\Basic\Data\CMS\Users;
use phpManufaktur\flexContent\Control\Command\Tools;

class PermanentLink
{
    protected $ContentData = null;
    protected $CategoryData = null;
    protected $CategoryTypeData = null;
    protected $TagData = null;
    protected $TagTypeData = null;
    protected $PageData = null;
    protected $RSSChannelControl = null;
    protected $RSSChannelData = null;
    protected $RSSChannelCounter = null;
    protected $app = null;
    protected $Tools = null;

    protected static $content_id = null;
    protected static $language = null;
    protected static $config = null;
    protected static $category_id = null;
    protected static $tag_id = null;
    protected static $rss_channel_id = null;

    protected static $ignore_parameters = array('searchresult','sstring','pid');

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
        $this->RSSChannelControl = new RSSChannelControl($app);
        $this->RSSChannelData = new RSSChannelData($app);
        $this->RSSChannelCounter = new RSSChannelCounter($app);
        $this->Tools = new Tools($app);
    }

    /**
     * Execute cURL to catch the CMS content into the permanent link
     *
     * @param string $url
     * @return mixed
     */
    protected function cURLexec($url, $page_id)
    {
        // init cURL
        $ch = curl_init();

        // set the general cURL options
        $options = array(
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => 'kitFramework::flexContent',
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false
        );

        if (is_null($this->app['session']->get('FLEXCONTENT_COOKIE_FILE')) ||
            !$this->app['filesystem']->exists($this->app['session']->get('FLEXCONTENT_COOKIE_FILE'))) {
            // this is the first call of this cURL session, create a cookie file
            $this->app['session']->set('FLEXCONTENT_COOKIE_FILE', FRAMEWORK_TEMP_PATH.'/session/'.uniqid('flexcontent_'));
            $options[CURLOPT_COOKIEJAR] = $this->app['session']->get('FLEXCONTENT_COOKIE_FILE');
        }
        else {
            // load the existing cookie file
            $options[CURLOPT_COOKIEFILE] = $this->app['session']->get('FLEXCONTENT_COOKIE_FILE');
        }

        // get the visibility of the target page
        $visibility = $this->PageData->getPageVisibilityByPageID($page_id);
        if ($visibility == 'none') {
            // page can not be shown!
            $error = 'The visibility of the requested page is "none", can not show the content!';
            $this->app['monolog']->addError($error, array(__METHOD__, __LINE__));
            return $this->app['twig']->render($this->app['utils']->getTemplateFile(
                '@phpManufaktur/Basic/Template', 'kitcommand/bootstrap/noframe/alert.twig'),
                array(
                    'content' => $this->app['translator']->trans($error),
                    'type' => 'alert-danger'));
        }
        elseif (in_array($visibility, array('registered', 'private'))) {
            // user must be authenticated!
            if (is_null($this->app['session']->get('CMS_USERNAME')) && !is_null($this->app['request']->query->get('pid'))) {
                $kitCommandParameter = new kitCommandParameter($this->app);
                if (false !== ($parameter = $kitCommandParameter->selectParameter($this->app['request']->query->get('pid')))) {
                    if (isset($parameter['cms']['user']['name']) && !empty($parameter['cms']['user']['name'])) {
                        // set the session username from the PID
                        $Users = new Users($this->app);
                        if (false !== ($user = $Users->selectUser($parameter['cms']['user']['name']))) {
                            // authenticate the user
                            $options[CURLOPT_URL] = MANUFAKTUR_URL.'/Basic/Control/CMS/Authenticate.php';
                            $options[CURLOPT_POST] = true;
                            $options[CURLOPT_POSTFIELDS] = array('username' => $user['username'], 'password' => $user['password']);

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
                            if ($result == $user['username']) {
                                $this->app['session']->set('CMS_USERNAME', $parameter['cms']['user']['name']);
                            }
                        }
                    }
                }
            }

            if (is_null($this->app['session']->get('CMS_USERNAME'))) {
                // user is not logged in
                $options[CURLOPT_URL] = CMS_URL.'/account/login.php';
                $options[CURLOPT_FOLLOWLOCATION] = true;
                // follow the location to show the content
                $options[CURLOPT_POST] = true;
                $options[CURLOPT_POSTFIELDS] = array('redirect' => $url);

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
                curl_close($ch);
                return $result;
            }
            else {
                // authenticate the user by the saved name in session
                $Users = new Users($this->app);
                if (false !== ($user = $Users->selectUser($this->app['session']->get('CMS_USERNAME')))) {
                    // authenticate the user
                    $options[CURLOPT_URL] = MANUFAKTUR_URL.'/Basic/Control/CMS/Authenticate.php';
                    $options[CURLOPT_POST] = true;
                    $options[CURLOPT_POSTFIELDS] = array('username' => $user['username'], 'password' => $user['password']);

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
                }
            }
        }

        // add the URL to the options
        $options[CURLOPT_URL] = $url;

        curl_setopt_array($ch, $options);

        $cms_host = strtolower(parse_url(CMS_URL, PHP_URL_HOST));
        $url_host = strtolower(parse_url($url, PHP_URL_HOST));
        if ($cms_host !== $url_host) {
            // set proxy if needed
            $this->app['utils']->setCURLproxy($ch);
        }

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
            if ($info['http_code'] > 299) {
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
            'language' => strtolower(self::$language),
            'robots' => self::$config['kitcommand']['permalink']['content']['robots'],
            'canonical' => $this->Tools->getPermalinkBaseURL(self::$language).'/'.$content['permalink']
        );

        if (self::$config['search']['result']['highlight'] &&
            (null !== ($searchresult = $this->app['request']->query->get('searchresult'))) &&
            (null !== ($sstring = $this->app['request']->query->get('sstring')))) {
            // create a highlight array
            $highlight = array();
            if ($searchresult == 1) {
                if (false !== strpos($sstring, '+')) {
                    $words = explode('+', $sstring);
                    foreach ($words as $word) {
                        $highlight[] = $word;
                    }
                }
                else {
                    $highlight[] = $sstring;
                }
            }
            else {
                $highlight[] = str_replace('_', ' ', $sstring);
            }
            $parameter['highlight'] = $highlight;
        }

        $gets = $this->app['request']->query->all();
        foreach ($gets as $key => $value) {
            if (!key_exists($key, $parameter) && !in_array($key, self::$ignore_parameters)) {
                // pass all other parameters to the target page
                $parameter[$key] = $value;
            }
        }

        if (self::$config['rss']['tracking']['enabled']) {
            $RSSViewCounter = new RSSViewCounter($this->app);
            $RSSViewCounter->cleanup(self::$content_id);
            if (isset($parameter['ref']) && ($parameter['ref'] == 'rss')) {
                $RSSViewCounter->trackRemoteAddress(self::$content_id);
            }
        }

        // create the target URL and set the needed parameters
        $target_url = CMS_URL.$target.'?'.http_build_query($parameter, '', '&');

        return $this->cURLexec($target_url, $page_id);
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
            'language' => strtolower(self::$language),
            'robots' => self::$config['kitcommand']['permalink']['category']['robots'],
            'canonical' => $this->Tools->getPermalinkBaseURL(self::$language).'/category/'.$category['category_permalink']
        );

        if (self::$config['search']['result']['highlight'] &&
            (null !== ($searchresult = $this->app['request']->query->get('searchresult'))) &&
            (null !== ($sstring = $this->app['request']->query->get('sstring')))) {
            // create a highlight array
            $highlight = array();
            if ($searchresult == 1) {
                if (false !== strpos($sstring, '+')) {
                    $words = explode('+', $sstring);
                    foreach ($words as $word) {
                        $highlight[] = $word;
                    }
                }
                else {
                    $highlight[] = $sstring;
                }
            }
            else {
                $highlight[] = str_replace('_', ' ', $sstring);
            }
            $parameter['highlight'] = $highlight;
        }

        $gets = $this->app['request']->query->all();
        foreach ($gets as $key => $value) {
            if (!key_exists($key, $parameter) && !in_array($key, self::$ignore_parameters)) {
                // pass all other parameters to the target page
                $parameter[$key] = $value;
            }
        }

        // create the target URL and set the needed parameters
        $target_url = CMS_URL.$category['target_url'].'?'.http_build_query($parameter, '', '&');

        return $this->cURLexec($target_url, $page_id);
    }

    /**
     * Redirect to the target URL to show the FAQ content
     *
     * @return string
     */
    protected function redirectToFAQID()
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
            'action' => 'faq',
            'category_id' => self::$category_id,
            'language' => strtolower(self::$language),
            'robots' => self::$config['kitcommand']['permalink']['faq']['robots'],
            'canonical' => $this->Tools->getPermalinkBaseURL(self::$language).'/faq/'.$category['category_permalink']
        );

        if (self::$config['search']['result']['highlight'] &&
            (null !== ($searchresult = $this->app['request']->query->get('searchresult'))) &&
            (null !== ($sstring = $this->app['request']->query->get('sstring')))) {
            // create a highlight array
            $highlight = array();
            if ($searchresult == 1) {
                if (false !== strpos($sstring, '+')) {
                    $words = explode('+', $sstring);
                    foreach ($words as $word) {
                        $highlight[] = $word;
                    }
                }
                else {
                    $highlight[] = $sstring;
                }
            }
            else {
                $highlight[] = str_replace('_', ' ', $sstring);
            }
            $parameter['highlight'] = $highlight;
        }

        $gets = $this->app['request']->query->all();
        foreach ($gets as $key => $value) {
            if (!key_exists($key, $parameter) && !in_array($key, self::$ignore_parameters)) {
                // pass all other parameters to the target page
                $parameter[$key] = $value;
            }
        }

        // create the target URL and set the needed parameters
        $target_url = CMS_URL.$category['target_url'].'?'.http_build_query($parameter, '', '&');

        return $this->cURLexec($target_url, $page_id);
    }


    /**
     * Redirect to the target URL to show the associated FAQ
     *
     * @return string
     */
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
            'language' => strtolower(self::$language),
            'robots' => self::$config['kitcommand']['permalink']['tag']['robots'],
            'canonical' => $this->Tools->getPermalinkBaseURL(self::$language).'/buzzword/'.$tag['tag_permalink']
        );

        if (null !== ($highlight = $this->app['request']->query->get('highlight'))) {
            // add search results
            $parameter['highlight'] = $highlight;
        }

        // create the target URL and set the needed parameters
        $target_url = CMS_URL.$target_url.'?'.http_build_query($parameter, '', '&');

        return $this->cURLexec($target_url, $page_id);
    }

    /**
     * Return the XML data for the requested RSS Channel
     *
     * @return string
     */
    protected function promptRSSChannel()
    {
        if (false === ($channel = $this->RSSChannelData->select(self::$rss_channel_id))) {
            // the RSS Channel does not exists!
            $this->app['monolog']->addError('The RSS Channel record with the ID '.self::$rss_channel_id.' does not exists!',
                array(__METHOD__, __LINE__));
            return $this->app['twig']->render($this->app['utils']->getTemplateFile(
                '@phpManufaktur/Basic/Template', 'kitcommand/bootstrap/noframe/alert.twig'),
                array(
                    'content' => $this->app['translator']->trans('The RSS Channel record with the ID %id% does not exists!',
                        array('%id%' => self::$rss_channel_id)),
                    'type' => 'alert-danger'));
        }

        if ($channel['status'] != 'ACTIVE') {
            return $this->app['twig']->render($this->app['utils']->getTemplateFile(
                '@phpManufaktur/Basic/Template', 'kitcommand/bootstrap/noframe/alert.twig'),
                array(
                    'content' => $this->app['translator']->trans('Sorry, but the RSS Channel %title% is currently not available!',
                        array('%title%' => $channel['channel_title'])),
                    'type' => 'alert-danger'));
        }

        if (false === ($xml = $this->RSSChannelControl->getRSSChannelXML(self::$rss_channel_id))) {
            return $this->app['twig']->render($this->app['utils']->getTemplateFile(
                '@phpManufaktur/Basic/Template', 'kitcommand/bootstrap/noframe/alert.twig'),
                array(
                    'content' => $this->app['translator']->trans('Sorry, but the RSS Channel %title% does not contain any feeds!',
                        array('%title%' => $channel['channel_title'])),
                    'type' => 'alert-danger'));
        }

        if (self::$config['rss']['tracking']['enabled']) {
            // track this RSS Channel call
            $this->RSSChannelCounter->trackRemoteAddress(self::$rss_channel_id);
            // cleanup the counter table if needed
            $this->RSSChannelCounter->cleanup(self::$rss_channel_id);
        }

        return new Response($xml, 200);
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
     * Controller to handle permanent links to FAQs
     *
     * @param Application $app
     * @param string $name
     * @param string $language
     * @return string
     */
    public function ControllerFAQName(Application $app, $name, $language)
    {
        $this->initialize($app);
        self::$language = $language;

        if (false !== (self::$category_id = filter_var($name, FILTER_VALIDATE_INT))) {
            // this is an integer - get the category by the given ID
            return $this->redirectToFAQID();
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

        return $this->redirectToFAQID();
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

        if (false !== (self::$tag_id = filter_var($name, FILTER_VALIDATE_INT))) {
            // this is an integer - get the tag by the given ID
            return $this->redirectToTagID();
        }

        if (false === (self::$tag_id = $this->TagTypeData->selectTagIDbyPermaLink($name, self::$language))) {
            // this permalink does not exists
            $this->app['monolog']->addError('The permalink /buzzword/'.$name.' does not exists!', array(__METHOD__, __LINE__));
            return $this->app['twig']->render($this->app['utils']->getTemplateFile(
                '@phpManufaktur/Basic/Template', 'kitcommand/bootstrap/noframe/alert.twig'),
                array(
                    'content' => $this->app['translator']->trans('The permalink <b>/buzzword/%permalink%</b> does not exists!',
                        array('%permalink%' => $name)),
                    'type' => 'alert-danger'
                ));
        }

        return $this->redirectToTagID();
    }

    /**
     * Controller to handle the RSS Channel requests
     *
     * @param Application $app
     * @param string $channel
     * @param string $language
     */
    public function ControllerRSSChannel(Application $app, $channel, $language)
    {
        $this->initialize($app);
        self::$language = $language;

        if (false !== (self::$rss_channel_id = filter_var($channel, FILTER_VALIDATE_INT))) {
            // this is an integer - get the rss channel by the given ID
            return $this->promptRSSChannel();
        }

        if (false === (self::$rss_channel_id = $this->RSSChannelData->selectChannelIDbyChannelLink($channel, $language))) {
            // this channel link does not exists
            $this->app['monolog']->addError('The RSS Channel /'.$language.'/rss/'.$channel.' does not exists or is not active!', array(__METHOD__, __LINE__));
            return $this->app['twig']->render($this->app['utils']->getTemplateFile(
                '@phpManufaktur/Basic/Template', 'kitcommand/bootstrap/noframe/alert.twig'),
                array(
                    'content' => $this->app['translator']->trans('The RSS Channel <b>/%language%/rss/%permalink%</b> does not exists or is not active!',
                        array('%permalink%' => $channel, '%language%' => $language)),
                    'type' => 'alert-danger'
                ));
        }
        return $this->promptRSSChannel();
    }

}

