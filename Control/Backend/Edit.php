<?php

/**
 * flexContent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/flexContent
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\flexContent\Control\Backend;

use Silex\Application;
use phpManufaktur\flexContent\Data\Content\Content as ContentData;

class Edit extends Backend
{
    protected $ContentData = null;

    /**
     * (non-PHPdoc)
     * @see \phpManufaktur\flexContent\Control\Backend\Backend::initialize()
     */
    protected function initialize(Application $app)
    {
        parent::initialize($app);
        $this->ContentData = new ContentData($app);
    }

    protected function getContentForm($data=array())
    {
        $form = $this->app['form.factory']->createBuilder('form')
        ->add('content_id', 'hidden', array(
            'data' => isset($data['content_id']) ? $data['content_id'] : -1
        ))
        ->add('title', 'text', array(
            'data' => isset($data['title']) ? $data['title'] : '',
            'required' => self::$config['content']['field']['title']['required']
        ))
        ->add('description', 'textarea', array(
            'data' => isset($data['description']) ? $data['description'] : '',
            'required' => self::$config['content']['field']['description']['required']
        ))
        ->add('keywords', 'textarea', array(
            'data' => isset($data['keywords']) ? $data['keywords'] : '',
            'required' => self::$config['content']['field']['keywords']['required']
        ))
        ->add('permalink', 'text', array(
            'data' => isset($data['permalink']) ? $data['permalink'] : '',
            'required' => self::$config['content']['field']['permalink']['required']
        ))
        ->add('publish_from', 'text', array(
            'attr' => array('class' => 'publish_from'),
            'required' => self::$config['content']['field']['publish_from']['required'],
            'data' => (!empty($data['publish_from']) && ($data['publish_from'] != '0000-00-00 00:00:00')) ? date($this->app['translator']->trans('DATETIME_FORMAT'), strtotime($data['publish_from'])) : date($this->app['translator']->trans('DATETIME_FORMAT')),
        ))
        ->add('publish_to', 'text', array(
            'attr' => array('class' => 'publish_to'),
            'required' => self::$config['content']['field']['publish_to']['required'],
            'data' => (!empty($data['publish_to']) && ($data['publish_to'] != '0000-00-00 00:00:00')) ? date($this->app['translator']->trans('DATETIME_FORMAT'), strtotime($data['publish_to'])) : null,
        ))
        ->add('publish_type', 'choice', array(
            'choices' => $this->ContentData->getPublishTypeValuesForForm(),
            'empty_value' => '- please select -',
            'expanded' => false,
            'required' => true,
            'data' => isset($data['publish_type']) ? $data['publish_type'] : null
        ))
        ;
        return $form->getForm();
    }

    public function ControllerEdit(Application $app, $content_id=null)
    {
        $this->initialize($app);


        $form = $this->getContentForm();

        return $this->app['twig']->render($this->app['utils']->getTemplateFile(
            '@phpManufaktur/flexContent/Template', 'backend/edit.twig'),
            array(
                'usage' => self::$usage,
                'toolbar' => $this->getToolbar('edit'),
                'form' => $form->createView()
            ));
    }
}
