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

class Event
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
        self::$table_name = FRAMEWORK_TABLE_PREFIX.'flexcontent_event';
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
        `event_id` INT(11) NOT NULL AUTO_INCREMENT,
        `content_id` INT(11) NOT NULL DEFAULT -1,
        `language` VARCHAR(2) NOT NULL DEFAULT 'EN',
        `event_date_from` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
        `event_date_to` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
        `event_organizer` INT(11) NOT NULL DEFAULT -1,
        `event_location` INT(11) NOT NULL DEFAULT -1,
        `timestamp` TIMESTAMP,
        PRIMARY KEY (`event_id`),
        INDEX (`content_id`),
        CONSTRAINT
            FOREIGN KEY (`content_id`)
            REFERENCES $table_content (`content_id`)
            ON DELETE CASCADE
        )
    COMMENT='The event extension for flexContent'
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
     * Insert a new event record
     *
     * @param array $data
     * @param integer reference $event_id
     * @throws \Exception
     * @return integer event ID
     */
    public function insert($data, &$event_id=-1)
    {
        try {
            unset($data['event_id']);
            unset($data['timestamp']);
            $this->app['db']->insert(self::$table_name, $data);
            $event_id = $this->app['db']->lastInsertId();
            return $event_id;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Update an existing Event record for the given Content ID
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
     * Check if a record for the given content ID exists
     *
     * @param integer $content_id
     * @throws \Exception
     * @return boolean
     */
    public function existsContentID($content_id)
    {
        try {
            $SQL = "SELECT `content_id` FROM `".self::$table_name."` WHERE `content_id`='$content_id'";
            $check = $this->app['db']->fetchColumn($SQL);
            return ($check == $content_id);
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select a event record by the given Content ID
     *
     * @param integer $content_id
     * @throws \Exception
     */
    public function selectContentID($content_id)
    {
        try {
            $SQL = "SELECT * FROM `".self::$table_name."` WHERE `content_id`='$content_id'";
            $event = $this->app['db']->fetchAssoc($SQL);
            return (is_array($event) && isset($event['event_id'])) ? $event : false;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }
}
