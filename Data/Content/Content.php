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
        PRIMARY KEY (`content_id`),
        UNIQUE INDEX (`permalink`)
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
     * Insert a new flexContent record
     *
     * @param array $data
     * @param integer reference $content_id
     * @throws \Exception
     */
    public function insert($data, &$content_id)
    {
        try {
            $insert = array();
            foreach ($data as $key => $value) {
                if (($key == 'content_id') || ($key == 'timestamp')) continue;
                $insert[$this->app['db']->quoteIdentifier($key)] = is_string($value) ? $this->app['utils']->sanitizeText($value) : $value;
            }
            $this->app['db']->insert(self::$table_name, $insert);
            $content_id = $this->app['db']->lastInsertId();
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
                if (($key == 'content_id') || ($key == 'timestamp')) continue;
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
            $SQL = "SELECT * FROM `$content`";
            if (is_array($select_status) && !empty($select_status)) {
                $SQL .= " WHERE ";
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
            $SQL .= " LIMIT $limit_from, $rows_per_page";
            $results = $this->app['db']->fetchAll($SQL);

            $contents = array();
            foreach ($results as $result) {
                $content = array();
                foreach ($columns as $column) {
                    foreach ($result as $key => $value) {
                        if ($key == $column) {
                            $content[$key] = is_string($value) ? $this->app['utils']->unsanitizeText($value) : $value;
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
    public function existsPermaLink($permalink)
    {
        try {
            $SQL = "SELECT `permalink` FROM `".self::$table_name."` WHERE `permalink`='$permalink'";
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
    public function countPermaLinksLikeThis($permalink)
    {
        try {
            $SQL = "SELECT COUNT(`permalink`) FROM `".self::$table_name."` WHERE `permalink` LIKE '$permalink%'";
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
                "category_id='$category_id' AND is_primary=1 AND '$published_from' $select `publish_from` AND (status !='UNPUBLISHED' OR status != 'DELETED') ".
                "AND `language`='$language' ORDER BY publish_from $direction LIMIT 1";

            $result = $this->app['db']->fetchAssoc($SQL);
            $content = array();
            if (is_array($result)) {
                foreach ($result as $key => $value) {
                    $content[$key] = (is_string($value)) ? $this->app['utils']->unsanitizeText($value) : $value;
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

}
