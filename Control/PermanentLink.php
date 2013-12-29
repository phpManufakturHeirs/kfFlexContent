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

class PermanentLink
{
    protected $ContentData = null;
    protected $CategoryData = null;
    protected $PageData = null;
    protected $app = null;

    protected static $content_id = null;
    protected static $config = null;

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
        $this->PageData = new Page($app);
    }

    /**
     * Redirect to the target URL to show there the desired content
     *
     * @return string
     */
    protected function redirectToContentID()
    {
        if (false === ($content = $this->ContentData->select(self::$content_id))) {
            // flexContent ID does not exists
            $this->app['monolog']->addError('The flexContent ID '.self::$content_id." does not exists.", array(__METHOD__, __LINE__));
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

        if (!$this->PageData->existsCommandAtPageID('flexcontent', $page_id)) {
            // the page exists but does not contain the needed kitCommand
            $this->app['monolog']->addError('The CMS target URL does not contain the needed kitCommand!', array(__METHOD__, __LINE__));
            return $this->app['twig']->render($this->app['utils']->getTemplateFile(
                '@phpManufaktur/Basic/Template', 'kitcommand/bootstrap/noframe/alert.twig'),
                array(
                    'content' => $this->app['translator']->trans('The CMS target URL does not contain the needed kitCommand!'),
                    'type' => 'alert-danger'));
        }

        // create the target URL and set the needed parameters
        $target_url = CMS_URL.$target.'?'.http_build_query(array(
            'command' => 'flexcontent',
            'action' => 'view',
            'id' => self::$content_id,
            'set_header' => self::$content_id
        ));
        // redirect to the target URL
        return $this->app->redirect($target_url, 302);
    }

    /**
     * Controller to handle contents given by ID
     *
     * @param Application $app
     * @param integer $content_id
     */
    public function ControllerContentID(Application $app, $content_id)
    {
        $this->initialize($app);

        if (false === (self::$content_id = filter_var($content_id, FILTER_VALIDATE_INT))) {
            // this is not an integer - try to get the ID by the given string
            return $this->ControllerName($app, $content_id);
        }

        // handle the content ID
        return $this->redirectToContentID();
    }

    /**
     * Controller to handle named permanent links
     *
     * @param Application $app
     * @param string $name
     */
    public function ControllerName(Application $app, $name)
    {
        $this->initialize($app);

        if (false !== (self::$content_id = filter_var($name, FILTER_VALIDATE_INT))) {
            // this is an integer - try to get the content by the given ID
            return $this->ControllerContentID($app, self::$content_id);
        }

        if (false === (self::$content_id = $this->ContentData->selectContentIDbyPermaLink($name))) {
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

}

