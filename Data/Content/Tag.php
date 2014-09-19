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

class Tag
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
        self::$table_name = FRAMEWORK_TABLE_PREFIX.'flexcontent_tag';
    }

    /**
     * Create the table
     *
     * @throws \Exception
     */
    public function createTable()
    {
        $table = self::$table_name;
        $table_tag_type = FRAMEWORK_TABLE_PREFIX.'flexcontent_tag_type';
        $table_content = FRAMEWORK_TABLE_PREFIX.'flexcontent_content';
        $SQL = <<<EOD
    CREATE TABLE IF NOT EXISTS `$table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `tag_id` INT(11) NOT NULL DEFAULT '-1',
        `position` INT(11) NOT NULL DEFAULT '-1',
        `content_id` INT(11) NOT NULL DEFAULT '-1',
        `timestamp` TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX (`tag_id`, `content_id`),
        CONSTRAINT
            FOREIGN KEY (`tag_id`)
            REFERENCES `$table_tag_type` (`tag_id`)
            ON DELETE CASCADE,
        CONSTRAINT
            FOREIGN KEY (`content_id`)
            REFERENCES `$table_content` (`content_id`)
            ON DELETE CASCADE
        )
    COMMENT='The tags used by the flexContent records'
    ENGINE=InnoDB
    AUTO_INCREMENT=1
    DEFAULT CHARSET=utf8
    COLLATE='utf8_general_ci'
EOD;
        try {
            $this->app['db']->query($SQL);
            $this->app['monolog']->addInfo("Created table 'flexcontent_tag'", array(__METHOD__, __LINE__));
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
     * Insert a new record
     *
     * @param array $data
     * @param integer reference $id
     * @throws \Exception
     */
    public function insert($data, &$id)
    {
        try {
            $this->app['db']->insert(self::$table_name, $data);
            $id = $this->app['db']->lastInsertId();
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Delete TAGs by the given content_id
     *
     * @param integer $content_id
     * @throws \Exception
     */
    public function deleteByContentID($content_id)
    {
        try {
            $this->app['db']->delete(self::$table_name, array('content_id' => $content_id));
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select the ID of a record by the given TAG ID and CONTENT ID
     *
     * @param integer $tag_id
     * @param integer $content_id
     * @throws \Exception
     * @return Ambigous <boolean, unknown>
     */
    public function selectIDbyTagIDandContentID($tag_id, $content_id)
    {
        try {
            $SQL = "SELECT `id` FROM `".self::$table_name."` WHERE `tag_id`='$tag_id' AND `content_id`='$content_id'";
            $id = $this->app['db']->fetchColumn($SQL);
            return (is_null($id)) ? false : $id;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Update the record with the given ID
     *
     * @param integer $id
     * @param array $data
     * @throws \Exception
     */
    public function update($id, $data)
    {
        try {
            $this->app['db']->update(self::$table_name, $data, array('id' => $id));
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Delete the record with the given ID
     *
     * @param integer $id
     * @throws \Exception
     */
    public function delete($id)
    {
        try {
            $this->app['db']->delete(self::$table_name, array('id' => $id));
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select all TAGs associated to the given CONTENT ID, sorted by POSITION
     *
     * @param integer $content_id
     * @throws \Exception
     */
    public function selectByContentID($content_id)
    {
        try {
            $SQL = "SELECT * FROM `".self::$table_name."` WHERE `content_id`='$content_id' ORDER BY `position` ASC";
            return $this->app['db']->fetchAll($SQL);
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select the flexContent IDs which are using the given TAG ID
     *
     * @param integer $tag_id
     * @throws \Exception
     * @return array with content IDs
     */
    public function selectByTagID($tag_id)
    {
        try {
            $SQL = "SELECT `content_id` FROM `".self::$table_name."` WHERE `tag_id`='$tag_id' ORDER BY `content_id` ASC";
            $results = $this->app['db']->fetchAll($SQL);
            $ids = array();
            foreach ($results as $result) {
                $ids[] = $result['content_id'];
            }
            return $ids;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Return a simple TAG array with TAG ID and TAG NAME for each record only
     *
     * @param integer $content_id
     * @throws \Exception
     * @return array
     */
    public function getSimpleTagArrayForContentID($content_id)
    {
        try {
            $tag_table = self::$table_name;
            $type_table = FRAMEWORK_TABLE_PREFIX.'flexcontent_tag_type';

            $SQL = "SELECT `$type_table`.`tag_id`, `$type_table`.`tag_name` FROM `$type_table` ".
                "LEFT JOIN `$tag_table` ON `$tag_table`.`tag_id`=`$type_table`.`tag_id` ".
                "WHERE `content_id`='$content_id' ORDER BY `position` ASC";

            $results = $this->app['db']->fetchAll($SQL);
            $tags = array();
            foreach ($results as $result) {
                $tag = array();
                foreach ($result as $key => $value) {
                    $tag[$key] = is_string($value) ? $this->app['utils']->unsanitizeText($value) : $value;
                }
                $tags[] = $tag;
            }
            return $tags;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Retrurn a TAG array for the given flexContent ID with all available information
     *
     * @param integer $content_id
     * @throws \Exception
     * @return multitype:multitype:unknown
     */
    public function selectTagArrayForContentID($content_id)
    {
        try {
            $tag_table = self::$table_name;
            $type_table = FRAMEWORK_TABLE_PREFIX.'flexcontent_tag_type';

            $SQL = "SELECT * FROM `$type_table` ".
                "LEFT JOIN `$tag_table` ON `$tag_table`.`tag_id`=`$type_table`.`tag_id` ".
                "WHERE `content_id`='$content_id' ORDER BY `position` ASC";

            $results = $this->app['db']->fetchAll($SQL);
            $tags = array();
            foreach ($results as $result) {
                $tag = array();
                foreach ($result as $key => $value) {
                    $tag[$key] = is_string($value) ? $this->app['utils']->unsanitizeText($value) : $value;
                }
                $tags[] = $tag;
            }
            return $tags;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select a target URL for the given TAG ID
     *
     * @param integer $tag_id
     * @param integer reference $category_id
     * @param integer reference $content_id
     * @throws \Exception
     * @return Ambigous <boolean, string>
     */
    public function selectTargetURLbyTagID($tag_id, &$category_id=-1, &$content_id=-1)
    {
        try {
            $tag_table = self::$table_name;
            $content_table = FRAMEWORK_TABLE_PREFIX.'flexcontent_content';
            $category_table = FRAMEWORK_TABLE_PREFIX.'flexcontent_category';
            $category_type_table = FRAMEWORK_TABLE_PREFIX.'flexcontent_category_type';

            $SQL = "SELECT `target_url`, $content_table.content_id, $category_table.category_id FROM `$content_table` ".
                "LEFT JOIN `$category_table` ON `$category_table`.`content_id`=`$content_table`.`content_id` ".
                "LEFT JOIN `$category_type_table` ON `$category_type_table`.`category_id`=`$category_table`.`category_id` ".
                "LEFT JOIN `$tag_table` ON `$tag_table`.`content_id`=`$content_table`.`content_id` ".
                "WHERE `$category_table`.`is_primary`=1 AND `tag_id`=$tag_id AND `$content_table`.`status` != 'UNPUBLISHED' AND ".
                "`$content_table`.`status` != 'DELETED' ".
                "ORDER BY `position` ASC, `$content_table`.`publish_from` DESC LIMIT 1";

            $result = $this->app['db']->fetchAssoc($SQL);
            $category_id = (isset($result['category_id'])) ? $result['category_id'] : -1;
            $content_id = (isset($result['content_id'])) ? $result['content_id'] : -1;
            return (isset($result['target_url'])) ? $result['target_url'] : false;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Check if the given TAG ID is assigned to any content
     *
     * @param integer $tag_id
     * @throws \Exception
     * @return boolean
     */
    public function isAssigned($tag_id)
    {
        try {
            $SQL = "SELECT `tag_id` FROM `".self::$table_name."` WHERE `tag_id`=$tag_id";
            $result = $this->app['db']->fetchColumn($SQL);
            return ($result == $tag_id);
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select #tags counting the usage
     *
     * @param string $locale
     * @param integer $limit
     * @param string $order_by default 'tag_count'
     * @param string $order_direction default null
     * @param array $tag_ids default null
     * @throws \Exception
     * @return Ambigous <boolean, array>
     */
    public function selectTags($locale='EN', $limit=null, $order_by='tag_count', $order_direction=null, $tag_ids=null)
    {
        try {
            if (in_array(strtolower($order_by), array('tag_count', 'tag_name'))) {
                switch (strtolower($order_by)) {
                    case 'tag_id':
                        $order_by = 'tags.tag_id';
                        break;
                    case 'tag_name':
                        $order_by = 'tag_name';
                        break;
                    case 'null':
                        $order_by = 'null';
                        break;
                    case 'tag_count':
                    default:
                        $order_by = 'tags_2.tag_count';
                        break;
                }
            }
            if (!is_null($order_direction)) {
                $order_direction = strtoupper($order_direction);
            }
            elseif ($order_by == 'tags_2.tag_count') {
                $order_direction = 'DESC';
            }
            else {
                $order_direction = 'ASC';
            }

            $SQL = "SELECT tags.id, tags.tag_id, tags_2.tag_count, language, tag_name, tag_permalink, tag_description, tag_image ".
                "FROM `".self::$table_name."` tags ".
                "JOIN (SELECT tag_id, COUNT(*) AS tag_count FROM `".self::$table_name."` GROUP BY tag_id) tags_2 ON (tags_2.tag_id = tags.tag_id) ".
                "LEFT JOIN `".FRAMEWORK_TABLE_PREFIX.'flexcontent_tag_type'."` type ON type.tag_id = tags.tag_id ".
                "WHERE language = '$locale' ";

            if (!is_null($tag_ids) && is_array($tag_ids) && !empty($tag_ids)) {
                $in_tags = "('".implode("','", $tag_ids)."')";
                $SQL .= "AND `tags`.`tag_id` IN $in_tags ";
            }

            $SQL .= "GROUP BY `tag_id`";

            if (($order_by === 'null') && !is_null($tag_ids) && is_array($tag_ids) && !empty($tag_ids)) {
                $in_tags = implode(',', $tag_ids);
                $SQL .= " ORDER BY FIELD(`tags`.`tag_id`, $in_tags)";
            }
            elseif (!empty($order_by)) {
                $SQL .= " ORDER BY $order_by $order_direction";
            }

            if (!is_null($limit)) {
                $SQL .= " LIMIT $limit";
            }

            $results = $this->app['db']->fetchAll($SQL);
            $tag_array = array();
            if (is_array($results)) {
                foreach ($results as $result) {
                    $item = array();
                    foreach ($result as $key => $value) {
                        $item[$key] = is_string($value) ? $this->app['utils']->unsanitizeText($value) : $value;
                    }
                    $tag_array[] = $item;
                }
            }
            return (!empty($tag_array)) ? $tag_array : false;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }


}
