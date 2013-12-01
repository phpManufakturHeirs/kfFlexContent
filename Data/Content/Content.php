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
        `title` VARCHAR(512) NOT NULL DEFAULT '',
        `description` VARCHAR(512) NOT NULL DEFAULT '',
        `keywords` VARCHAR(512) NOT NULL DEFAULT '',
        `permalink` VARCHAR(255) NOT NULL DEFAULT '',
        `publish_from` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
        `publish_to` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
        `publish_type` ENUM('UNPUBLISHED','PUBLISHED','PROTECTED','HIDDEN','BREAKING','ARCHIVE') NOT NULL DEFAULT 'UNPUBLISHED',
        `teaser` TEXT NOT NULL,
        `teaser_image` TEXT NOT NULL,
        `content` TEXT NOT NULL,
        `status` ENUM('ACTIVE', 'LOCKED', 'DELETED') NOT NULL DEFAULT 'ACTIVE',
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
    public function getPublishTypeValuesForForm()
    {
        $enums = $this->app['db.utils']->getEnumValues(self::$table_name, 'publish_type');
        $result = array();
        foreach ($enums as $enum) {
            $result[$enum] = $enum;
        }
        return $result;
    }

}
