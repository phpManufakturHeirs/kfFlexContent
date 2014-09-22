<?php

/**
 * flexContent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/flexContent
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\flexContent\Control\Admin;

use Silex\Application;
use phpManufaktur\flexContent\Data\Content\Content as ContentData;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use phpManufaktur\flexContent\Data\Content\Tag;
use phpManufaktur\flexContent\Data\Content\TagType;
use phpManufaktur\flexContent\Data\Content\Category;
use phpManufaktur\flexContent\Data\Content\CategoryType;
use phpManufaktur\flexContent\Data\Import\WYSIWYG;
use phpManufaktur\Basic\Data\CMS\Page;
use phpManufaktur\flexContent\Data\Content\Event;
use phpManufaktur\flexContent\Data\Content\Glossary as GlossaryData;

class ContentEdit extends Admin
{
    protected $ContentData = null;
    protected static $content_id = null;
    protected $TagData = null;
    protected $TagTypeData = null;
    protected $CategoryTypeData = null;
    protected $CategoryData = null;
    protected $WYSIWYG = null;
    protected $CMSPage = null;
    protected $EventData = null;
    protected $GlossaryData = null;

    protected static $language = null;

    /**
     * (non-PHPdoc)
     * @see \phpManufaktur\flexContent\Control\Backend\Backend::initialize()
     */
    protected function initialize(Application $app)
    {
        parent::initialize($app);

        self::$content_id = -1;
        $this->ContentData = new ContentData($app);
        $this->TagData = new Tag($app);
        $this->TagTypeData = new TagType($app);
        $this->CategoryData = new Category($app);
        $this->CategoryTypeData = new CategoryType($app);
        $this->WYSIWYG = new WYSIWYG($app);
        $this->CMSPage = new Page($app);
        $this->EventData = new Event($app);
        $this->GlossaryData = new GlossaryData($app);

        self::$language = $this->app['request']->get('form[language]', self::$config['content']['language']['default'], true);
    }

    /**
     * Create the form.factory form for flexContent
     *
     * @param array $data
     */
    protected function getContentForm($data=array())
    {
        if (isset($data['language'])) {
            // set the language property from the content data
            self::$language = $data['language'];
        }

        if (!isset($data['publish_from']) || ($data['publish_from'] == '0000-00-00 00:00:00')) {
            $dt = Carbon::create();
            $dt->addHours(self::$config['content']['field']['publish_from']['add']['hours']);
            $publish_from = $dt->toDateTimeString();
        }
        else {
            $publish_from = $data['publish_from'];
        }

        if (!isset($data['breaking_to']) || ($data['breaking_to'] == '0000-00-00 00:00:00')) {
            $dt = Carbon::createFromFormat('Y-m-d H:i:s', $publish_from);
            $dt->addHours(self::$config['content']['field']['breaking_to']['add']['hours']);
            $breaking_to = $dt->toDateTimeString();
        }
        else {
            $breaking_to = $data['breaking_to'];
        }

        if (!isset($data['archive_from']) || ($data['archive_from'] == '0000-00-00 00:00:00')) {
            $dt = Carbon::createFromFormat('Y-m-d H:i:s', $publish_from);
            $dt->endOfDay();
            $dt->addDays(self::$config['content']['field']['archive_from']['add']['days']);
            $archive_from = $dt->toDateTimeString();
        }
        else {
            $archive_from = $data['archive_from'];
        }

        if (false === ($primary_category = $this->CategoryData->selectPrimaryCategoryIDbyContentID(self::$content_id))) {
            $primary_category = null;
        }
        if (false === ($secondary_categories = $this->CategoryData->selectSecondaryCategoryIDsByContentID(self::$content_id))) {
            $secondary_categories = null;
        }

        $categories = $this->CategoryTypeData->getListForSelect(self::$language);
        if (empty($categories)) {
            $this->setAlert('No category available for the language %language%, please create a category first!',
                array('%language%' => $this->app['translator']->trans(self::$language)), self::ALERT_TYPE_WARNING);
        }
        $categories_second = $this->CategoryTypeData->getListForSelect(self::$language, true);

        // show the permalink URL
        $permalink_url = CMS_URL.str_ireplace('{language}', strtolower(self::$language), self::$config['content']['permalink']['directory']).'/';

        $check_kitcommand = 0;
        $category_target_url = '';
        $category_name = '';
        if (isset($data['content_id']) && ($data['content_id'] > 0) && !is_null($primary_category)) {
            // get the target_url from the primary category
            if (false !== ($category = $this->CategoryTypeData->select($primary_category))) {
                if (!empty($category['target_url'])) {
                    // check if the flexContent kitCommand exists at the target
                    $link = '/'.basename($category['target_url'], $this->CMSPage->getPageExtension());
                    if (false !== ($page_id = $this->CMSPage->getPageIDbyPageLink($link))) {
                        $check_kitcommand = (int) $this->WYSIWYG->checkPageIDforFlexContentCommand($page_id);
                        $category_target_url = $category['target_url'];
                        $category_name = $category['category_name'];
                    }
                }
            }
        }

        // category type
        $category_type = !is_null($primary_category) ? $this->CategoryTypeData->selectType($primary_category) : 'DEFAULT';

        $form = $this->app['form.factory']->createBuilder('form')
        ->add('check_kitcommand', 'hidden', array(
            'data' => $check_kitcommand
        ))
        ->add('primary_category_target_url', 'hidden', array(
            'data' => $category_target_url
        ))
        ->add('primary_category_name', 'hidden', array(
            'data' => $category_name
        ))
        ->add('category_type', 'hidden', array(
            'data' => $category_type
        ))

        // add empty fields for the optional category type EVENT
        ->add('event_date_from', 'hidden')
        ->add('event_date_to', 'hidden')
        ->add('event_organizer', 'hidden')
        ->add('event_location', 'hidden')

        // add empty field for the optional category type GLOSSARY
        ->add('glossary_type', 'hidden')

        ->add('content_id', 'hidden', array(
            'data' => isset($data['content_id']) ? $data['content_id'] : -1
        ))
        ->add('language', 'hidden', array(
            'data' => self::$language,
        ))
        ->add('title', 'text', array(
            'data' => isset($data['title']) ? $this->app['utils']->sanitizeText($data['title']) : '',
            'required' => self::$config['content']['field']['title']['required'],
            'label' => 'Headline'
        ))
        ->add('page_title', 'text', array(
            'data' => isset($data['page_title']) ? $this->app['utils']->sanitizeText($data['page_title']) : '',
            'required' => self::$config['content']['field']['page_title']['required'],
            'label' => 'SEO: Page title'
        ))
        ->add('description', 'textarea', array(
            'data' => isset($data['description']) ? $this->app['utils']->sanitizeText($data['description']) : '',
            'required' => self::$config['content']['field']['description']['required'],
            'label' => 'SEO: Description'
        ))
        ->add('keywords', 'textarea', array(
            'data' => isset($data['keywords']) ? $data['keywords'] : '',
            'required' => self::$config['content']['field']['keywords']['required'],
            'label' => 'SEO: Keywords'
        ))
        ->add('publish_from', 'text', array(
            'required' => self::$config['content']['field']['publish_from']['required'],
            'data' => date($this->app['translator']->trans('DATETIME_FORMAT'), strtotime($publish_from)),
        ))
        ->add('breaking_to', 'text', array(
            'required' => self::$config['content']['field']['breaking_to']['required'],
            'data' => date($this->app['translator']->trans('DATETIME_FORMAT'), strtotime($breaking_to)),
        ))
        ->add('archive_from', 'text', array(
            'required' => self::$config['content']['field']['archive_from']['required'],
            'data' => date($this->app['translator']->trans('DATETIME_FORMAT'), strtotime($archive_from)),
        ))
        ->add('teaser', 'textarea', array(
            'data' => isset($data['teaser']) ? $data['teaser'] : '',
            'required' => self::$config['content']['field']['teaser']['required']
        ))
        ->add('content', 'textarea', array(
            'data' => isset($data['content']) ? $data['content'] : '',
            'required' => self::$config['content']['field']['content']['required']
        ))
        ->add('rss', 'choice', array(
            'choices' => $this->ContentData->getRSSValuesForForm(),
            'empty_value' => '- please select -',
            'expanded' => false,
            'required' => self::$config['content']['field']['rss']['required'],
            'data' => isset($data['rss']) ? $data['rss'] : 'YES',
            'label' => 'RSS'
        ))
        ->add('status', 'choice', array(
            'choices' => $this->ContentData->getStatusTypeValuesForForm(),
            'empty_value' => '- please select -',
            'expanded' => false,
            'required' => self::$config['content']['field']['status']['required'],
            'data' => isset($data['status']) ? $data['status'] : 'UNPUBLISHED'
        ))
        ->add('permalink', 'text', array(
            'required' => self::$config['content']['field']['permalink']['required'],
            'data' => isset($data['permalink']) ? $data['permalink'] : ''
        ))
        ->add('permalink_url', 'hidden', array(
            'data' => $permalink_url
        ))
        ->add('redirect_url', 'text', array(
            'required' => self::$config['content']['field']['redirect_url']['required'],
            'data' => isset($data['redirect_url']) ? $data['redirect_url'] : ''
        ))
        ->add('redirect_target', 'choice', array(
            'choices' => $this->ContentData->getTargetTypeValuesForForm(),
            'empty_value' => '- please select -',
            'expanded' => false,
            'required' => self::$config['content']['field']['redirect_target']['required'],
            'data' => isset($data['redirect_target']) ? $data['redirect_target'] : self::$config['content']['field']['redirect_target']['default']
        ))
        ->add('teaser_image', 'hidden', array(
            'data' => isset($data['teaser_image']) ? $data['teaser_image'] : ''
        ))
        ->add('primary_category', 'choice', array(
            'choices' => $categories,
            'empty_value' => '- please select -',
            'expanded' => false,
            'required' => true,
            'data' => $primary_category
        ))
        ->add('secondary_categories', 'choice', array(
            'choices' => $categories_second,
            'empty_value' => '- please select -',
            'required' => false,
            'multiple' => true,
            'data' => $secondary_categories
        ))
        ;

        if ($category_type == 'EVENT') {
            // additional fields for the handling of EVENTS

            $form->remove('event_date_from');
            $form->remove('event_date_to');
            $form->remove('event_organizer');
            $form->remove('event_location');

            if (!isset($data['event_date_from']) || ($data['event_date_from'] == '0000-00-00 00:00:00')) {
                $dt = Carbon::createFromFormat('Y-m-d H:i:s', $publish_from);
                $dt->startOfDay();
                $event_date_from = $dt->toDateTimeString();
            }
            else {
                $event_date_from = $data['event_date_from'];
            }
            if (!isset($data['event_date_to']) || ($data['event_date_to'] == '0000-00-00 00:00:00')) {
                $dt = Carbon::createFromFormat('Y-m-d H:i:s', $publish_from);
                $dt->endOfDay();
                $event_date_to = $dt->toDateTimeString();
            }
            else {
                $event_date_to = $data['event_date_to'];
            }

            $form->add('event_date_from', 'text', array(
                'required' => self::$config['content']['field']['event_date_from']['required'],
                'data' => date($this->app['translator']->trans('DATETIME_FORMAT'), strtotime($event_date_from))
            ));

            $form->add('event_date_to', 'text', array(
                'required' => self::$config['content']['field']['event_date_to']['required'],
                'data' => date($this->app['translator']->trans('DATETIME_FORMAT'), strtotime($event_date_to))
            ));

            $form->add('event_organizer', 'choice', array(
                'choices' => $this->app['contact']->selectContactIdentifiersForSelect(
                    !self::$config['content']['field']['event_organizer']['required'],
                    self::$config['content']['field']['event_organizer']['tags']),
                'empty_value' => self::$config['content']['field']['event_organizer']['required'] ? '- please select -' : null,
                'expanded' => false,
                'required' => self::$config['content']['field']['event_organizer']['required'],
                'data' => isset($data['event_organizer']['contact_id']) ? $data['event_organizer']['contact_id'] : -1
            ));

            $form->add('event_location', 'choice', array(
                'choices' => $this->app['contact']->selectContactIdentifiersForSelect(
                    !self::$config['content']['field']['event_location']['required'],
                    self::$config['content']['field']['event_location']['tags']),
                'empty_value' => '- please select -',
                'expanded' => false,
                'required' => self::$config['content']['field']['event_location']['required'],
                'data' => isset($data['event_location']['contact_id']) ? $data['event_location']['contact_id'] : -1
            ));

        }

        if ($category_type == 'GLOSSARY') {
            // additional fields for the GLOSSARY handling

            $form->remove('glossary_type');

            $form->add('glossary_type', 'choice', array(
               'choices' => $this->GlossaryData->getGlossaryTypeValuesForForm(),
                'empty_value' => '- please select -',
                'required' => self::$config['content']['field']['glossary_type']['required'],
                'data' => isset($data['glossary_type']) ? $data['glossary_type'] : 'KEYWORD'
            ));
        }

        return $form->getForm();
    }

    /**
     * Check the submitted form, create a new record or update an existing
     *
     * @param array reference $data
     * @return boolean
     */
    protected function checkContentForm(&$data=array())
    {
        $request = $this->app['request']->get('form');

        // get the form
        $form = $this->getContentForm();
        // get the requested data
        $form->bind($this->app['request']);

        if ($form->isValid()) {
            // the form is valid
            $content = $form->getData();
            $data = array();

            self::$content_id = $content['content_id'];
            $data['content_id'] = self::$content_id;

            // only for event data
            $event = array();

            // only for glossary data
            $glossary = array();

            $checked = true;

            // check the fields
            foreach (self::$config['content']['field'] as $name => $property) {
                switch ($name) {
                    case 'title':
                        if (!$property['required']) {
                            // the title must be always set!
                            $this->setAlert('The headline is always needed and can not switched off, please check the configuration!',
                                array(), self::ALERT_TYPE_WARNING);
                        }
                        if ((strlen($content[$name]) < $property['length']['minimum']) ||
                            (strlen($content[$name]) > $property['length']['maximum'])) {
                            $this->setAlert('The headline should have a length between %minimum% and %maximum% characters (actual: %length%).',
                                array('%minimum%' => $property['length']['minimum'],
                                    '%maximum%' => $property['length']['maximum'], '%length%' => strlen($content[$name])),
                                self::ALERT_TYPE_WARNING);
                            $checked = false;
                        }
                        $data[$name] = !is_null($content[$name]) ? $content[$name] : '';
                        break;
                    case 'page_title':
                        if (is_null($content[$name]) || empty($content[$name])) {
                            // set the page title from the headline!
                            $content[$name] = $data['title'];
                        }
                        if ((strlen($content[$name]) < $property['length']['minimum']) ||
                            (strlen($content[$name]) > $property['length']['maximum'])) {
                            $this->setAlert('The page title should have a length between %minimum% and %maximum% characters (actual: %length%).',
                                array('%minimum%' => $property['length']['minimum'],
                                    '%maximum%' => $property['length']['maximum'], '%length%' => strlen($content[$name])),
                                self::ALERT_TYPE_WARNING);
                            $checked = false;
                        }
                        $data[$name] = !is_null($content[$name]) ? $content[$name] : '';
                        break;
                    case 'description':
                        if ($property['required']) {
                            if ((strlen($content[$name]) < $property['length']['minimum']) ||
                                (strlen($content[$name]) > $property['length']['maximum'])) {
                                $this->setAlert('The description should have a length between %minimum% and %maximum% characters (actual: %length%).',
                                    array('%minimum%' => $property['length']['minimum'],
                                        '%maximum%' => $property['length']['maximum'], '%length%' => strlen($content[$name])),
                                    self::ALERT_TYPE_WARNING);
                                $checked = false;
                            }
                        }
                        $data[$name] = !is_null($content[$name]) ? $content[$name] : '';
                        break;
                    case 'keywords':
                        if ($property['required']) {
                            $separator = ($property['separator'] == 'comma') ? ',' : ' ';
                            if (false === strpos($content[$name], $separator)) {
                                $this->setAlert('Please define keywords for the content', array(), self::ALERT_TYPE_WARNING);
                                $data[$name] = $content[$name];
                                $checked = false;
                            }
                            else {
                                $explode = explode($separator, utf8_decode($content[$name]));
                                $keywords = array();
                                foreach ($explode as $item) {
                                    $keyword = strtolower(trim($item));
                                    if (!empty($keyword)) {
                                        $keywords[] = $keyword;
                                    }
                                }
                                if ((count($keywords) < $property['words']['minimum']) ||
                                    (count($keywords) > $property['words']['maximum'])) {
                                    $this->setAlert('Please define between %minimum% and %maximum% keywords, actual: %count%',
                                        array('%minimum%' => $property['words']['minimum'],
                                            '%maximum' => $property['words']['maximum'], '%count%' => count($keywords)),
                                        self::ALERT_TYPE_WARNING);
                                    $checked = false;
                                }
                                $data[$name] = utf8_encode(implode(($separator == 'comma') ? ', ' : ' ', $keywords));
                            }
                        }
                        else {
                            if (($property['separator'] == 'comma') && strpos($content[$name], ',')) {
                                // proper separate the keywords
                                $explode = explode(',', utf8_decode($content[$name]));
                                $keywords = array();
                                foreach ($explode as $item) {
                                    $keyword = strtolower(trim($item));
                                    if (!empty($keyword)) {
                                        $keywords[] = $keyword;
                                    }
                                }
                                $data[$name] = utf8_encode(implode(', ', $keywords));
                            }
                            else {
                                $data[$name] = !is_null($content[$name]) ? $content[$name] : '';
                            }
                        }
                        break;
                    case 'permalink':
                        if (!$property['required']) {
                            // the 'required' flag for the permanent link can not switched off
                            $this->setAlert('The permanent link is always needed and can not switched off, please check the configuration!',
                                    array(), self::ALERT_TYPE_DANGER);
                        }

                        $permalink = !is_null($content[$name]) ? strtolower($content[$name]) : '';
                        $permalink = $this->app['utils']->sanitizeLink($permalink);

                        if ((self::$content_id < 1) && $this->ContentData->existsPermaLink($permalink, self::$language)) {
                            // this PermaLink already exists!
                            $this->setAlert('The permalink %permalink% is already in use, please select another one!',
                                    array('%permalink%' => $permalink), self::ALERT_TYPE_WARNING);
                            $checked = false;
                        }
                        elseif ((self::$content_id > 0) &&
                            (false !== ($used_by = $this->ContentData->selectContentIDbyPermaLink($permalink))) &&
                            ($used_by != self::$content_id)) {
                            $this->setAlert('The permalink %permalink% is already in use by the flexContent record %id%, please select another one!',
                                array('%permalink%' => $permalink, '%id%' => $used_by), self::ALERT_TYPE_WARNING);
                            $checked = false;
                        }
                        $data[$name] = $permalink;
                        break;
                    case 'redirect_url':

                        // @todo: check the URL!
                        $data[$name] = !is_null($content[$name]) ? $content[$name] : '';

                        break;
                    case 'rss':
                    case 'redirect_target':
                        $data[$name] = $content[$name];
                        break;
                    case 'publish_from':
                        if (!$property['required']) {
                            // publish_from is always needed!
                            $this->setAlert("The 'publish from' field is always needed and can not switched off, please check the configuration!",
                                array(), self::ALERT_TYPE_DANGER);
                        }
                        if (empty($content[$name])) {
                            // if field is empty set the actual date/time
                            $dt = Carbon::create();
                            $dt->addHours($property['add']['hours']);
                            $content[$name] = date($this->app['translator']->trans('DATETIME_FORMAT'), $dt->getTimestamp());
                        }
                        // convert the date/time string
                        $dt = Carbon::createFromFormat($this->app['translator']->trans('DATETIME_FORMAT'), $content[$name]);
                        $data[$name] = $dt->toDateTimeString();
                        break;
                    case 'breaking_to':
                        // ignore property 'required'!
                        if (!isset($data['publish_from'])) {
                            // problem: publish_from must defined first!
                            $this->setAlert("Problem: '%first%' must be defined before '%second%', please check the configuration file!",
                                array('%first%' => 'publish_from', '%second%' => 'breaking_to'), self::ALERT_TYPE_DANGER);
                            $checked = false;
                            break;
                        }
                        if (empty($content[$name])) {
                            // if field is empty create date/time as configured
                            $dt = Carbon::createFromFormat('Y-m-d H:i:s', $data['publish_from']);
                            $dt->addHours($property['add']['hours']);
                            $content[$name] = date($this->app['translator']->trans('DATETIME_FORMAT'), $dt->getTimestamp());
                        }
                        // convert the date/time string
                        $dt = Carbon::createFromFormat($this->app['translator']->trans('DATETIME_FORMAT'), $content[$name]);
                        $data[$name] = $dt->toDateTimeString();
                        break;
                    case 'archive_from':
                        // ignore property 'required'!
                        if (!isset($data['publish_from'])) {
                            // problem: publish_from must defined first!
                            $this->setAlert("Problem: '%first%' must be defined before '%second%', please check the configuration file!",
                                array('%first%' => 'publish_from', '%second%' => 'archive_from'), self::ALERT_TYPE_DANGER);
                            $checked = false;
                            break;
                        }
                        if (empty($content[$name])) {
                            // if field is empty create date/time as configured
                            $dt = Carbon::createFromFormat('Y-m-d H:i:s', $data['publish_from']);
                            $dt->endOfDay();
                            $dt->addDays($property['add']['days']);
                            $content[$name] = date($this->app['translator']->trans('DATETIME_FORMAT'), $dt->getTimestamp());
                        }
                        // convert the date/time string
                        $dt = Carbon::createFromFormat($this->app['translator']->trans('DATETIME_FORMAT'), $content[$name]);
                        $data[$name] = $dt->toDateTimeString();
                        break;
                    case 'event_date_from':
                        if (!isset($data['publish_from'])) {
                            // problem: publish_from must defined first!
                            $this->setAlert("Problem: '%first%' must be defined before '%second%', please check the configuration file!",
                                array('%first%' => 'publish_from', '%second%' => 'event_date_from'), self::ALERT_TYPE_DANGER);
                            $checked = false;
                            break;
                        }
                        if (isset($content[$name])) {
                            // check only if the field isset
                            if (empty($content[$name])) {
                                // if field is empty create date/time as configured
                                $dt = Carbon::createFromFormat('Y-m-d H:i:s', $data['publish_from']);
                                $dt->startOfDay();
                                $content[$name] = date($this->app['translator']->trans('DATETIME_FORMAT'), $dt->getTimestamp());
                                $this->setAlert('The date and time for the event where set automatically, you must check them!');
                            }
                            // convert the date/time string
                            $dt = Carbon::createFromFormat($this->app['translator']->trans('DATETIME_FORMAT'), $content[$name]);

                            $publish_from = Carbon::createFromFormat('Y-m-d H:i:s', $data['publish_from']);
                            if ($dt->lt($publish_from)) {
                                // the event_date_from is less the publish_from date!
                                $this->setAlert('The event starting date %event_date_from% is less then the content publish from date %publish_from%, this is not allowed!',
                                    array('%event_date_from%' => date($this->app['translator']->trans('DATETIME_FORMAT'), $dt->timestamp),
                                        '%publish_from%' => date($this->app['translator']->trans('DATETIME_FORMAT'), $publish_from->timestamp)),
                                    self::ALERT_TYPE_WARNING);
                                $checked = false;
                                break;
                            }

                            $data[$name] = $dt->toDateTimeString();
                            $event[$name] = $data[$name];
                        }
                        break;
                    case 'event_date_to':
                        if (!isset($data['publish_from'])) {
                            // problem: publish_from must defined first!
                            $this->setAlert("Problem: '%first%' must be defined before '%second%', please check the configuration file!",
                                array('%first%' => 'publish_from', '%second%' => 'event_date_to'), self::ALERT_TYPE_DANGER);
                            $checked = false;
                            break;
                        }
                        if (isset($content[$name])) {
                            // check only if the field isset
                            if (!isset($data['event_date_from'])) {
                                // problem: event_date_from must defined first!
                                $this->setAlert("Problem: '%first%' must be defined before '%second%', please check the configuration file!",
                                    array('%first%' => 'event_date_from', '%second%' => 'event_date_to'), self::ALERT_TYPE_DANGER);
                                $checked = false;
                                break;
                            }
                            if (empty($content[$name])) {
                                // if field is empty create date/time as configured
                                $dt = Carbon::createFromFormat('Y-m-d H:i:s', $data['publish_from']);
                                $dt->endOfDay();
                                $content[$name] = date($this->app['translator']->trans('DATETIME_FORMAT'), $dt->getTimestamp());
                            }
                            // convert the date/time string
                            $dt = Carbon::createFromFormat($this->app['translator']->trans('DATETIME_FORMAT'), $content[$name]);

                            $event_date_from = Carbon::createFromFormat('Y-m-d H:i:s', $data['event_date_from']);
                            if ($dt->lt($event_date_from)) {
                                // the event_date_from is less the publish_from date!
                                $this->setAlert('The event ending date %event_date_to% is less then the event starting date %event_date_from%!',
                                    array('%event_date_from%' => date($this->app['translator']->trans('DATETIME_FORMAT'), $event_date_from->timestamp),
                                        '%event_date_to%' => date($this->app['translator']->trans('DATETIME_FORMAT'), $dt->timestamp)),
                                    self::ALERT_TYPE_WARNING);
                                $checked = false;
                                break;
                            }

                            $data[$name] = $dt->toDateTimeString();
                            $event[$name] = $data[$name];
                        }
                        break;
                    case 'event_organizer':
                    case 'event_location':
                        if (isset($content[$name])) {
                            // check only if the field isset
                            $data[$name] = $content[$name];
                            $event[$name] = $data[$name];
                        }
                        break;
                    case 'teaser':
                    case 'content':
                        if ($property['required'] && empty($content[$name])) {
                            $this->setAlert('The field %name% can not be empty!',
                                array('%name%' => $this->app['translator']->trans($name)), self::ALERT_TYPE_WARNING);
                            $checked = false;
                        }
                        $data[$name] = !is_null($content[$name]) ? $content[$name] : '';
                        break;
                    case 'status':
                        // ignore property 'required'!
                        $values = $this->app['db.utils']->getEnumValues(FRAMEWORK_TABLE_PREFIX.'flexcontent_content', 'status');
                        if (!in_array($content[$name], $values)) {
                            $this->setAlert('Please check the status, the value %value% is invalid!',
                                array('%value%' => $content[$name]), self::ALERT_TYPE_WARNING);
                            $checked = false;
                        }
                        $data[$name] = $content[$name];
                        break;
                    case 'language':
                        // ignore property 'required'!
                        $language_checked = false;
                        foreach (self::$config['content']['language']['support'] as $language) {
                            if ($content[$name] == $language['code']) {
                                $language_checked = true;
                                break;
                            }
                        }
                        $data[$name] = $language_checked ? $content[$name] : self::$config['content']['language']['default'];
                        break;
                    case 'primary_category':
                        // ignore the property 'required'
                        if (intval($content['name'] < 1)) {
                            $this->setAlert('Please select a category!', array(), self::ALERT_TYPE_WARNING);
                            $checked = false;
                        }
                        break;
                    case 'glossary_type':
                        if (isset($content[$name])) {
                            // ignore the property 'required'
                            $unique = $this->app['utils']->specialCharsToAsciiChars($data['title'], true);
                            if ((false !== ($id = $this->GlossaryData->existsUnique($unique))) &&
                                ($id !== self::$content_id)) {
                                $this->setAlert('The glossary item %title% already exists and is used by the flexContent ID %id%!',
                                    array('%title%' => $data['title'], '%id%' => $id), self::ALERT_TYPE_WARNING);
                                $checked = false;
                            }
                            $glossary[$name] = $content[$name];
                            $glossary['glossary_unique'] = $unique;
                        }
                        break;
                }
            }

            // additional checks
            if (empty($data['teaser']) && empty($data['content'])) {
                $this->setAlert('At least must it exists some text within the teaser or the content, at the moment the Teaser and the Content are empty!',
                            array(), self::ALERT_TYPE_WARNING);
                $checked = false;
            }

            $category = $this->CategoryTypeData->select($content['primary_category']);
            $primary_category_type = $category['category_type'];

            if (empty($glossary) && ($primary_category_type == 'GLOSSARY')) {
                // new glossary record
                $unique = $this->app['utils']->specialCharsToAsciiChars($data['title'], true);
                if ((false !== ($id = $this->GlossaryData->existsUnique($unique))) &&
                    ($id !== self::$content_id)) {
                    $this->setAlert('The glossary item %title% already exists and is used by the flexContent ID %id%!',
                        array('%title%' => $data['title'], '%id%' => $id), self::ALERT_TYPE_WARNING);
                    $checked = false;
                }
                $glossary['glossary_type'] = 'KEYWORD';
                $glossary['glossary_unique'] = $unique;
            }

            if ($checked) {
                // add update information

                $record = $data;

                foreach ($event as $key => $value) {
                    // remove event field from the content fields
                    unset($record[$key]);
                }

                $record['update_username'] = $this->app['account']->getUsername();

                if (self::$content_id < 1) {
                    // insert a new record
                    $record['author_username'] = $this->app['account']->getUsername();
                    $this->ContentData->insert($record, self::$content_id);
                    $this->setAlert('Successfull created a new flexContent record with the ID %id%.',
                        array('%id%' => self::$content_id), self::ALERT_TYPE_SUCCESS);
                    // important: set the content_id also in the $data array!
                    $data['content_id'] = self::$content_id;
                }
                else {
                    // update an existing record
                    $this->ContentData->update($record, self::$content_id);
                    $this->setAlert('Succesfull updated the flexContent record with the ID %id%',
                        array('%id%' => self::$content_id), self::ALERT_TYPE_SUCCESS);
                }

                // check the CATEGORIES
                $this->checkContentCategories($content['primary_category'], $content['secondary_categories']);

                // check the TAGs
                $this->checkContentTags();

                if (!empty($event)) {
                    // this is an event ...
                    $event['language'] = $data['language'];

                    if (!$this->EventData->existsContentID(self::$content_id)) {
                        // insert a new event record
                        $event['content_id'] = self::$content_id;
                        $this->EventData->insert($event);
                    }
                    else {
                        // update existing event record
                        $this->EventData->updateContentID(self::$content_id, $event);
                    }
                }

                if (!empty($glossary)) {
                    // glossary entry
                    if ($primary_category_type != 'GLOSSARY') {
                        // wrong category type!
                        $this->GlossaryData->deleteContentID(self::$content_id);
                    }
                    else {
                        $glossary['language'] = $data['language'];

                        if ($this->GlossaryData->existsContentID(self::$content_id)) {
                            // update an existing record
                            $this->GlossaryData->updateContentID(self::$content_id, $glossary);
                        }
                        else {
                            // insert a new glossary record
                            $glossary['content_id'] = self::$content_id;
                            $this->GlossaryData->insert($glossary);
                        }
                    }
                }
                elseif ($this->GlossaryData->existsContentID(self::$content_id)) {
                    // remove this entry
                    $this->GlossaryData->deleteContentID(self::$content_id);
                }


                return true;
            }
        }
        else {
            // general error (timeout, CSFR ...)
            $this->setAlert('The form is not valid, please check your input and try again!', array(),
                self::ALERT_TYPE_DANGER, true, array('form_errors' => $form->getErrorsAsString(),
                    'method' => __METHOD__, 'line' => __LINE__));
        }

        // always check the TAGs
        $this->checkContentTags();
        return false;
    }

    /**
     * Check the primary and secondary CATEGORIES, add or remove them ...
     *
     * @param unknown $primary_category
     * @param unknown $secondary_categories
     */
    protected function checkContentCategories($primary_category, $secondary_categories)
    {
        // check the primary category
        if (false !== ($old_category = $this->CategoryData->selectPrimaryCategoryIDbyContentID(self::$content_id))) {
            if ($old_category != $primary_category) {
                // delete the old category
                $this->CategoryData->deleteByContentIDandCategoryID(self::$content_id, $old_category);
                // delete the new category, perhaps it is used as secondary category
                $this->CategoryData->deleteByContentIDandCategoryID(self::$content_id, $primary_category);
                // insert the primary category
                $data = array(
                    'content_id' => self::$content_id,
                    'category_id' => $primary_category,
                    'is_primary' => 1
                );
                $this->CategoryData->insert($data);
            }
        }
        else {
            // insert a primary category
            $data = array(
                'content_id' => self::$content_id,
                'category_id' => $primary_category,
                'is_primary' => 1
            );
            $this->CategoryData->insert($data);
        }

        // check the secondary categories
        if (false !== ($old_categories = $this->CategoryData->selectSecondaryCategoryIDsByContentID(self::$content_id))) {
            foreach ($old_categories as $old_category) {
                if (!in_array($old_category, $secondary_categories)) {
                    // delete this category
                    $this->CategoryData->deleteByContentIDandCategoryID(self::$content_id, $old_category);
                }
                else {
                    // unset this key
                    unset($secondary_categories[array_search($old_category, $secondary_categories)]);
                }
            }
        }

        foreach ($secondary_categories as $category) {
            if ($category == $primary_category) {
                // ignore the primary category in the seconds ...
                continue;
            }
            // insert as second category
            $data = array(
                'content_id' => self::$content_id,
                'category_id' => $category,
                'is_primary' => 0
            );
            $this->CategoryData->insert($data);
        }

    }

    /**
     * Check the tags which are associated to the flexContent, insert, update and delete them
     *
     * @throws \Exception
     */
    protected function checkContentTags()
    {
        if (null !== ($tags = $this->app['request']->get('tag'))) {
            $position = 1;
            $tag_ids = array();
            foreach($tags as $key => $value) {
                if(preg_match('/([0-9]*)-?(a|d)?$/', $key, $keyparts) === 1) {
                    if(isset($keyparts[2])) {
                        switch($keyparts[2]) {
                            case 'a':
                                // check the key
                                if (false === ($name = $this->TagTypeData->selectNameByID($keyparts[1]))) {
                                    throw new \Exception('The Tag Type with the ID '.$keyparts[1].' does not exists!');
                                }
                                if ($name != $value) {
                                    // the TAG name was changed
                                    $permalink = $this->app['utils']->sanitizeLink($value);
                                    if ($this->TagTypeData->existsPermaLink($permalink)) {
                                        // this permalink is already in use - add a counter
                                        $count = $this->TagTypeData->countPermaLinksLikeThis($permalink);
                                        $count++;
                                        // add a counter to the new permanet link
                                        $permalink = sprintf('%s-%d', $permalink, $count);
                                    }
                                    $data = array(
                                        'tag_name' => $value,
                                        'tag_permalink' => $permalink
                                    );
                                    // update the TAG TYPE record
                                    $this->TagTypeData->update($keyparts[1], $data);
                                    $this->setAlert('The tag %old% was changed to %new%. This update will affect all contents.',
                                        array('%old%' => $name, '%new%' => $value), self::ALERT_TYPE_SUCCESS);
                                }
                                // add the TAG to the tag table
                                $data = array(
                                    'tag_id' => $keyparts[1],
                                    'position' => $position,
                                    'content_id' => self::$content_id
                                );
                                if (false === ($id = $this->TagData->selectIDbyTagIDandContentID($keyparts[1], self::$content_id))) {
                                    // insert a new TAG record
                                    $this->TagData->insert($data, $id);
                                    $this->setAlert('Associated the tag %tag% to this flexContent.',
                                        array('%tag%' => $value), self::ALERT_TYPE_SUCCESS);
                                }
                                else {
                                    // update an existing TAG record
                                    $this->TagData->update($id, $data);
                                }

                                $tag_ids[] = $id;
                                $position++;

                                break;
                            case 'd':
                                // delete the Tag
                                $this->TagTypeData->delete($keyparts[1]);

                                $this->setAlert('The tag %tag% was successfull deleted and removed from all content.',
                                    array('%tag%' => $value), self::ALERT_TYPE_SUCCESS);
                                break;
                        }
                    }
                    else {
                        // insert a new key
                        $tag_id = -1;
                        $permalink = $this->app['utils']->sanitizeLink($value);
                        if ($this->TagTypeData->existsPermaLink($permalink, self::$language)) {
                            // this permalink is already in use - add a counter
                            $count = $this->TagTypeData->countPermaLinksLikeThis($permalink, self::$language);
                            $count++;
                            // add a counter to the new permanet link
                            $permalink = sprintf('%s-%d', $permalink, $count);
                        }
                        $data = array(
                            'tag_name' => $value,
                            'tag_permalink' => $permalink,
                            'language' => self::$language
                        );
                        // create a new TAG ID
                        $this->TagTypeData->insert($data, $tag_id);

                        // add a new TAG record
                        $data = array(
                            'tag_id' => $tag_id,
                            'position' => $position,
                            'content_id' => self::$content_id
                        );
                        $id = -1;
                        $this->TagData->insert($data, $id);

                        $tag_ids[] = $id;
                        $position++;

                        $this->setAlert('Created the new tag %tag% and attached it to this content.',
                            array('%tag%' => $value), self::ALERT_TYPE_SUCCESS);
                    }
                }
            }

            $checks = $this->TagData->selectByContentID(self::$content_id);
            foreach ($checks as $check) {
                if (!in_array($check['id'], $tag_ids)) {
                    // delete this record
                    $this->TagData->delete($check['id']);
                    $tag_name = $this->TagTypeData->selectNameByID($check['tag_id']);
                    $this->setAlert('The tag %tag% is no longer associated with this content.',
                        array('%tag%' => $tag_name), self::ALERT_TYPE_SUCCESS);
                }
            }
        }
        else {
            // remove all associated tags
            $checks = $this->TagData->selectByContentID(self::$content_id);
            if (is_array($checks)) {
                foreach ($checks as $check) {
                    // delete this #hashtag
                    $this->TagData->delete($check['id']);
                    $tag_name = $this->TagTypeData->selectNameByID($check['tag_id']);
                    $this->setAlert('The tag %tag% is no longer associated with this content.',
                        array('%tag%' => $tag_name), self::ALERT_TYPE_SUCCESS);
                }
            }
        }
    }

    /**
     * Render the form and return the complete dialog
     *
     * @param Form Factory $form
     */
    protected function renderContentForm($form)
    {
        // set content ID and language as session - i.e. for the CKEditor dialogs
        $this->app['session']->set('FLEXCONTENT_EDIT_CONTENT_ID', self::$content_id);
        $this->app['session']->set('FLEXCONTENT_EDIT_CONTENT_LANGUAGE', self::$language);

        return $this->app['twig']->render($this->app['utils']->getTemplateFile(
            '@phpManufaktur/flexContent/Template', 'admin/edit.twig'),
            array(
                'usage' => self::$usage,
                'toolbar' => $this->getToolbar('edit'),
                'alert' => $this->getAlert(),
                'form' => $form->createView(),
                'config' => self::$config,
                'tags' => $this->TagData->getSimpleTagArrayForContentID(self::$content_id)
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

        $this->setAlert('Please select the language for the new flexContent.');

        return $this->app['twig']->render($this->app['utils']->getTemplateFile(
            '@phpManufaktur/flexContent/Template', 'admin/select.language.twig'),
            array(
                'usage' => self::$usage,
                'toolbar' => $this->getToolbar('edit'),
                'alert' => $this->getAlert(),
                'form' => $form->createView(),
                'config' => self::$config,
                'action' => '/flexcontent/editor/edit/language/check'
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

        $form = $this->getContentForm($data);
        return $this->renderContentForm($form);
    }

    /**
     * Controller to create or edit contents
     *
     * @param Application $app
     * @param integer $content_id
     */
    public function ControllerEdit(Application $app, $content_id=null)
    {
        $this->initialize($app);

        if (!is_null($content_id)) {
            self::$content_id = $content_id;
        }

        if ((self::$content_id < 1) && self::$config['content']['language']['select']) {
            // language selection is active - select language first!
            return $this->selectLanguage();
        }

        $data = array();
        if ((self::$content_id > 0) && (false === ($data = $this->ContentData->select(self::$content_id)))) {
            $this->setAlert('The flexContent record with the ID %id% does not exists!',
                array('%id%' => self::$content_id), self::ALERT_TYPE_WARNING);
        }

        $form = $this->getContentForm($data);
        return $this->renderContentForm($form);
    }

    /**
     * Controller executed when the form was submitted
     *
     * @param Application $app
     * @return string
     */
    public function ControllerEditCheck(Application $app)
    {
        $this->initialize($app);

        $data = array();
        // check the form
        $this->checkContentForm($data);

        if ((self::$content_id > 0) && (false === ($data = $this->ContentData->select(self::$content_id)))) {
            $this->setAlert('The flexContent record with the ID %id% does not exists!',
                array('%id%' => self::$content_id), self::ALERT_TYPE_WARNING);
        }

        // get the form
        $form = $this->getContentForm($data);
        // return the form with results
        return $this->renderContentForm($form);
    }

    /**
     * Controller to select a image
     *
     * @param Application $app
     */
    public function ControllerImage(Application $app)
    {
        $this->initialize($app);

        // check the form data and set self::$contact_id
        $data = array();
        if (!$this->checkContentForm($data)) {
            // the check fails - show the form again
            $form = $this->getContentForm($data);
            return $this->renderContentForm($form);
        }

        // grant that the directory exists
        $app['filesystem']->mkdir(FRAMEWORK_PATH.self::$config['content']['images']['directory']['select']);

        // exec the MediaBrowser
        $subRequest = Request::create('/mediabrowser', 'GET', array(
            'usage' => self::$usage,
            'start' => self::$config['content']['images']['directory']['start'],
            'redirect' => '/flexcontent/editor/edit/image/check/id/'.self::$content_id,
            'mode' => 'public',
            'directory' => self::$config['content']['images']['directory']['select']
        ));
        return $app->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    }

    /**
     * Controller check the submitted image
     *
     * @param Application $app
     * @param integer $content_id
     * @return string
     */
    public function ControllerImageCheck(Application $app, $content_id)
    {
        $this->initialize($app);

        self::$content_id = $content_id;

        // get the selected image
        if (null == ($image = $app['request']->get('file'))) {
            $this->setAlert('There was no image selected.', array(), self::ALERT_TYPE_INFO);
        }
        else {
            // udate the flexContent record
            $data = array(
                'teaser_image' => $image
            );
            $this->ContentData->update($data, self::$content_id);
            $this->setAlert('The image %image% was successfull inserted.',
                array('%image%' => basename($image)), self::ALERT_TYPE_SUCCESS);
        }

        if (false === ($data = $this->ContentData->select(self::$content_id))) {
            $this->setAlert('The flexContent record with the ID %id% does not exists!',
                array('%id%' => self::$content_id), self::ALERT_TYPE_WARNING);
        }
        $form = $this->getContentForm($data);
        return $this->renderContentForm($form);
    }

    /**
     * Controller to remove the actual image from the teaser section
     *
     * @param Application $app
     * @param integer $content_id
     */
    public function ControllerImageRemove(Application $app, $content_id)
    {
        $this->initialize($app);

        self::$content_id = $content_id;

        // udate the flexContent record
        $data = array(
            'teaser_image' => '' // empty field == no image
        );
        $this->ContentData->update($data, self::$content_id);
        $this->setAlert('The image was successfull removed.',
            array(), self::ALERT_TYPE_SUCCESS);

        if (false === ($data = $this->ContentData->select(self::$content_id))) {
            $this->setAlert('The flexContent record with the ID %id% does not exists!',
                array('%id%' => self::$content_id), self::ALERT_TYPE_WARNING);
        }
        $form = $this->getContentForm($data);
        return $this->renderContentForm($form);
    }
}
