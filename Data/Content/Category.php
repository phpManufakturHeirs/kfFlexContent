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

class Category
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
        self::$table_name = FRAMEWORK_TABLE_PREFIX.'flexcontent_category';
    }

    /**
     * Create the table
     *
     * @throws \Exception
     */
    public function createTable()
    {
        $table = self::$table_name;
        $table_category_type = FRAMEWORK_TABLE_PREFIX.'flexcontent_category_type';
        $table_content = FRAMEWORK_TABLE_PREFIX.'flexcontent_content';
        $SQL = <<<EOD
    CREATE TABLE IF NOT EXISTS `$table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `category_id` INT(11) NOT NULL DEFAULT '-1',
        `is_primary` TINYINT(1) NOT NULL DEFAULT '0',
        `content_id` INT(11) NOT NULL DEFAULT '-1',
        `timestamp` TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX (`category_id`, `content_id`),
        CONSTRAINT
            FOREIGN KEY (`category_id`)
            REFERENCES `$table_category_type` (`category_id`)
            ON DELETE CASCADE,
        CONSTRAINT
            FOREIGN KEY (`content_id`)
            REFERENCES `$table_content` (`content_id`)
            ON DELETE CASCADE
        )
    COMMENT='The categories used by the flexContent records'
    ENGINE=InnoDB
    AUTO_INCREMENT=1
    DEFAULT CHARSET=utf8
    COLLATE='utf8_general_ci'
EOD;
        try {
            $this->app['db']->query($SQL);
            $this->app['monolog']->addInfo("Created table 'flexcontent_category'", array(__METHOD__, __LINE__));
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
     * Delete Categories by the given content_id
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
     * Select the flexContent IDs which are using the given CATEGORY ID
     *
     * @param integer $category_id
     * @throws \Exception
     * @return array with content IDs
     */
    public function selectByCategoryID($category_id)
    {
        try {
            $SQL = "SELECT `content_id` FROM `".self::$table_name."` WHERE `category_id`='$category_id' ORDER BY `content_id` ASC";
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
}
