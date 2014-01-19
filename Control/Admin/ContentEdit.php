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

class ContentEdit extends Admin
{
    protected $ContentData = null;
    protected static $content_id = null;
    protected $TagData = null;
    protected $TagTypeData = null;
    protected $CategoryTypeData = null;
    protected $CategoryData = null;
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

        // show the permalink URL
        $permalink_url = CMS_URL.str_ireplace('{language}', strtolower(self::$language), self::$config['content']['permalink']['directory']).'/';

        $form = $this->app['form.factory']->createBuilder('form')
        ->add('content_id', 'hidden', array(
            'data' => isset($data['content_id']) ? $data['content_id'] : -1
        ))
        ->add('language', 'hidden', array(
            'data' => self::$language,
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
            'choices' => $categories,
            'empty_value' => '- please select -',
            'required' => false,
            'multiple' => true,
            'data' => $secondary_categories
        ))

        ;
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

            $checked = true;

            // check the fields
            foreach (self::$config['content']['field'] as $name => $property) {
                switch ($name) {
                    case 'title':
                        if (!$property['required']) {
                            // the title must be always set!
                            $this->setAlert('The title is always needed and con not switched off, please check the configuration!',
                                array(), self::ALERT_TYPE_WARNING);
                        }
                        if ((strlen($content[$name]) < $property['length']['minimum']) ||
                        (strlen($content[$name]) > $property['length']['maximum'])) {
                            $this->setAlert('The title should have a length between %minimum% and %maximum% characters (actual: %length%).',
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
                                $explode = explode($separator, $content[$name]);
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
                                $data[$name] = implode($separator, $keywords);
                            }
                        }
                        else {
                            $data[$name] = !is_null($content[$name]) ? $content[$name] : '';
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
                }
            }

            // additional checks
            if (empty($data['teaser']) && empty($data['content'])) {
                $this->setAlert('At least must it exists some text within the teaser or the content, at the moment the Teaser and the Content are empty!',
                            array(), self::ALERT_TYPE_WARNING);
                $checked = false;
            }

            if ($checked) {
                // add update information
                $data['update_username'] = $this->app['account']->getUsername();

                if (self::$content_id < 1) {
                    // insert a new record
                    $data['author_username'] = $this->app['account']->getUsername();
                    $this->ContentData->insert($data, self::$content_id);
                    $this->setAlert('Successfull created a new flexContent record with the ID %id%.',
                        array('%id%' => self::$content_id), self::ALERT_TYPE_SUCCESS);
                    // important: set the content_id also in the $data array!
                    $data['content_id'] = self::$content_id;
                }
                else {
                    // update an existing record
                    $this->ContentData->update($data, self::$content_id);
                    $this->setAlert('Succesfull updated the flexContent record with the ID %id%',
                        array('%id%' => self::$content_id), self::ALERT_TYPE_SUCCESS);
                }

                // check the CATEGORIES
                $this->checkContentCategories($content['primary_category'], $content['secondary_categories']);

                // check the TAGs
                $this->checkContentTags();
                return true;
            }
        }
        else {
            // general error (timeout, CSFR ...)
            $this->setAlert('The form is not valid, please check your input and try again!', array(), self::ALERT_TYPE_DANGER);
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
            $this->setAlert('The form is not valid, please check your input and try again!', array(), self::ALERT_TYPE_DANGER);
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
        $subRequest = Request::create('/admin/mediabrowser', 'GET', array(
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
            $this->setAlert('There was no image selected.', self::ALERT_TYPE_INFO);
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
}
