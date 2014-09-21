<?php

/**
 * flexContent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/flexContent
 * @copyright 2014 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\flexContent\Data\Content;

use Silex\Application;

class Glossary
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
        self::$table_name = FRAMEWORK_TABLE_PREFIX.'flexcontent_glossary';
        $this->EventData = new Event($app);
    }

    /**
     * Create the table
     *
     * @throws \Exception
     */
    public function createTable()
    {
        $table = self::$table_name;
        $table_content = FRAMEWORK_TABLE_PREFIX.'flexcontent_content';

        $SQL = <<<EOD
    CREATE TABLE IF NOT EXISTS `$table` (
        `glossary_id` INT(11) NOT NULL AUTO_INCREMENT,
        `content_id` INT(11) NOT NULL DEFAULT -1,
        `language` VARCHAR(2) NOT NULL DEFAULT 'EN',
        `glossary_type` ENUM('ABBREVIATION', 'ACRONYM', 'KEYWORD') NOT NULL DEFAULT 'KEYWORD',
        `glossary_unique` VARCHAR(128) NOT NULL DEFAULT '',
        `timestamp` TIMESTAMP,
        PRIMARY KEY (`glossary_id`),
        INDEX (`content_id`),
        UNIQUE (`glossary_unique`),
        CONSTRAINT
            FOREIGN KEY (`content_id`)
            REFERENCES $table_content (`content_id`)
            ON DELETE CASCADE
        )
    COMMENT='The glossary extension for flexContent'
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
     * Check if the given Glossary Unique entry exists
     *
     * @param string $glossary_unique
     * @throws \Exception
     * @return boolean
     */
    public function existsUnique($glossary_unique)
    {
        try {
            $SQL = "SELECT `content_id` FROM `".self::$table_name."` WHERE `glossary_unique`='$glossary_unique'";
            $content_id = $this->app['db']->fetchColumn($SQL);
            return ($content_id > 0) ? $content_id : false;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Insert a new Glossary extension record
     *
     * @param array $data
     * @throws \Exception
     * @return integer new glossary ID
     */
    public function insert($data)
    {
        try {
            unset($data['glossary_id']);
            unset($data['timestamp']);
            $this->app['db']->insert(self::$table_name, $data);
            return $this->app['db']->lastInsertId();
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }
}
