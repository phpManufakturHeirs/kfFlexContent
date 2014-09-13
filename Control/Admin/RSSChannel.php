<?php

/**
 * flexContent
*
* @author Team phpManufaktur <team@phpmanufaktur.de>
* @link https://kit2.phpmanufaktur.de/flexContent
* @copyright 2014 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
* @license MIT License (MIT) http://www.opensource.org/licenses/MIT
*/

namespace phpManufaktur\flexContent\Control\Admin;

use Silex\Application;
use phpManufaktur\flexContent\Data\Content\RSSChannel as RSSChannelData;
use phpManufaktur\flexContent\Data\Content\CategoryType as CategoryTypeData;
use phpManufaktur\flexContent\Control\Command\Tools;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class RSSChannel extends Admin
{
    protected static $route = null;
    protected static $columns = null;
    protected static $order_by = null;
    protected static $order_direction = null;
    protected static $status = null;
    protected static $language = null;
    protected static $channel_id = null;

    protected $RSSChannelData = null;
    protected $CategoryTypeData = null;
    protected $Tools = null;

    /**
     * (non-PHPdoc)
     * @see \phpManufaktur\flexContent\Control\Admin\Admin::initialize()
     */
    protected function initialize(Application $app)
    {
        parent::initialize($app);

        $this->RSSChannelData = new RSSChannelData($app);
        $this->CategoryTypeData = new CategoryTypeData($app);
        $this->Tools = new Tools($app);

        self::$channel_id = -1;

        try {
            // search for the config file in the template directory
            $cfg_file = $this->app['utils']->getTemplateFile('@phpManufaktur/flexContent/Template', 'admin/rss.channel.list.json', '', true);
            $cfg = $this->app['utils']->readJSON($cfg_file);
            // get the columns to show in the list
            self::$columns = isset($cfg['columns']) ? $cfg['columns'] : $this->RSSChannel->getColumns();
            self::$order_by = isset($cfg['list']['order']['by']) ? $cfg['list']['order']['by'] : array('channel_title');
            self::$order_direction = isset($cfg['list']['order']['direction']) ? $cfg['list']['order']['direction'] : 'ASC';
            self::$status = isset($cfg['list']['status']) ? $cfg['list']['status'] : array('ACTIVE','LOCKED');
        } catch (\Exception $e) {
            // the config file does not exists - use all available columns
            self::$columns = $this->RSSChannel->getColumns();
            self::$order_by = array('channel_title');
            self::$order_direction = 'ASC';
            self::$status = array('ACTIVE','LOCKED');
        }
        self::$route =  array(
            'edit' => '/flexcontent/editor/rss/channel/edit/{channel_id}?usage='.self::$usage,
            'create' => '/flexcontent/editor/rss/channel/edit?usage='.self::$usage,
            'edit_category' => '/flexcontent/editor/category/edit/id/{category_id}?usage='.self::$usage,
            'list' => '/flexcontent/editor/rss/channel/list?order={order}&direction={direction}&usage='.self::$usage
        );

        self::$language = $this->app['request']->get('form[language]', self::$config['content']['language']['default'], true);
    }

    /**
     * Controller to show the list of RSS Channels
     *
     * @param Application $app
     * @return string dialog
     */
    public function ControllerChannelList(Application $app)
    {
        $this->initialize($app);

        $order_by = explode(',', $app['request']->get('order', implode(',', self::$order_by)));
        $order_direction = $app['request']->get('direction', self::$order_direction);

        $channels = $this->RSSChannelData->selectList(self::$columns, $order_by, $order_direction, self::$status);

        return $this->app['twig']->render($this->app['utils']->getTemplateFile(
            '@phpManufaktur/flexContent/Template', 'admin/rss.channel.list.twig'),
            array(
                'usage' => self::$usage,
                'toolbar' => $this->getToolbar('rss'),
                'alert' => $this->getAlert(),
                'channels' => $channels,
                'columns' => self::$columns,
                'route' => self::$route,
                'order_by' => $order_by,
                'order_direction' => strtolower($order_direction)
            ));
    }

    /**
     * Create the form to select the language desired to the flexContent
     *
     * @param array $data
     */
    protected function getLanguageForm($data=array())
    {
        $languages = array();
        foreach (self::$config['content']['language']['support'] as $language) {
            $languages[$language['code']] = $language['name'];
        }

        return $this->app['form.factory']->createBuilder('form')
        ->add('language', 'choice', array(
            'choices' => $languages,
            'empty_value' => '- please select -',
            'expanded' => false,
            'required' => self::$config['content']['field']['language']['required'],
            'data' => isset($data['language']) ? $data['language'] : self::$language,
        ))
        ->getForm();
    }

    /**
     * Select a language for the flexContent
     *
     * @return string dialog
     */
    protected function selectLanguage()
    {
        $form = $this->getLanguageForm();

        $this->setAlert('Please select the language for the new RSS Channel.');

        return $this->app['twig']->render($this->app['utils']->getTemplateFile(
            '@phpManufaktur/flexContent/Template', 'admin/select.language.twig'),
            array(
                'usage' => self::$usage,
                'toolbar' => $this->getToolbar('edit'),
                'alert' => $this->getAlert(),
                'form' => $form->createView(),
                'config' => self::$config,
                'action' => '/flexcontent/editor/rss/channel/language/check'
            ));
    }

    /**
     * Controller to check the selected language and show the flexContent dialog
     *
     * @param Application $app
     */
    public function ControllerLanguageCheck(Application $app)
    {
        $this->initialize($app);

        // get the form
        $form = $this->getLanguageForm();
        // get the requested data
        $form->bind($this->app['request']);

        if ($form->isValid()) {
            // the form is valid
            $data = $form->getData();
            self::$language = $data['language'];
        }
        else {
            // general error (timeout, CSFR ...)
            $this->setAlert('The form is not valid, please check your input and try again!', array(),
                self::ALERT_TYPE_DANGER, true, array('form_errors' => $form->getErrorsAsString(),
                    'method' => __METHOD__, 'line' => __LINE__));
        }

        $form = $this->getRSSChannelForm($data);
        return $this->renderRSSChannelForm($form);
    }

    /**
     * Build the form to create or edit a RSS Channel
     *
     * @param array $data RSS Channel
     */
    protected function getRSSChannelForm($data=array())
    {
        if (isset($data['language'])) {
            // set the language property from the category type data
            self::$language = $data['language'];
        }

        $categories = $this->CategoryTypeData->getListForSelect(self::$language);
        if (empty($categories)) {
            $this->setAlert('No category available for the language %language%, please create a category first!',
                array('%language%' => $this->app['translator']->trans(self::$language)), self::ALERT_TYPE_WARNING);
        }

        $form = $this->app['form.factory']->createBuilder('form')
        ->add('channel_id', 'hidden', array(
            'data' => isset($data['channel_id']) ? $data['channel_id'] : -1
        ))
        ->add('language', 'hidden', array(
            'data' => self::$language
        ))
        ->add('channel_image', 'hidden', array(
            'data' => isset($data['channel_image']) ? $data['channel_image'] : ''
        ))
        ->add('channel_limit', 'hidden', array(
            'data' => isset($data['channel_limit']) ? $data['channel_limit'] : self::$config['rss']['channel']['limit']
        ))
        ->add('channel_title', 'text', array(
            'data' => isset($data['channel_title']) ? $data['channel_title'] : '',
            'required' => true,
            'attr' => array(
                'help' => $this->app['translator']->trans('A title for this RSS Channel')
            )
        ))
        ->add('channel_description', 'textarea', array(
            'data' => isset($data['channel_description']) ? $data['channel_description'] : '',
            'required' => true,
            'attr' => array(
                'help' => $this->app['translator']->trans('A brief description of this RSS Channel')
            )
        ))
        ->add('channel_link', 'text', array(
            'data' => isset($data['channel_link']) ? $data['channel_link'] : '',
            'required' => true,
            'attr' => array(
                'prefix' => $this->Tools->getRSSPermalinkBaseURL(self::$language).'/'
            )
        ))
        ->add('status', 'choice', array(
            'choices' => $this->RSSChannelData->getStatusTypeValuesForForm(),
            'empty_value' => '- please select -',
            'expanded' => false,
            'required' => true,
            'data' => isset($data['status']) ? $data['status'] : 'LOCKED'
        ))
        ->add('content_categories', 'choice', array(
            'choices' => $categories,
            'empty_value' => '- please select -',
            'required' => true,
            'multiple' => true,
            'expanded' => true,
            'data' => isset($data['content_categories']) ? is_array($data['content_categories']) ? $data['content_categories'] : explode(',', $data['content_categories']) : null,
            'attr' => array(
                'help' => $this->app['translator']->trans('Select the categories which are assigned to this RSS Channel')
            )
        ))
        ->add('channel_category', 'text', array(
            'data' => isset($data['channel_category']) ? $data['channel_category'] : '',
            'required' => false,
            'attr' => array(
                'help' => $this->app['translator']->trans('If you are providing multiple RSS Channels you can also define a category')
            )
        ))
        ->add('channel_copyright', 'text', array(
            'data' => isset($data['channel_copyright']) ? $data['channel_copyright'] : '',
            'required' => false,
            'attr' => array(
                'help' => $this->app['translator']->trans('You can specify a copyright hint for the RSS Channel')
            )
        ))
        ->add('channel_webmaster', 'email', array(
            'data' => isset($data['channel_webmaster']) ? $data['channel_webmaster'] : '',
            'required' => false,
            'attr' => array(
                'help' => $this->app['translator']->trans('You can specify the email address of the webmaster for this RSS Channel')
            )
        ))
        ;
        return $form->getForm();
    }

    /**
     * Check the submitted form data and create or update a record
     *
     * @param array reference $data
     * @return boolean
     */
    protected function checkRSSChannelForm(&$data=array())
    {
        // get the form
        $form = $this->getRSSChannelForm();
        // get the requested data
        $form->bind($this->app['request']);

        if ($form->isValid()) {
            // the form is valid
            $channel = $form->getData();
            $data = $channel;

            self::$channel_id = $channel['channel_id'];

            if (empty($channel['channel_title'])) {
                $this->setAlert('Please type in a title for the RSS Channel.', array(), self::ALERT_TYPE_WARNING);
                return false;
            }

            if (empty($channel['channel_description'])) {
                $this->setAlert('Please type in a brief description for the RSS Channel!', array(), self::ALERT_TYPE_WARNING);
                return false;
            }

            // check permalink
            if (empty($channel['channel_link'])) {
                $category['channel_link'] = $channel['channel_title'];
            }
            $permalink = $this->app['utils']->sanitizeLink($channel['channel_link']);

            if (self::$channel_id < 1 && $this->RSSChannelData->existsChannelLink($permalink, $channel['language'])) {
                // this PermaLink already exists!
                $this->setAlert('The permalink %permalink% is already in use, please select another one!',
                    array('%permalink%' => $permalink), self::ALERT_TYPE_WARNING);
                return false;
            }
            elseif ((self::$channel_id > 0) &&
                (false !== ($used_by = $this->RSSChannelData->selectChannelIDbyChannelLink($permalink, $channel['language']))) &&
                ($used_by != self::$channel_id)) {
                $this->setAlert('The Channel Link %channel_link% is already in use by the RSS Channel record %id%, please select another one!',
                    array('%channel_link%' => $permalink, '%id%' => $used_by), self::ALERT_TYPE_WARNING);
                return false;
            }

            if (!isset($channel['content_categories']) || empty($channel['content_categories'])) {
                $this->setAlert('Please select a content category which is associated to the RSS Channel!', array(), self::ALERT_TYPE_WARNING);
                return false;
            }

            if (!empty($channel['channel_webmaster'])) {
                if (!filter_var($channel['channel_webmaster'], FILTER_VALIDATE_EMAIL)) {
                    $this->setAlert('The email address %email% is invalid!',
                        array('%email%' => $channel['channel_webmaster']), self::ALERT_TYPE_WARNING);
                    return false;
                }
            }

            $data['channel_link'] = $permalink;
            $data['content_categories'] = implode(',', $channel['content_categories']);

            if (self::$channel_id < 1) {
                // create a new RSS Channel record
                $this->RSSChannelData->insert($data, self::$channel_id);
                $this->setAlert('Successfull create the new RSS Channel %title%.',
                    array('%title%' => $data['channel_title']), self::ALERT_TYPE_SUCCESS);
            }
            else {
                // update an existing record
                $this->RSSChannelData->update(self::$channel_id, $data);
                $this->setAlert('Updated the RSS Channel %title%.',
                    array('%title%' => $data['channel_title']), self::ALERT_TYPE_SUCCESS);
            }

            return true;
        }
        else {
            // general error (timeout, CSFR ...)
            $this->setAlert('The form is not valid, please check your input and try again!', array(),
                self::ALERT_TYPE_DANGER, true, array('form_errors' => $form->getErrorsAsString(),
                    'method' => __METHOD__, 'line' => __LINE__));
        }
        return false;
    }

    /**
     * Render the RSS Channel form and return the complete dialog
     *
     * @param Form Factory $form
     */
    protected function renderRSSChannelForm($form)
    {
        return $this->app['twig']->render($this->app['utils']->getTemplateFile(
            '@phpManufaktur/flexContent/Template', 'admin/rss.channel.edit.twig'),
            array(
                'usage' => self::$usage,
                'toolbar' => $this->getToolbar('rss'),
                'alert' => $this->getAlert(),
                'form' => $form->createView(),
                'config' => self::$config,
                'route' => array(
                    'action' => '/flexcontent/editor/rss/channel/check',
                    'image' => '/flexcontent/editor/rss/channel/image/select'
                )
            ));
    }

    /**
     * Controller to create or edit a RSS Channel
     *
     * @param Application $app
     * @param integer $channel_id
     * @return string RSS Channel form
     */
    public function ControllerChannelEdit(Application $app, $channel_id=null)
    {
        $this->initialize($app);

        self::$channel_id = !is_null($channel_id) ? $channel_id : -1;

        if ((self::$channel_id < 1) && self::$config['content']['language']['select']) {
            // language selection is active - select language first!
            return $this->selectLanguage();
        }

        $data = array();
        if ((self::$channel_id > 0) && (false === ($data = $this->RSSChannelData->select(self::$channel_id)))) {
            $this->setAlert('The RSS Channel record with the ID %id% does not exists!',
                array('%id%' => self::$channel_id), self::ALERT_TYPE_WARNING);
        }

        $form = $this->getRSSChannelForm($data);
        return $this->renderRSSChannelForm($form);
    }

    /**
     * Controlle to check the submitted form data, create or update the
     * RSS Channel record
     *
     * @param Application $app
     * @return string
     */
    public function ControllerChannelCheck(Application $app)
    {
        $this->initialize($app);

        $data = array();
        // check the form and set self::$channel_id
        if (!$this->checkRSSChannelForm($data)) {
            // the check fail, return to the dialog
            $form = $this->getRSSChannelForm($data);
            return $this->renderRSSChannelForm($form);
        }

        // all done, return to the channel list
        return $this->ControllerChannelList($app);
    }

    /**
     * Controller to select a image for the RSS Channel
     *
     * @param Application $app
     */
    public function ControllerImage(Application $app)
    {
        $this->initialize($app);

        // check the form data and set self::$contact_id
        $data = array();
        if (!$this->checkRSSChannelForm($data)) {
            // the check fails - show the form again
            $form = $this->getRSSChannelForm($data);
            return $this->renderRSSChannelForm($form);
        }

        // grant that the directory exists
        $app['filesystem']->mkdir(FRAMEWORK_PATH.self::$config['content']['images']['directory']['select']);

        // exec the MediaBrowser
        $subRequest = Request::create('/mediabrowser', 'GET', array(
            'usage' => self::$usage,
            'start' => self::$config['content']['images']['directory']['start'],
            'redirect' => '/flexcontent/editor/rss/channel/image/check/id/'.self::$channel_id,
            'mode' => 'public',
            'directory' => self::$config['content']['images']['directory']['select']
        ));
        return $app->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    }

    /**
     * Controller check the submitted image
     *
     * @param Application $app
     * @param integer $category_id
     * @return string
     */
    public function ControllerImageCheck(Application $app, $channel_id)
    {
        $this->initialize($app);

        self::$channel_id = $channel_id;

        // get the selected image
        if (null == ($image = $app['request']->get('file'))) {
            $this->setAlert('There was no image selected.', array(), self::ALERT_TYPE_INFO);
        }
        else {
            // udate the RSS Channel
            $data = array(
                'channel_image' => $image
            );
            $this->RSSChannelData->update(self::$channel_id, $data);
            $this->setAlert('The image %image% was successfull inserted.',
                array('%image%' => basename($image)), self::ALERT_TYPE_SUCCESS);
        }

        if (false === ($data = $this->RSSChannelData->select(self::$channel_id))) {
            $this->setAlert('The RSS Channel record with the ID %id% does not exists!',
                array('%id%' => self::$channel_id), self::ALERT_TYPE_WARNING);
        }
        $form = $this->getRSSChannelForm($data);
        return $this->renderRSSChannelForm($form);
    }
}
