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

    public function getSimpleTagArrayForContentID($content_id)
    {
        try {
            $tag_table = self::$table_name;
            $type_table = FRAMEWORK_TABLE_PREFIX.'flexcontent_tag_type';
            $SQL = "SELECT $type_table.`tag_id`, `tag_name`  FROM `$tag_table`, `$type_table` WHERE $tag_table.`tag_id`=$type_table.`tag_id` AND `content_id`='$content_id' ORDER BY `position` ASC";
            return $this->app['db']->fetchAll($SQL);
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }
}
