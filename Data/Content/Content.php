<?php

/**
 * flexContent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://addons.phpmanufaktur.de/flexContent
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\flexContent\Data\Content;

use Silex\Application;

class Content
{
    protected $app = null;
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
            $this->app['monolog']->addInfo("Created table 'flexcontent_content'", array(__METHOD__, __LINE__));
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
            $result[$enum] = $enum;
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
            $content = array();
            if (is_array($result)) {
                foreach ($result as $key => $value) {
                    $content[$key] = is_string($value) ? $this->app['utils']->unsanitizeText($value) : $value;
                    if (($key == 'content') || ($key == 'teaser')) {
                        $content[$key] = $this->replacePlaceholderWithURL($content[$key]);
                    }
                }
            }
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
     * @throws \Exception
     * @return array selected records
     */
    public function selectList($limit_from, $rows_per_page, $select_status=null, $order_by=null, $order_direction='ASC', $columns)
    {
        try {
            $content = self::$table_name;
            $category = FRAMEWORK_TABLE_PREFIX.'flexcontent_category';
            $category_type = FRAMEWORK_TABLE_PREFIX.'flexcontent_category_type';
            $SQL = "SELECT * FROM `$content`, `$category`, `$category_type` ";
            $SQL .= "WHERE `$category`.content_id=`$content`.content_id AND `$category`.is_primary=1 AND `$category`.category_id=`$category_type`.category_id";

            if (is_array($select_status) && !empty($select_status)) {
                //$SQL .= " WHERE ";
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
            $SQL .= " LIMIT $limit_from, $rows_per_page";
            $results = $this->app['db']->fetchAll($SQL);

            $contents = array();
            foreach ($results as $result) {
                $content = array();
                foreach ($columns as $column) {
                    foreach ($result as $key => $value) {
                        if ($key == $column) {
                            $content[$key] = is_string($value) ? $this->app['utils']->unsanitizeText($value) : $value;
                            if (($key == 'content') || ($key == 'teaser')) {
                                $content[$key] = $this->replacePlaceholderWithURL($content[$key]);
                            }
                        }
                    }
                }
                $contents[] = $content;
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
    public function count($status=null)
    {
        try {
            $SQL = "SELECT COUNT(*) FROM `".self::$table_name."`";
            if (is_array($status) && !empty($status)) {
                $SQL .= " WHERE ";
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
            $SQL = "SELECT `permalink` FROM `".self::$table_name."` WHERE `permalink`='$permalink' AND `language`='$language'";
            $result = $this->app['db']->fetchColumn($SQL);
            return ($result == $permalink);
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
            $SQL = "SELECT COUNT(`permalink`) FROM `".self::$table_name."` WHERE `permalink` LIKE '$permalink%' AND `language`='$language'";
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

            $SQL = "SELECT * FROM $category_table, $content_table WHERE $category_table.content_id=$content_table.content_id AND $content_table.content_id != '$content_id' AND ".
                "category_id='$category_id' AND is_primary=1 AND '$published_from' $select `publish_from` AND status !='UNPUBLISHED' AND status != 'DELETED' ".
                "AND `language`='$language' ORDER BY publish_from $direction LIMIT 1";

            $result = $this->app['db']->fetchAssoc($SQL);
            $content = array();
            if (is_array($result)) {
                foreach ($result as $key => $value) {
                    $content[$key] = (is_string($value)) ? $this->app['utils']->unsanitizeText($value) : $value;
                    if (($key == 'content') || ($key == 'teaser')) {
                        $content[$key] = $this->replacePlaceholderWithURL($content[$key]);
                    }
                }
            }
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
     * @throws \Exception
     * @return Ambigous <boolean, array>
     */
    public function selectContentsByCategoryID($category_id, $status=array('PUBLISHED','BREAKING'), $limit=100, $order_by='publish_from', $order_direction='DESC')
    {
        try {
            $content_table = self::$table_name;
            $category_table = FRAMEWORK_TABLE_PREFIX.'flexcontent_category';
            $in_status = "('".implode("','", $status)."')";
            if (in_array($order_by, array('publish_from','breaking_to','archive_from','timestamp'))) {
                $SQL = "SELECT * FROM `$category_table`, `$content_table` WHERE $content_table.content_id = $category_table.content_id ".
                    "AND `category_id`=$category_id AND `status` IN $in_status ORDER BY ".
                    "FIELD (`status`,'BREAKING','PUBLISHED','HIDDEN','ARCHIVED','UNPUBLISHED','DELETED'), ".
                    "`$order_by` $order_direction LIMIT $limit";
            }
            else {
                $SQL = "SELECT * FROM `$category_table`, `$content_table` WHERE $content_table.content_id = $category_table.content_id ".
                    "AND `category_id`=$category_id AND `status` IN $in_status ORDER BY ".
                    "`$content_table`.`$order_by` $order_direction LIMIT $limit";
            }
            $results = $this->app['db']->fetchAll($SQL);
            $contents = array();
            foreach ($results as $result) {
                $content = array();
                foreach ($result as $key => $value) {
                    $content[$key] = is_string($value) ? $this->app['utils']->unsanitizeText($value) : $value;
                    if (($key == 'content') || ($key == 'teaser')) {
                        $content[$key] = $this->replacePlaceholderWithURL($content[$key]);
                    }
                }
                $contents[] = $content;
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
     * @param integer $status
     * @param number $limit
     * @throws \Exception
     * @return Ambigous <boolean, array>
     */
    public function selectContentsByTagID($tag_id, $status=array('PUBLISHED','BREAKING'), $limit=100)
    {
        try {
            $content_table = self::$table_name;
            $tag_table = FRAMEWORK_TABLE_PREFIX.'flexcontent_tag';
            $in_status = "('".implode("','", $status)."')";
            $SQL = "SELECT * FROM `$tag_table`, `$content_table` WHERE $content_table.content_id = $tag_table.content_id ".
                "AND `tag_id`=$tag_id AND `status` IN $in_status ORDER BY `position` ASC, ".
                "FIELD (`status`,'BREAKING','PUBLISHED','HIDDEN','ARCHIVED','UNPUBLISHED','DELETED'), `publish_from` DESC ".
                "LIMIT $limit";
            $results = $this->app['db']->fetchAll($SQL);
            $contents = array();
            foreach ($results as $result) {
                $content = array();
                foreach ($result as $key => $value) {
                    $content[$key] = is_string($value) ? $this->app['utils']->unsanitizeText($value) : $value;
                    if (($key == 'content') || ($key == 'teaser')) {
                        $content[$key] = $this->replacePlaceholderWithURL($content[$key]);
                    }
                }
                $contents[] = $content;
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

            $SQL = "SELECT * FROM `$category_table`, `$content_table` WHERE ".
                "$category_table.content_id=$content_table.content_id AND `category_id`=$category_id ".
                "AND `is_primary`=1 AND ($search) AND `status` IN $in_status ORDER BY ".
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

    /*
     * Seem's to be no longer needed ...

    /**
     * Select a list of contents configured by parameters
     *
     * @param string $language
     * @param integer $limit max. number of hits
     * @param array $categories query only this categories
     * @param array $categories_exclude don't query this categories
     * @param array $status status of contents to list
     * @param string $order_by given field(s), separated by comma
     * @param string $order_direction default 'DESC'
     * @return boolean|array
     */
    public function selectContentList($language, $limit=100, $categories=array(),
        $categories_exclude=array(), $status=array('PUBLISHED','BREAKING','HIDDEN','ARCHIVED'),
        $order_by='publish_from', $order_direction='DESC')
    {
        $content_table = self::$table_name;
        $category_table = FRAMEWORK_TABLE_PREFIX.'flexcontent_category';
        $in_status = "('".implode("','", $status)."')";

        $SQL = "SELECT * FROM `$category_table`,`$content_table` WHERE `$content_table`.content_id=`$category_table`.content_id ".
            "AND `language`='$language' ";

        if (!empty($categories)) {
            $cats = "('".implode("','", $categories)."')";
            $SQL .= "AND `$category_table`.category_id IN $cats ";
        }
        elseif (!empty($categories_exclude)) {
            $categories = "('".implode("','", $categories_exclude)."')";
            $SQL .= "AND `$category_table`.category_id NOT IN $categories ";
        }

        // and the rest - GROUP BY prevents duplicate entries!
        $order_table = (in_array($order_by, $this->getColumns())) ? $content_table : $category_table;
        $SQL .= "AND `status` IN $in_status GROUP BY `$content_table`.content_id ORDER BY ".
            "FIELD (`status`,'BREAKING','PUBLISHED','HIDDEN','ARCHIVED','UNPUBLISHED','DELETED'), `$order_table`.`$order_by` $order_direction ".
            "LIMIT $limit";
        $results = $this->app['db']->fetchAll($SQL);

        $list = array();
        foreach ($results as $result) {
            $content = array();
            foreach ($result as $key => $value) {
                $content[$key] = (is_string($value)) ? $this->app['utils']->unsanitizeText($value) : $value;
                if (($key == 'content') || ($key == 'teaser')) {
                    $content[$key] = $this->replacePlaceholderWithURL($content[$key]);
                }
            }
            $list[] = $content;
        }
        return (!empty($list)) ? $list : false;
    }
}
