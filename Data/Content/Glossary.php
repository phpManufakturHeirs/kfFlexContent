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
     * Get the glossary TYPEs as key => value pair for the usage in forms
     *
     * @return array
     */
    public function getGlossaryTypeValuesForForm()
    {
        $enums = $this->app['db.utils']->getEnumValues(self::$table_name, 'glossary_type');
        $result = array();
        foreach ($enums as $enum) {
            $result[$enum] = $this->app['utils']->humanize($enum);
        }
        return $result;
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
     * Check if the given content ID already exists
     *
     * @param integer $content_id
     * @throws \Exception
     * @return boolean
     */
    public function existsContentID($content_id)
    {
        try {
            $SQL = "SELECT `content_id` FROM `".self::$table_name."` WHERE `content_id`=$content_id";
            $content_id = $this->app['db']->fetchColumn($SQL);
            return ($content_id > 0);
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Update an existing glossary record by the given content ID
     *
     * @param integer $content_id
     * @param array $data
     * @throws \Exception
     */
    public function updateContentID($content_id, $data)
    {
        try {
            unset($data['timestamp']);
            $this->app['db']->update(self::$table_name, $data, array('content_id' => $content_id));
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Delete the entry which is assigned to the given content ID
     *
     * @param integer $content_id
     * @throws \Exception
     */
    public function deleteContentID($content_id)
    {
        try {
            $this->app['db']->delete(self::$table_name, array('content_id' => $content_id));
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select a record by the given content ID
     *
     * @param integer $content_id
     * @throws \Exception
     * @return Ambigous <boolean, array>
     */
    public function selectContentID($content_id)
    {
        try {
            $SQL = "SELECT * FROM `".self::$table_name."` WHERE `content_id`='$content_id'";
            $glossary = $this->app['db']->fetchAssoc($SQL);
            return (is_array($glossary) && isset($glossary['glossary_id'])) ? $glossary : false;
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
