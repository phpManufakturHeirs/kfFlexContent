<?php

/**
 * flexContent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/flexContent
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\flexContent\Data\Content;

use Silex\Application;
use phpManufaktur\Contact\Control\Contact;

class Content
{
    protected $app = null;
    protected $EventData = null;
    protected $GlossaryData = null;
    protected static $table_name = null;

    /**
     * Constructor
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        self::$table_name = FRAMEWORK_TABLE_PREFIX.'flexcontent_content';
        $this->EventData = new Event($app);
        $this->GlossaryData = new Glossary($app);
    }

    /**
     * Create the table
     *
     * @throws \Exception
     */
    public function createTable()
    {
        $table = self::$table_name;
        $SQL = <<<EOD
    CREATE TABLE IF NOT EXISTS `$table` (
        `content_id` INT(11) NOT NULL AUTO_INCREMENT,
        `language` VARCHAR(2) NOT NULL DEFAULT 'EN',
        `title` VARCHAR(512) NOT NULL DEFAULT '',
        `page_title` VARCHAR(512) NOT NULL DEFAULT '',
        `description` VARCHAR(512) NOT NULL DEFAULT '',
        `keywords` VARCHAR(512) NOT NULL DEFAULT '',
        `permalink` VARCHAR(255) NOT NULL DEFAULT '',
        `redirect_url` TEXT NOT NULL,
        `redirect_target` ENUM('_blank','_self','_parent','_top') NOT NULL DEFAULT '_blank',
        `publish_from` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
        `breaking_to` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
        `archive_from` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
        `status` ENUM('UNPUBLISHED','PUBLISHED','BREAKING','HIDDEN','ARCHIVED','DELETED') NOT NULL DEFAULT 'UNPUBLISHED',
        `teaser` TEXT NOT NULL,
        `teaser_image` TEXT NOT NULL,
        `content` TEXT NOT NULL,
        `rss` ENUM('YES','NO') NOT NULL DEFAULT 'YES',
        `author_username` VARCHAR(64) NOT NULL DEFAULT '',
        `update_username` VARCHAR(64) NOT NULL DEFAULT '',
        `timestamp` TIMESTAMP,
        PRIMARY KEY (`content_id`)
        )
    COMMENT='The main table for flexContent'
    ENGINE=InnoDB
    AUTO_INCREMENT=1
    DEFAULT CHARSET=utf8
    COLLATE='utf8_general_ci'
EOD;
        try {
            $this->app['db']->query($SQL);
            $this->app['monolog']->addInfo('Created table '.$table, array(__METHOD__, __LINE__));
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Drop the table
     */
    public function dropTable()
    {
        $this->app['db.utils']->dropTable(self::$table_name);
    }

    /**
     * Get the ENUM values of field publish_type as associated array for
     * usage in form
     *
     * @return array
     */
    public function getStatusTypeValuesForForm()
    {
        $enums = $this->app['db.utils']->getEnumValues(self::$table_name, 'status');
        $result = array();
        foreach ($enums as $enum) {
            $result[$enum] = $this->app['utils']->humanize($enum);
        }
        return $result;
    }

    /**
     * Delete the content with the given content ID
     *
     * @param integer $content_id
     * @throws \Exception
     */
    public function delete($content_id)
    {
        try {
            $this->app['db']->delete(self::$table_name, array('content_id' => $content_id));
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Get the RSS Status Values as associated array usage in form
     *
     * @return array
     */
    public function getRSSValuesForForm()
    {
        $enums = $this->app['db.utils']->getEnumValues(self::$table_name, 'rss');
        $result = array();
        foreach ($enums as $enum) {
            $result[$enum] = $this->app['utils']->humanize($enum);
        }
        return $result;
    }

    /**
     * Get the ENUM values of field redirect_target as associated array for
     * usage in form
     *
     * @return array
     */
    public function getTargetTypeValuesForForm()
    {
        $enums = $this->app['db.utils']->getEnumValues(self::$table_name, 'redirect_target');
        $result = array();
        foreach ($enums as $enum) {
            $result[$enum] = $enum;
        }
        return $result;
    }

    /**
     * Replace CMS and framework URLs with placeholders
     *
     * @param string reference $content
     * @return string
     */
    protected function replaceURLwithPlaceholder(&$content)
    {
        $search = array(FRAMEWORK_URL, CMS_MEDIA_URL, CMS_URL);
        $replace = array('{flexContent:FRAMEWORK_URL}','{flexContent:CMS_MEDIA_URL}', '{flexContent:CMS_URL}');
        $content = str_replace($search, $replace, $content);
        return $content;
    }

    /**
     * Replace placeholders with the real CMS and framework URLs
     *
     * @param string reference $content
     * @return string
     */
    protected function replacePlaceholderWithURL(&$content)
    {
        $search = array('{flexContent:FRAMEWORK_URL}','{flexContent:CMS_MEDIA_URL}', '{flexContent:CMS_URL}');
        $replace = array(FRAMEWORK_URL, CMS_MEDIA_URL, CMS_URL);
        $content = str_replace($search, $replace, $content);
        return $content;
    }

    /**
     * Loop through the content record and prepare it for output.
     * Add also EVENT data if avaiable
     *
     * @param array $record
     * @return array
     */
    protected function prepareContent($record)
    {
        $content = array();
        if (is_array($record)) {
            foreach ($record as $key => $value) {
                $content[$key] = is_string($value) ? $this->app['utils']->unsanitizeText($value) : $value;
                if (($key == 'content') || ($key == 'teaser')) {
                    $content[$key] = $this->replacePlaceholderWithURL($content[$key]);
                }
                // is this an EVENT?
                if ($key == 'content_id') {
                    if ('EVENT' == $this->getContentType($value)) {
                        if (false !== ($event = $this->EventData->selectContentID($value))) {
                            // add the EVENT data to the content record
                            foreach ($event as $event_key => $event_value) {
                                if (in_array($event_key, array('event_date_from', 'event_date_to', 'event_id'))) {
                                    $content[$event_key] = $event_value;
                                }
                                elseif (in_array($event_key, array('event_organizer', 'event_location'))) {
                                    $content[$event_key]['contact_id'] = $event_value;
                                    $ContactData = new Contact($this->app);
                                    if (($event_value > 0) && (false !== ($contact = $ContactData->selectOverview($event_value)))) {
                                        // add the contact overview
                                        $content[$event_key] = $contact;
                                    }
                                }
                            }
                        }
                    }
                    elseif ('GLOSSARY' == $this->getContentType($value)) {
                        if (false !== ($glossary = $this->GlossaryData->selectContentID($value))) {
                            // add the GLOSSARY data to the content record
                            $content['glossary_type'] = $glossary['glossary_type'];
                            $content['glossary_unique'] = $glossary['glossary_unique'];
                        }
                    }
                }
            }
        }
        return $content;
    }

    /**
     * Insert a new flexContent record
     *
     * @param array $data
     * @param integer reference $content_id
     * @throws \Exception
     * @return integer $content_id
     */
    public function insert($data, &$content_id=-1)
    {
        try {
            $insert = array();
            foreach ($data as $key => $value) {
                if (($key == 'content_id') || ($key == 'timestamp')) {
                    continue;
                }
                if (($key == 'content') || ($key == 'teaser')) {
                    // replace all internal URL's with a placeholder
                    $value = $this->replaceURLwithPlaceholder($value);
                }
                if (($key == 'title') || ($key == 'description') || ($key == 'keywords')) {
                    // remove HTML tags
                    $value = trim(strip_tags($value));
                }
                $insert[$key] = is_string($value) ? $this->app['utils']->sanitizeText($value) : $value;
            }
            $not_null = array('redirect_url', 'teaser', 'teaser_image', 'content');
            foreach ($not_null as $field) {
                if (!isset($insert[$field]) || is_null($insert[$field])) {
                    $insert[$field] = '';
                }
            }
            $this->app['db']->insert(self::$table_name, $insert);
            $content_id = $this->app['db']->lastInsertId();
            return $content_id;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Update an existing flexContent record
     *
     * @param array $data
     * @param integer $content_id
     * @throws \Exception
     */
    public function update($data, $content_id)
    {
        try {
            $update = array();
            foreach ($data as $key => $value) {
                if (($key == 'content_id') || ($key == 'timestamp')) {
                    continue;
                }
                if (($key == 'content') || ($key == 'teaser')) {
                    $value = $this->replaceURLwithPlaceholder($value);
                }
                if (($key == 'title') || ($key == 'description') || ($key == 'keywords')) {
                    // remove HTML tags
                    $value = trim(strip_tags($value));
                }
                $update[$this->app['db']->quoteIdentifier($key)] = is_string($value) ? $this->app['utils']->sanitizeText($value) : $value;
            }
            $this->app['db']->update(self::$table_name, $update, array('content_id' => $content_id));
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select a flexContent record by the given content ID
     *
     * @param integer $content_id
     * @throws \Exception
     * @return Ambigous <boolean, multitype:unknown >
     */
    public function select($content_id, $language=null)
    {
        try {
            if (is_string($language)) {
                $SQL = "SELECT * FROM `".self::$table_name."` WHERE `content_id`='$content_id' AND `language`='$language'";
            }
            else {
                $SQL = "SELECT * FROM `".self::$table_name."` WHERE `content_id`='$content_id'";
            }
            $result = $this->app['db']->fetchAssoc($SQL);

            // prepare the content for output
            $content = $this->prepareContent($result);

            return (!empty($content)) ? $content : false;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Get the columns of the flexContent table
     *
     * @param string $table
     * @throws \Exception
     * @return array
     */
    public function getColumns()
    {
        return $this->app['db.utils']->getColumns(self::$table_name);
    }

    /**
     * Select a list from the flexContent table in paging view
     *
     * @param integer $limit_from start selection at position
     * @param integer $rows_per_page select max. rows per page
     * @param array $select_status tags, i.e. array('UNPUBLISHED','PUBLISHED')
     * @param array $order_by fields to order by
     * @param string $order_direction 'ASC' (default) or 'DESC'
     * @param array $columns the columns to select
     * @param integer $category_id the category ID to select, default null
     * @throws \Exception
     * @return array selected records
     */
    public function selectList($limit_from, $rows_per_page, $select_status=null,
        $order_by=null, $order_direction='ASC', $columns, $category_id=null)
    {
        try {
            $content = self::$table_name;
            $category = FRAMEWORK_TABLE_PREFIX.'flexcontent_category';
            $category_type = FRAMEWORK_TABLE_PREFIX.'flexcontent_category_type';

            $SQL = "SELECT * FROM `$content` ".
                "LEFT JOIN `$category` ON `$category`.`content_id`=`$content`.`content_id` ".
                "LEFT JOIN `$category_type` ON `$category_type`.`category_id`=`$category`.`category_id` ".
                "WHERE `$category`.`is_primary`=1";

            if (!is_null($category_id)) {
                if (is_array($category_id)) {
                    $in_categories = '('.implode(',', $category_id).')';
                    $SQL .= " AND `$category`.`category_id` IN $in_categories";
                }
                else {
                    $SQL .= " AND `$category`.`category_id`=$category_id";
                }
            }

            if (is_array($select_status) && !empty($select_status)) {
                $SQL .= " AND ";
                $use_status = false;
                if (is_array($select_status) && !empty($select_status)) {
                    $use_status = true;
                    $SQL .= '(';
                    $start = true;
                    foreach ($select_status as $stat) {
                        if (!$start) {
                            $SQL .= " OR ";
                        }
                        else {
                            $start = false;
                        }
                        $SQL .= "`status`='$stat'";
                    }
                    $SQL .= ')';
                }
            }
            $SQL .= " GROUP BY `$content`.content_id ";
            if (is_array($order_by) && !empty($order_by)) {
                $SQL .= " ORDER BY ";
                $start = true;
                foreach ($order_by as $by) {
                    if ($by == 'content_id') {
                        $by = "`$content`.content_id";
                    }
                    if (!$start) {
                        $SQL .= ", ";
                    }
                    else {
                        $start = false;
                    }
                    $SQL .= "$by";
                }
                $SQL .= " $order_direction";
            }

            if ($limit_from < 0) {
                $limit_from = 0;
            }
            $SQL .= " LIMIT $limit_from, $rows_per_page";
            $results = $this->app['db']->fetchAll($SQL);

            $contents = array();
            foreach ($results as $result) {
                // prepare the content for output
                $contents[] = $this->prepareContent($result);
            }
            return $contents;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Count the records in the table
     *
     * @param array $status flags, i.e. array('UNPUBLISHED','PUBLISHED')
     * @throws \Exception
     * @return integer number of records
     */
    public function count($status=null, $category_id=null)
    {
        try {
            $category_table = FRAMEWORK_TABLE_PREFIX.'flexcontent_category';
            $content_table = self::$table_name;

            if (is_null($category_id)) {
                $SQL = "SELECT COUNT(*) FROM `$content_table`";
            }
            else {
                $SQL = "SELECT COUNT(`$content_table`.`content_id`) FROM `$content_table` ".
                    "LEFT JOIN `$category_table` ON `$category_table`.`content_id`=`$content_table`.`content_id` WHERE ";
                if (is_array($category_id)) {
                    $in_categories = '('.implode(',', $category_id).')';
                    $SQL .= "`category_id` IN $in_categories";
                }
                else {
                    $SQL .= "`category_id`=$category_id";
                }
            }

            if (is_array($status) && !empty($status)) {
                if (is_null($category_id)) {
                    $SQL .= " WHERE ";
                }
                else {
                    $SQL .= " AND ";
                }
                $use_status = false;
                if (is_array($status) && !empty($status)) {
                    $use_status = true;
                    $SQL .= '(';
                    $start = true;
                    foreach ($status as $stat) {
                        if (!$start) {
                            $SQL .= " OR ";
                        }
                        else {
                            $start = false;
                        }
                        $SQL .= "`status`='$stat'";
                    }
                    $SQL .= ')';
                }
            }
            return $this->app['db']->fetchColumn($SQL);
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Check if a permalink already exists
     *
     * @param link $permalink
     * @throws \Exception
     * @return boolean
     */
    public function existsPermaLink($permalink, $language)
    {
        try {
            $SQL = "SELECT `permalink` FROM `".self::$table_name."` WHERE `permalink`='$permalink' ".
                "AND `language`='$language' AND `status`!='DELETED'";
            $result = $this->app['db']->fetchColumn($SQL);
            return (strtolower($result) === strtolower($permalink));
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Count PermaLinks which starts LIKE the given $this
     *
     * @param string $this
     * @throws \Exception
     */
    public function countPermaLinksLikeThis($permalink, $language)
    {
        try {
            $SQL = "SELECT COUNT(`permalink`) FROM `".self::$table_name."` WHERE `permalink` LIKE '$permalink%' ".
                "AND `language`='$language' `status`!='DELETED'";
            return $this->app['db']->fetchColumn($SQL);
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select the Content ID by the given PermanentLink
     *
     * @param string $permalink
     * @throws \Exception
     * @return Ambigous <boolean, array>
     */
    public function selectContentIDbyPermaLink($permalink, $language=null)
    {
        try {
            if (is_null($language)) {
                $SQL = "SELECT `content_id` FROM `".self::$table_name."` WHERE `permalink`='$permalink'";
            }
            else {
                $SQL = "SELECT `content_id` FROM `".self::$table_name."` WHERE `permalink`='$permalink' AND `language`='$language'";
            }
            $result = $this->app['db']->fetchColumn($SQL);
            return ($result > 0) ? $result : false;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select a permanent link for the given content ID. The function return a
     * array with the permalink and the language assigned to this content
     *
     * @param integer $content_id
     * @throws \Exception
     * @return Ambigous <boolean, array>
     */
    public function selectPermaLinkByContentID($content_id)
    {
        try {
            $SQL = "SELECT `permalink`, `language` FROM `".self::$table_name."` WHERE `content_id`='$content_id'";
            $result = $this->app['db']->fetchAssoc($SQL);
            return (isset($result['permalink'])) ? $result : false;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select the content in previous or next order to the given content ID.
     *
     * @param integer $content_id
     * @throws \Exception
     * @return boolean|Ambigous <boolean, array>
     */
    protected function selectPreviousOrNextContentForID($content_id, $select_previous=true, $language='EN')
    {
        try {
            $category_table = FRAMEWORK_TABLE_PREFIX.'flexcontent_category';
            $content_table = self::$table_name;
            // first get the primary category of $content_id
            $SQL = "SELECT `category_id` FROM `$category_table` WHERE `content_id`='$content_id' AND `is_primary`='1'";
            $category_id = $this->app['db']->fetchColumn($SQL);
            if ($category_id < 1) {
                // no hit ...
                $this->app['monolog']->addDebug("Can't find the primary category ID for content ID $content_id.", array(__METHOD__, __FILE__));
                return false;
            }
            // get the publishing date of $content_id
            $SQL = "SELECT `publish_from` FROM `$content_table` WHERE `content_id`='$content_id'";
            $published_from = $this->app['db']->fetchColumn($SQL);
            if (empty($published_from)) {
                // invalid record?
                $this->app['monolog']->addDebug("Can't select the `publish_from` date for content ID $content_id", array(__METHOD__, __LINE__));
                return false;
            }
            // now select the content record
            if ($select_previous) {
                $select = '>=';
                $direction = 'DESC';
            }
            else {
                $select = '<=';
                $direction = 'ASC';
            }

            $SQL = "SELECT * FROM `$content_table` ".
                "LEFT JOIN `$category_table` ON `$category_table`.`content_id`=`$content_table`.`content_id` ".
                "WHERE `$content_table`.`content_id` != $content_id AND `category_id`=$category_id AND `is_primary`=1 AND ".
                "'$published_from' $select `publish_from` AND `status` != 'UNPUBLISHED' AND `status` != 'DELETED' AND ".
                "`language`='$language' ORDER BY `publish_from` $direction LIMIT 1";

            $result = $this->app['db']->fetchAssoc($SQL);

            // prepare the content for output
            $content = $this->prepareContent($result);

            return (!empty($content)) ? $content : false;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select the content in previous order to the given content ID.
     *
     * @param integer $content_id
     * @throws \Exception
     * @return boolean|Ambigous <boolean, array>
     */
    public function selectPreviousContentForID($content_id, $language='EN')
    {
        return $this->selectPreviousOrNextContentForID($content_id, true, $language);
    }

    /**
     * Select the content in next order to the given content ID.
     *
     * @param integer $content_id
     * @throws \Exception
     * @return boolean|Ambigous <boolean, array>
     */
    public function selectNextContentForID($content_id, $language='EN')
    {
        return $this->selectPreviousOrNextContentForID($content_id, false, $language);
    }

    /**
     * Select the contents for the given Category ID.
     * Order the results by status (BREAKING, PUBLISHED ...) and publishing date descending
     *
     * @param integer $category_id
     * @param array $status default = PUBLISHED, BREAKING
     * @param number $limit default = 100
     * @param string $order_by default 'publish_from'
     * @param string $order_direction default 'DESC'
     * @param array $exclude_ids default null
     * @throws \Exception
     * @return Ambigous <boolean, array>
     */
    public function selectContentsByCategoryID($category_id, $status=array('PUBLISHED','BREAKING'),
        $limit=100, $order_by='publish_from', $order_direction='DESC', $exclude_ids=null)
    {
        try {
            $content_table = self::$table_name;
            $category_table = FRAMEWORK_TABLE_PREFIX.'flexcontent_category';
            $in_status = "('".implode("','", $status)."')";

            $SQL = "SELECT * FROM `$content_table` ".
                "LEFT JOIN `$category_table` ON `$category_table`.`content_id`=`$content_table`.`content_id` WHERE ";

            if (!is_null($exclude_ids) && is_array($exclude_ids) && !empty($exclude_ids)) {
                $in_exclude = "('".implode("','", $exclude_ids)."')";
                $SQL .= "`$content_table`.`content_id` NOT IN $in_exclude AND ";
            }

            if (in_array($order_by, array('publish_from','breaking_to','archive_from','timestamp'))) {
                $SQL .= "`category_id`=$category_id AND `status` IN $in_status ORDER BY ".
                    "FIELD (`status`,'BREAKING','PUBLISHED','HIDDEN','ARCHIVED','UNPUBLISHED','DELETED'), ".
                    "`$order_by` $order_direction";
            }
            else {
                $SQL .= "`category_id`=$category_id AND `status` IN $in_status ORDER BY ".
                    "`$content_table`.`$order_by` $order_direction";
            }

            if (!is_null($limit)) {
                $SQL .= " LIMIT $limit";
            }

            $results = $this->app['db']->fetchAll($SQL);
            $contents = array();
            foreach ($results as $result) {
                // prepare the content for output
                $contents[] = $this->prepareContent($result);
            }
            return (!empty($contents)) ? $contents : false;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select content by the given TAG ID, depending by status
     *
     * @param integer $tag_id
     * @param array $status default 'PUBLISHED', 'BREAKING'
     * @param number $limit default 100
     * @param string $order_by default null
     * @param string $order_direction default null
     * @param integer $excluded_ids default null
     * @throws \Exception
     * @return Ambigous <boolean, array>
     */
    public function selectContentsByTagID($tag_id, $status=array('PUBLISHED','BREAKING'),
        $limit=100, $order_by=null, $order_direction='ASC', $excluded_ids=null)
    {
        try {
            $content_table = self::$table_name;
            $tag_table = FRAMEWORK_TABLE_PREFIX.'flexcontent_tag';
            $in_status = "('".implode("','", $status)."')";
            $SQL = "SELECT * FROM `$content_table` ".
                "LEFT JOIN `$tag_table` ON `$tag_table`.`content_id`=`$content_table`.`content_id` ".
                "WHERE `tag_id`=$tag_id AND `status` IN $in_status ";

            if (!is_null($excluded_ids)) {
                $in_excludes = "('".implode("','", $excluded_ids)."')";
                $SQL .= " AND `$content_table`.`content_id` NOT IN $in_excludes ";
            }

            if (!is_null($order_by)) {
                if (in_array($order_by, array('publish_from','breaking_to','archive_from','timestamp'))) {
                    $SQL .= "ORDER BY FIELD (`status`,'BREAKING','PUBLISHED','HIDDEN','ARCHIVED','UNPUBLISHED','DELETED') $order_direction, ".
                        "`$content_table`.`$order_by` $order_direction ";
                }
                else {
                    $SQL .= "ORDER BY `$content_table`.`$order_by` $order_direction ";
                }
            }
            else {
                $SQL .= "ORDER BY `position` ASC, ".
                    "FIELD (`status`,'BREAKING','PUBLISHED','HIDDEN','ARCHIVED','UNPUBLISHED','DELETED'), `publish_from` DESC ";
            }

            $SQL .= "LIMIT $limit";
            $results = $this->app['db']->fetchAll($SQL);
            $contents = array();
            if (is_array($results)) {
                foreach ($results as $result) {
                    // prepare the content for output
                    $contents[] = $this->prepareContent($result);
                }
            }
            return (!empty($contents)) ? $contents : false;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * CMS search response
     *
     * @param integer $category_id
     * @param array $words the search words
     * @param boolean $or combine the $words with OR or with AND
     * @param array $status
     * @throws \Exception
     * @return Ambigous <boolean, multitype:string >
     */
    public function cmsSearch($category_id, $words, $or=true, $status)
    {
        try {
            $category_table = FRAMEWORK_TABLE_PREFIX.'flexcontent_category';
            $content_table = self::$table_name;

            $search = '';
            foreach ($words as $word) {
                if (!empty($search)) {
                    $search .= $or ? ' OR ' : ' AND ';
                }
                $search .= "(`title` LIKE '%$word%' OR `teaser` LIKE '%$word%' OR ".
                    "`content` LIKE '%$word%' OR `description` LIKE '%$word%' OR `keywords` LIKE '%$word%')";
            }

            $in_status = "('".implode("','", $status)."')";

            $SQL = "SELECT * FROM `$content_table` ".
                "LEFT JOIN `$category_table` ON `$category_table`.`content_id`=`$content_table`.`content_id` ".
                "WHERE `category_id`=$category_id AND `is_primary`=1 AND ($search) AND `status` IN $in_status ORDER BY ".
                "FIELD (`status`,'BREAKING','PUBLISHED','HIDDEN','ARCHIVED','UNPUBLISHED','DELETED'), ".
                "`publish_from` DESC";

            $result = $this->app['db']->fetchAll($SQL);
            $contents = array();
            for ($i=0; $i < sizeof($result); $i++) {
                $excerpt = strip_tags($this->app['utils']->unsanitizeText($result[$i]['title']));
                $excerpt .= '.'.strip_tags($this->app['utils']->unsanitizeText($result[$i]['teaser']));
                $excerpt .= '.'.strip_tags($this->app['utils']->unsanitizeText($result[$i]['content']));
                $excerpt .= '.'.strip_tags($this->app['utils']->unsanitizeText($result[$i]['description']));
                $excerpt .= '.'.strip_tags($this->app['utils']->unsanitizeText($result[$i]['keywords']));
                $result[$i]['excerpt'] = $excerpt;
                $contents[] = $result[$i];
            }
            return (!empty($contents)) ? $contents : false;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select a list of contents configured by parameters
     *
     * @param string $language
     * @param integer $limit max. number of hits
     * @param array $categories query only this categories
     * @param array $categories_exclude don't query this categories
     * @param array $status status of contents to list
     * @param string $order_by given field(s), separated by comma
     * @param string $order_direction default 'DESC',
     * @param string $category_type default 'DEFAULT'
     * @param integer $paging_from default 0
     * @param integer $paging_limit default 0
     * @param array $exclude_ids default null
     * @return boolean|array
     */
    public function selectContentList($language, $limit=100, $categories=array(),
        $categories_exclude=array(), $status=array('PUBLISHED','BREAKING','HIDDEN','ARCHIVED'),
        $order_by='publish_from', $order_direction='DESC', $category_type='DEFAULT',
        $paging_from=0, $paging_limit=0, $exclude_ids=null)
    {
        $content_table = self::$table_name;
        $category_table = FRAMEWORK_TABLE_PREFIX.'flexcontent_category';
        $category_type_table = FRAMEWORK_TABLE_PREFIX.'flexcontent_category_type';

        $in_status = "('".implode("','", $status)."')";

        $SQL = "SELECT * FROM `$content_table` ".
            "LEFT JOIN `$category_table` ON `$category_table`.`content_id`=`$content_table`.`content_id` ".
            "LEFT JOIN `$category_type_table` ON `$category_type_table`.`category_id`=`$category_table`.`category_id` ".
            "WHERE `$content_table`.`language`='$language' ";

        if (!is_null($exclude_ids) && is_array($exclude_ids) && !empty($exclude_ids)) {
            // exclude the given content IDs
            $in_exclude = "('".implode("','", $exclude_ids)."')";
            $SQL .= "AND `$content_table`.`content_id` NOT IN $in_exclude ";
        }

        if (!empty($categories)) {
            $cats = "('".implode("','", $categories)."')";
            $SQL .= "AND `$category_table`.category_id IN $cats ";
        }
        elseif (!empty($categories_exclude)) {
            $categories = "('".implode("','", $categories_exclude)."')";
            $SQL .= "AND `$category_table`.category_id NOT IN $categories ";
        }

        if ($category_type != 'DEFAULT') {
            $SQL .= "AND `$category_table`.`is_primary`='1' AND `$category_type_table`.`category_type`='$category_type' ";
        }

        // and the rest - GROUP BY prevents duplicate entries!
        $order_table = (in_array($order_by, $this->getColumns())) ? $content_table : $category_table;
        $SQL .= "AND `status` IN $in_status GROUP BY `$content_table`.`content_id` ORDER BY ";

        if (in_array($order_by, array('publish_from','breaking_to','archive_from','timestamp'))) {
            $SQL .= "FIELD (`status`,'BREAKING','PUBLISHED','HIDDEN','ARCHIVED','UNPUBLISHED','DELETED') $order_direction, ".
                "`$order_table`.`$order_by` $order_direction";
        }
        else {
            $SQL .= "`$order_table`.`$order_by` $order_direction";
        }

        if (!is_null($limit) && ($paging_from == 0) && ($paging_limit == 0)) {
            $SQL .= " LIMIT $limit";
        }
        elseif (!is_null($limit)) {
            $SQL .= " LIMIT $paging_from, $paging_limit";
        }
        $results = $this->app['db']->fetchAll($SQL);

        $list = array();
        foreach ($results as $result) {
            // prepare the content for output
            $list[] = $this->prepareContent($result);
        }
        return (!empty($list)) ? $list : false;
    }

    /**
     * Create a content link list for the usage in the toolbar of the CKEditor
     *
     * @param string $language
     * @param array $status
     * @throws \Exception
     * @return Ambigous <boolean, array>
     * @see \phpManufaktur\CKEditor\Control\flexContentLink
     */
    public function SelectContentLinkList($language=null, $status=array('PUBLISHED','BREAKING','HIDDEN','ARCHIVED'))
    {
        try {
            $content = self::$table_name;
            $in_status = "('".implode("','", $status)."')";

            if (is_null($language)) {
                $SQL = "SELECT * FROM `$content` WHERE `status` IN $in_status ORDER BY `title` ASC";
            }
            else {
                $SQL = "SELECT * FROM `$content` WHERE `language`='$language' AND `status` IN $in_status ORDER BY `title` ASC";
            }
            $results = $this->app['db']->fetchAll($SQL);
            $list = array();
            foreach ($results as $result) {
                $item = array();
                foreach ($result as $key => $value) {
                    if (!in_array($key, array('description','keywords','teaser','teaser_image','content','rss','update_username'))) {
                        $item[$key] = (is_string($value)) ? $this->app['utils']->unsanitizeText($value) : $value;
                    }
                }
                $list[] = $item;
            }
            return (!empty($list)) ? $list : false;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }


    /**
     * Search the TERM within the CONTENT records
     *
     * @param string $search_term
     * @param array $order_by fields to order the record
     * @param string $order_direction default DESC
     * @param string $status default DELETED
     * @param string $status_operator default !=
     * @throws \Exception
     * @return Ambigous <boolean, array>
     */
    public function SearchContent($search_term, $order_by=array('content_id'), $order_direction='DESC', $status='DELETED', $status_operator='!=')
    {
        try {
            $SQL = "SELECT * FROM `".self::$table_name."` WHERE (";
            $search = trim($search_term);
            $search_array = array();
            if (strpos($search, ' ')) {
                $dummy = explode(' ', $search_term);
                foreach ($dummy as $item) {
                    $search_array[] = trim($item);
                }
            }
            else {
                $search_array[] = trim($search_term);
            }
            $start = true;
            $skipped = false;
            foreach ($search_array as $search) {
                if (!$skipped) {
                    if ($start) {
                        $SQL .= "(";
                        $start = false;
                    }
                    elseif (strtoupper($search) == 'AND') {
                        $SQL .= ") AND (";
                        $skipped = true;
                        continue;
                    }
                    elseif (strtoupper($search) == 'NOT') {
                        $SQL .= ") AND NOT (";
                        $skipped = true;
                        continue;
                    }
                    elseif (strtoupper($search) == 'OR') {
                        $SQL .= ") OR (";
                        $skipped = true;
                        continue;
                    }
                    else {
                        $SQL .= ") OR (";
                    }
                }
                else {
                    $skipped = false;
                }
                $SQL .= "`title` LIKE '%$search%' OR "
                ."`description` LIKE '%$search%' OR "
                ."`keywords` LIKE '%$search%' OR "
                ."`teaser` LIKE '%$search%' OR "
                ."`content` LIKE '%$search%'";
            }
            $SQL .= ")) AND ";

            $in_order_by = "'".implode("','", $order_by)."'";

            $SQL .= "`status` $status_operator '$status'";// ORDER BY $in_order_by $order_direction";


            if (is_array($order_by) && !empty($order_by)) {
                $SQL .= " ORDER BY ";
                $start = true;
                foreach ($order_by as $by) {
                    if (!$start) {
                        $SQL .= ", ";
                    }
                    else {
                        $start = false;
                    }
                    $SQL .= "$by";
                }
                $SQL .= " $order_direction";
            }

            $results = $this->app['db']->fetchAll($SQL);

            $contents = array();
            foreach ($results as $key => $value) {
                $contents[$key] = is_string($value) ? $this->app['utils']->unsanitizeText($value) : $value;
            }
            return (!empty($contents)) ? $contents : false;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Get the content TYPE for the given content ID
     *
     * @param integer $content_id
     * @throws \Exception
     * @return Ambigous <boolean, string>
     */
    public function getContentType($content_id)
    {
        try {
            $content = self::$table_name;
            $category = FRAMEWORK_TABLE_PREFIX.'flexcontent_category';
            $category_type = FRAMEWORK_TABLE_PREFIX.'flexcontent_category_type';

            $SQL = "SELECT `category_type` FROM `$category_type` ".
                "LEFT JOIN `$category` ON `$category`.`category_id`=`$category_type`.`category_id` ".
                "LEFT JOIN `$content` ON `$content`.`content_id`=`$category`.`content_id` ".
                "WHERE `$content`.`content_id`=$content_id";

            $result = $this->app['db']->fetchColumn($SQL);
            return (!is_null($result)) ? $result : false;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Update all STATUS information
     *
     * @throws \Exception
     */
    public function autoUpdateStatus()
    {
        try {
            // first step: check if BREAKING must be changed to PUBLISHED
            $now = date('Y-m-d H:i:s');
            $SQL = "UPDATE `".self::$table_name."` SET `status`='PUBLISHED' WHERE `status`='BREAKING' AND ".
                "`breaking_to` < '$now'";
            $this->app['db']->query($SQL);
            // second step: check if articles should  be moved to ARCHIVED
            $SQL = "UPDATE `".self::$table_name."` SET `status`='ARCHIVED'  WHERE `status` IN ('PUBLISHED','BREAKING','HIDDEN') ".
                "AND `archive_from` < '$now'";
            $this->app['db']->query($SQL);
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select the title for the given content ID
     *
     * @param integer $content_id
     * @throws \Exception
     */
    public function selectTitleByID($content_id)
    {
        try {
            $SQL = "SELECT `title` FROM `".self::$table_name."` WHERE `content_id`=$content_id";
            $title = $this->app['db']->fetchColumn($SQL);
            return $this->app['utils']->unsanitizeText($title);
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }
}
