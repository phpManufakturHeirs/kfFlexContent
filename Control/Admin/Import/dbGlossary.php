<?php

/**
 * flexContent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/flexContent
 * @copyright 2014 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\flexContent\Control\Admin\Import;

use Silex\Application;
use phpManufaktur\Basic\Control\Pattern\Alert;
use phpManufaktur\flexContent\Data\Content\CategoryType as DataCategoryType;
use phpManufaktur\flexContent\Data\Content\Glossary as DataGlossary;
use phpManufaktur\flexContent\Data\Content\Content as DataContent;
use phpManufaktur\flexContent\Control\Configuration;
use Carbon\Carbon;
use phpManufaktur\flexContent\Data\Content\Category as DataCategory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class dbGlossary extends Alert
{
    protected static $usage = null;
    protected $DataCategoryType = null;
    protected $DataCategory = null;
    protected $DataGlossary = null;
    protected $DataContent = null;
    protected static $config = null;

    protected static $mime_types = array(
        'text/comma-separated-values',
        'text/csv',
        'application/csv',
        'application/excel',
        'application/vnd.ms-excel',
        'application/vnd.msexcel',
        'text/anytext',
        'text/plain'
    );

    protected static $csv_delimiter = ',';
    protected static $csv_enclosure = '"';
    protected static $csv_keys = array(
        'gl_id',
        'gl_item',
        'gl_sort',
        'gl_explain',
        'gl_type',
        'gl_link',
        'gl_target',
        'gl_group',
        'gl_status',
        'gl_update_when',
        'gl_update_by'
    );

    protected static $dbglossary_type = array(
        0 => 'undefined',
        1 => 'abbreviation',
        2 => 'acronym',
        3 => 'text',
        4 => 'link',
        5 => 'db_glossary',
        6 => 'html'
    );

    protected static $dbglossary_target = array(
        0 => '_self',
        1 => '_blank',
        2 => '_parent',
        3 => '_top'
    );

    /**
     * Initialize the class with the needed parameters
     *
     * @param Application $app
     */
    protected function initialize(Application $app)
    {
        parent::initialize($app);

        self::$usage = $this->app['request']->get('usage', 'framework');

        if (null !== ($locale = $this->app['session']->get('CMS_LOCALE'))) {
            // set the locale from the CMS locale
            $app['translator']->setLocale($locale);
        }

        $this->DataCategoryType = new DataCategoryType($app);
        $this->DataGlossary = new DataGlossary($app);
        $this->DataContent = new DataContent($app);
        $this->DataCategory = new DataCategory($app);

        $Config = new Configuration($app);
        self::$config = $Config->getConfiguration();
    }

    /**
     * Get the form for the CSV file upload
     *
     */
    protected function formUpload()
    {
        $cats = $this->DataCategoryType->selectCategoriesByType('GLOSSARY');
        $categories = array();
        if (is_array($cats)) {
            foreach ($cats as $cat) {
                $categories[$cat['category_id']] = $cat['category_name'];
            }
        }

        return $this->app['form.factory']->createBuilder('form')
            ->add('csv_file', 'file')
            ->add('category', 'choice', array(
                'choices' => $categories,
                'empty_value' => '- please select -',
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->getForm();
    }

    /**
     * Controller to start the CSV import from dbGlossary
     *
     * @param Application $app
     */
    public function Controller(Application $app)
    {
        $this->initialize($app);

        if (false === $this->DataCategoryType->selectCategoriesByType('GLOSSARY')) {
            $this->setAlert('Please create a flexContent category of type <var>GLOSSARY</var> before you import CSV data from dbGlossary.',
                array(), self::ALERT_TYPE_INFO);
        }

        $form = $this->formUpload();

        return $this->app['twig']->render($this->app['utils']->getTemplateFile(
            '@phpManufaktur/flexContent/Template', 'admin/import.dbglossary.twig'),
            array(
                'usage' => self::$usage,
                'alert' => $this->getAlert(),
                'form' => $form->createView(),
            ));
    }

    /**
     * Process the CSV file and execute the import
     *
     * @param string $csv_path
     * @param integer $category_id
     * @return boolean
     */
    protected function importCSV($csv_path, $category_id)
    {
        if (false === ($handle = @fopen($csv_path, 'r'))) {
            $this->setAlert('Got no file handle for <var>%file%</var>.',
                array('%file%' => $csv_path), self::ALERT_TYPE_DANGER);
            return false;
        }

        $key = array();
        $importCSV = array();
        $start = true;

        while (false !== ($csv_row = fgetcsv($handle, 1000, self::$csv_delimiter, self::$csv_enclosure))) {
            $record = array();

            for ($i=0; $i < count($csv_row); $i++) {
                if ($start) {
                    if (!in_array($csv_row[$i], self::$csv_keys)) {
                        // unknown key!
                        $this->setAlert('Unexpected structure of the dbGlossary CSV file!', array(), self::ALERT_TYPE_DANGER);
                        return false;
                    }
                    $key[$i] = $csv_row[$i];
                }
                else {
                    $record[$key[$i]] = $csv_row[$i];
                }
            }

            if ($start) {
                $start = false;
            }
            else {
                $importCSV[] = $record;
            }
        }

        fclose($handle);

        $CategoryType = $this->DataCategoryType->select($category_id);
        $language = $CategoryType['language'];

        $skipped_glossary_redirect = array();
        $skipped_glossary_exists = array();
        $skipped_glossary_permanentlink_exists = array();

        $added_glossary_item = array();

        foreach ($importCSV as $import) {
            if ($import['gl_type'] == 5) {
                $skipped_glossary_redirect[$import['gl_item']] = $import['gl_explain'];
                $this->app['monolog']->addDebug("[flexContent dbGlossary Import] Skipped dbGlossary redirect {$import['gl_item']} => {$import['gl_explain']}");
                continue;
            }

            // check unique identifier
            $glossary_unique = $this->app['utils']->specialCharsToAsciiChars($import['gl_item'], true);

            if (false !== ($content_id = $this->DataGlossary->existsUnique($glossary_unique))) {
                $skipped_glossary_exists[$import['gl_item']] = $content_id;
                $this->app['monolog']->addDebug("[flexContent dbGlossary Import] Skipped dbGlossary {$import['gl_item']} because entry already exists: $content_id");
                continue;
            }

            $permalink = $this->app['utils']->sanitizeLink($import['gl_item']);
            if ($this->DataContent->existsPermaLink($permalink, $language)) {
                $skipped_glossary_permanentlink_exists[$import['gl_item']] = $permalink;
                $this->app['monolog']->addDebug("[flexContent dbGlossary Import] Skipped dbGlossary {$import['gl_item']} because the desired permanent link $permalink already exists.");
            }

            $dt = Carbon::create();
            $dt->addHours(self::$config['content']['field']['breaking_to']['add']['hours']);
            $breaking_to = $dt->toDateTimeString();

            $dt = Carbon::create();
            $dt->endOfDay();
            $dt->addDays(self::$config['content']['field']['archive_from']['add']['days']);
            $archive_from = $dt->toDateTimeString();

            $content = array(
                'language' => $language,
                'title' => $import['gl_item'],
                'page_title' => $import['gl_item'],
                'description' => '',
                'keywords' => '',
                'permalink' => $permalink,
                'redirect_url' => $import['gl_link'],
                'redirect_target' => self::$dbglossary_target[$import['gl_target']],
                'publish_from' => $import['gl_update_when'],
                'breaking_to' => $breaking_to,
                'archive_from' => $archive_from,
                'status' => 'PUBLISHED',
                'teaser' => $import['gl_explain'],
                'teaser_image' => '',
                'content' => '',
                'rss' => 'NO',
                'author_username' => $this->app['account']->getUsername(),
                'update_username' => ''
            );

            // insert the content record
            $content_id = $this->DataContent->insert($content);

            // create the category record
            $category = array(
                'content_id' => $content_id,
                'category_id' => $category_id,
                'is_primary' => 1
            );
            $this->DataCategory->insert($category);

            // create the glossary record
            if ($import['gl_type'] == 1) {
                $glossary_type = 'ABBREVIATION';
            }
            elseif ($import['gl_type'] == 2) {
                $glossary_type = 'ACRONYM';
            }
            else {
                $glossary_type = 'KEYWORD';
            }

            $glossary = array(
                'content_id' => $content_id,
                'language' => $language,
                'glossary_type' => $glossary_type,
                'glossary_unique' => $glossary_unique
            );
            $this->DataGlossary->insert($glossary);

            $added_glossary_item[$import['gl_item']] = $content_id;
            $this->app['monolog']->addDebug("[flexContent dbGlossary Import] Import dbGlossary {$import['gl_item']} as flexContent ID $content_id");
        }

        if (count($added_glossary_item) == 0) {
            $this->setAlert('<p>No <em>dbGlossary</em> items imported.</p><p>Skipped <em>dbGlossary redirects</em>: %skipped_redirect% items,<br>Skipped already existing items: %skipped_existing%,<br>Skipped due <em>permanent link</em> conflict: %skipped_permanentlink%.</p><p>Check protocol for detailed information.</p>',
                array('%skipped_redirect%' => count($skipped_glossary_redirect),
                    '%skipped_existing%' => count($skipped_glossary_exists),
                    '%skipped_permanentlink%' => count($skipped_glossary_permanentlink_exists)
            ));
        }
        else {
            $this->setAlert('<p>Successful imported <strong>%items%</strong> <em>dbGlossary</em> items.</p><p>Skipped <em>dbGlossary redirects</em>: %skipped_redirect% items,<br>Skipped already existing items: %skipped_existing%,<br>Skipped due <em>permanent link</em> conflict: %skipped_permanentlink%.</p><p>Check protocol for detailed information.</p>',
                array('%items%' => count($added_glossary_item),
                    '%skipped_redirect%' => count($skipped_glossary_redirect),
                    '%skipped_existing%' => count($skipped_glossary_exists),
                    '%skipped_permanentlink%' => count($skipped_glossary_permanentlink_exists)
            ));
        }
        return true;
    }

    /**
     * Controller to execute the CSV import
     *
     * @param Application $app
     */
    public function ControllerExecute(Application $app)
    {
        $this->initialize($app);

        $form = $this->formUpload();
        $form->bind($this->app['request']);

        if ($form->isValid()) {
            $mime_type_ok = true;
            if (null === ($mime_type = $form['csv_file']->getData()->getMimeType())) {
                $this->setAlert('Oooops, can not evaluate the mime type of the uploaded file, abort submission.',
                    array(), self::ALERT_TYPE_DANGER);
                $mime_type_ok = false;
            }
            elseif (!in_array($mime_type, self::$mime_types)) {
                $this->setAlert('Oooops, can not evaluate the mime type of the uploaded file, abort submission.',
                    array(), self::ALERT_TYPE_DANGER);
                $mime_type_ok = false;
            }

            if ($mime_type_ok) {
                $filename = sha1(uniqid(mt_rand(), true));
                $filename = $filename.'.'.$form['csv_file']->getData()->guessExtension();

                $form['csv_file']->getData()->move(FRAMEWORK_TEMP_PATH, $filename);

                $this->importCSV(FRAMEWORK_TEMP_PATH.'/'.$filename, $form['category']->getData());
            }

        }
        else {
            // general error (timeout, CSFR ...)
            $this->setAlert('The form is not valid, please check your input and try again!', array(),
                self::ALERT_TYPE_DANGER, true, array('form_errors' => $form->getErrorsAsString(),
                    'method' => __METHOD__, 'line' => __LINE__));
        }

        $subRequest = Request::create('/flexcontent/editor/import/dbglossary', 'GET', array(
            'usage' => self::$usage
        ));
        return $app->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    }
}
