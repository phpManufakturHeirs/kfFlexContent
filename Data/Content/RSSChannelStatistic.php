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

class RSSChannelStatistic
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
        self::$table_name = FRAMEWORK_TABLE_PREFIX.'flexcontent_rss_channel_statistic';
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
        `statistic_id` INT(11) NOT NULL AUTO_INCREMENT,
        `channel_id` INT(11) NOT NULL DEFAULT -1,
        `channel_callers` INT(11) NOT NULL DEFAULT 0,
        `channel_views` INT(11) NOT NULL DEFAULT 0,
        `channel_date` DATE NOT NULL DEFAULT '0000-00-00',
        `timestamp` TIMESTAMP,
        PRIMARY KEY (`statistic_id`),
        INDEX (`channel_id`, `channel_date`)
        )
    COMMENT='Statistic for the RSS Channels used by flexContent'
    ENGINE=InnoDB
    AUTO_INCREMENT=1
    DEFAULT CHARSET=utf8
    COLLATE='utf8_general_ci'
EOD;
        try {
            $this->app['db']->query($SQL);
            $this->app['monolog']->addInfo("Created table 'flexcontent_rss_channel_statistic'", array(__METHOD__, __LINE__));
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
     * Insert a statistic record for the given Channel ID
     *
     * @param integer $channel_id
     * @param integer $channel_callers
     * @param integer $channel_views
     * @param string $channel_date date
     * @throws \Exception
     */
    public function insert($channel_id, $channel_callers, $channel_views, $channel_date)
    {
        try {
            $this->app['db']->insert(self::$table_name, array(
                'channel_id' => $channel_id,
                'channel_callers' => $channel_callers,
                'channel_views' => $channel_views,
                'channel_date' => $channel_date
            ));
            return $this->app['db']->lastInsertId();
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }
}
