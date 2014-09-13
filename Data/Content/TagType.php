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

class TagType
{
    protected $app = null;
    protected static $table_name = null;

    public static $forbidden_chars = array('|',';');

    /**
     * Constructor
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        self::$table_name = FRAMEWORK_TABLE_PREFIX.'flexcontent_tag_type';
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
        `tag_id` INT(11) NOT NULL AUTO_INCREMENT,
        `language` VARCHAR(2) NOT NULL DEFAULT 'EN',
        `tag_name` VARCHAR(64) NOT NULL DEFAULT '',
        `tag_permalink` VARCHAR(255) NOT NULL DEFAULT '',
        `tag_description` TEXT NOT NULL,
        `tag_image` TEXT NOT NULL,
        `timestamp` TIMESTAMP,
        PRIMARY KEY (`tag_id`),
        UNIQUE KEY (`tag_name`)
    )
    COMMENT='The tag type definition table for flexContent'
    ENGINE=InnoDB
    AUTO_INCREMENT=1
    DEFAULT CHARSET=utf8
    COLLATE='utf8_general_ci'
EOD;
        try {
            $this->app['db']->query($SQL);
            $this->app['monolog']->addInfo("Created table 'flexcontent_tag_type'", array(__METHOD__, __LINE__));
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
     * Get the columns of the tag type table
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
     * Count the records in the table
     *
     * @throws \Exception
     * @return integer number of records
     */
    public function count()
    {
        try {
            $SQL = "SELECT COUNT(*) FROM `".self::$table_name."`";
            return $this->app['db']->fetchColumn($SQL);
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select all records in alphabetical order
     *
     * @throws \Exception
     * @return multitype:multitype:unknown
     */
    public function selectAll()
    {
        try {
            $results = $this->app['db']->fetchAll("SELECT * FROM `".self::$table_name."` ORDER BY `tag_name` ASC");
            $tags = array();
            if (is_array($results)) {
                foreach ($results as $result) {
                    $record = array();
                    foreach ($result as $key => $value) {
                        $record[$key] = is_string($value) ? $this->app['utils']->unsanitizeText($value) : $value;
                    }
                    $tags[] = $record;
                }
            }
            return $tags;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select TAG names with LIKE for the autocomplete function
     *
     * @param string $search
     * @throws \Exception
     * @return multitype:multitype:unknown
     */
    public function selectLikeName($search, $language)
    {
        try {
            $SQL = "SELECT `tag_id`, `tag_name` FROM `".self::$table_name."` ".
                "WHERE `language`='$language' AND `tag_name` LIKE '%$search%' ORDER BY `tag_name` ASC";
            $results = $this->app['db']->fetchAll($SQL);
            $tags = array();
            if (is_array($results)) {
                foreach ($results as $result) {
                    $record = array();
                    foreach ($result as $key => $value) {
                        $record[$key] = is_string($value) ? $this->app['utils']->unsanitizeText($value) : $value;
                    }
                    $tags[] = $record;
                }
            }
            return $tags;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select the TAG name by TAG_ID
     *
     * @param integer $tag_id
     * @throws \Exception
     * @return boolean
     */
    public function selectNameByID($tag_id)
    {
        try {
            $SQL = "SELECT `tag_name` FROM `".self::$table_name."` WHERE `tag_id`='$tag_id'";
            $name = $this->app['db']->fetchColumn($SQL);
            return (is_null($name)) ? false : $this->app['utils']->unsanitizeText($name);
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select a Tag Type record by the given TAG ID
     *
     * @param integer $tag_id
     * @throws \Exception
     * @return Ambigous <boolean, array>
     */
    public function select($tag_id) {
        try {
            $SQL = "SELECT * FROM `".self::$table_name."` WHERE `tag_id`='$tag_id'";
            $result = $this->app['db']->fetchAssoc($SQL);
            $tag = array();
            if (is_array($result)) {
                foreach ($result as $key => $value) {
                    $tag[$key] = is_string($value) ? $this->app['utils']->unsanitizeText($value) : '';
                }
            }
            return (!empty($tag)) ? $tag : false;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Update the given TAG TYPE record
     *
     * @param integer $tag_id
     * @param array $data
     * @throws \Exception
     */
    public function update($tag_id, $data)
    {
        try {
            $update = array();
            foreach ($data as $key => $value) {
                if ($key == 'tag_id') {
                    continue;
                }
                $update[$key] = is_string($value) ? $this->app['utils']->sanitizeText($value) : $value;
            }
            $this->app['db']->update(self::$table_name, $update, array('tag_id' => $tag_id));
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Delete the record with the given TAG_ID
     *
     * @param integer $tag_id
     * @throws \Exception
     */
    public function delete($tag_id)
    {
        try {
            $this->app['db']->delete(self::$table_name, array('tag_id' => $tag_id));
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Insert a new record
     *
     * @param array $data
     * @param integer reference $tag_id
     * @throws \Exception
     */
    public function insert($data, &$tag_id)
    {
        try {
            $insert = array();
            foreach ($data as $key => $value) {
                if (($key == 'tag_id') || ($key == 'timestamp')) continue;
                if ($key == 'tag_name') {
                    foreach (self::$forbidden_chars as $forbidden) {
                        if (false !== strpos($value, $forbidden)) {
                            throw new \Exception("The tag name $value contains the forbidden character : $forbidden");
                        }
                    }
                }
                $insert[$key] = is_string($value) ? $this->app['utils']->sanitizeText($value) : $value;
            }
            $check_array = array('tag_description', 'tag_image');
            foreach ($check_array as $key) {
                if (!isset($insert[$key])) {
                    $insert[$key] = '';
                }
            }
            $this->app['db']->insert(self::$table_name, $insert);
            $tag_id = $this->app['db']->lastInsertId();
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select a list from the TagType table in paging view
     *
     * @param integer $limit_from start selection at position
     * @param integer $rows_per_page select max. rows per page
     * @param array $order_by fields to order by
     * @param string $order_direction 'ASC' (default) or 'DESC'
     * @throws \Exception
     * @return array selected records
     */
    public function selectList($limit_from, $rows_per_page, $order_by=null, $order_direction='ASC', $columns)
    {
        try {
            $SQL = "SELECT * FROM `".self::$table_name."`";
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

            $TagData = new Tag($this->app);

            $tags = array();
            foreach ($results as $result) {
                $tag = array();
                foreach ($columns as $column) {
                    if ($column == 'used_by_content_id') {
                        // get all flexContent ID's which are using this TAG
                        $tag['used_by_content_id'] = $TagData->selectByTagID($result['tag_id']);
                    }
                    else {
                        foreach ($result as $key => $value) {
                            if ($key == $column) {
                                $tag[$key] = is_string($value) ? $this->app['utils']->unsanitizeText($value) : $value;
                            }
                        }
                    }
                }
                $tags[] = $tag;
            }
            return $tags;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Check if a TAG NAME already exists
     *
     * @param string $tag_name
     * @throws \Exception
     * @return boolean
     */
    public function existsName($tag_name, $language)
    {
        try {
            $SQL = "SELECT `tag_name` FROM `".self::$table_name."` WHERE LOWER(`tag_name`) = '".
                $this->app['utils']->sanitizeVariable(strtolower($tag_name))."' AND `language`='$language'";
            $tag = $this->app['db']->fetchColumn($SQL);
            return !empty($tag);
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select the record by the given TAG Name and language
     *
     * @param string $tag_name
     * @param string $language
     * @throws \Exception
     * @return boolean|array
     */
    public function selectByName($tag_name, $language)
    {
        try {
            $SQL = "SELECT * FROM `".self::$table_name."` WHERE LOWER(`tag_name`) = '".
                $this->app['utils']->sanitizeVariable(strtolower($tag_name))."' AND `language`='$language'";
            $result = $this->app['db']->fetchAssoc($SQL);
            $tag = array();
            if (is_array($result)) {
                foreach ($result as $key => $value) {
                    $tag[$key] = (is_string($value)) ? $this->app['utils']->unsanitizeText($value) : $value;
                }
            }
            return (isset($tag['tag_id'])) ? $tag : false;
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
            $SQL = "SELECT `tag_permalink` FROM `".self::$table_name."` WHERE `tag_permalink`='$permalink' AND `language`='$language'";
            $result = $this->app['db']->fetchColumn($SQL);
            return ($result == $permalink);
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Count PermaLinks which starts LIKE the given $permalink
     *
     * @param string $this
     * @throws \Exception
     */
    public function countPermaLinksLikeThis($permalink, $language)
    {
        try {
            $SQL = "SELECT COUNT(`tag_permalink`) FROM `".self::$table_name."` WHERE `tag_permalink` LIKE '$permalink%' ".
                "AND `language`='$language'";
            return $this->app['db']->fetchColumn($SQL);
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select the TAG ID by the given PermanentLink
     *
     * @param string $permalink
     * @throws \Exception
     * @return Ambigous <boolean, array>
     */
    public function selectTagIDbyPermaLink($permalink, $language)
    {
        try {
            $SQL = "SELECT `tag_id` FROM `".self::$table_name."` WHERE `tag_permalink`='$permalink' AND `language`='$language'";
            $result = $this->app['db']->fetchColumn($SQL);
            return ($result > 0) ? $result : false;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    public function cmsSearch($category_id, $words, $or, $status)
    {
        try {
            $category = FRAMEWORK_TABLE_PREFIX.'flexcontent_category';
            $content = FRAMEWORK_TABLE_PREFIX.'flexcontent_content';
            $tag = FRAMEWORK_TABLE_PREFIX.'flexcontent_tag';
            $tag_type = self::$table_name;

            $search = '';
            foreach ($words as $word) {
                if (!empty($search)) {
                    $search .= $or ? ' OR ' : ' AND ';
                }
                $search .= "(`tag_name` LIKE '%$word%' OR `tag_description` LIKE '%$word%')";
            }

            $in_status = "('".implode("','", $status)."')";

            $SQL = "SELECT * FROM `$tag_type` ".
                "LEFT JOIN `$tag` ON `$tag`.`tag_id`=`$tag_type`.`tag_id` ".
                "LEFT JOIN `$content` ON `$content`.`content_id`=`$tag`.`content_id` ".
                "LEFT JOIN `$category` ON `$category`.`content_id`=`$content`.`content_id` ".
                "WHERE `category_id`=$category_id AND ($search) AND `status` IN $in_status ORDER BY ".
                "FIELD (`status`,'BREAKING','PUBLISHED','HIDDEN','ARCHIVED','UNPUBLISHED','DELETED'), ".
                "`publish_from` DESC";

            $result = $this->app['db']->fetchAll($SQL);
            $tags = array();
            for ($i=0; $i < sizeof($result); $i++) {
                $excerpt = strip_tags($this->app['utils']->unsanitizeText($result[$i]['tag_name']));
                $excerpt .= '.'.strip_tags($this->app['utils']->unsanitizeText($result[$i]['tag_description']));
                $result[$i]['excerpt'] = $excerpt;
                $tags[] = $result[$i];
            }
            return (!empty($tags)) ? $tags : false;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select the Hashtag list for the usage in the CKEditor
     *
     * @param string $language
     * @param unknown $status
     * @throws \Exception
     * @return Ambigous <boolean, multitype:multitype:unknown  >
     */
    public function selectHashtagLinkList($language=null, $status=array('PUBLISHED','BREAKING','HIDDEN','ARCHIVED'))
    {
        try {
            $tagtype = self::$table_name;
            $tag = FRAMEWORK_TABLE_PREFIX.'flexcontent_tag';
            $content = FRAMEWORK_TABLE_PREFIX.'flexcontent_content';
            $in_status = "('".implode("','", $status)."')";
            if (is_null($language)) {
                $SQL = "SELECT * FROM `$tagtype` ".
                    "LEFT JOIN `$tag` ON `$tag`.`tag_id`=`$tagtype`.`tag_id` ".
                    "LEFT JOIN `$content` ON `$content`.`content_id`= `$tag`.`content_id` ".
                    "WHERE `status` IN $in_status GROUP BY `tag_name` ORDER BY `tag_name` ASC";
            }
            else {
                $SQL = "SELECT * FROM `$tagtype` ".
                    "LEFT JOIN `$tag` ON `$tag`.`tag_id`=`$tagtype`.`tag_id` ".
                    "LEFT JOIN `$content` ON `$content`.`content_id`= `$tag`.`content_id` ".
                    "WHERE `status` IN $in_status AND `$tagtype`.`language`='$language' GROUP BY `tag_name` ORDER BY `tag_name` ASC";
            }

            $results = $this->app['db']->fetchAll($SQL);
            $list = array();
            foreach ($results as $result) {
                $item = array();
                foreach ($result as $key => $value) {
                    $item[$key] = (is_string($value)) ? $this->app['utils']->unsanitizeText($value) : $value;
                }
                $list[] = $item;
            }
            return (!empty($list)) ? $list : false;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }
}
