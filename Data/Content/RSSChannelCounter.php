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

class RSSChannelCounter
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
        self::$table_name = FRAMEWORK_TABLE_PREFIX.'flexcontent_rss_channel_counter';
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
        `counter_id` INT(11) NOT NULL AUTO_INCREMENT,
        `channel_id` INT(11) NOT NULL DEFAULT -1,
        `counter_hash` VARCHAR(32) NOT NULL DEFAULT '',
        `counter_count` INT(11) NOT NULL DEFAULT 0,
        `counter_date` DATE NOT NULL DEFAULT '0000-00-00',
        `timestamp` TIMESTAMP,
        PRIMARY KEY (`counter_id`),
        INDEX (`channel_id`, `counter_hash`)
        )
    COMMENT='Counter for the RSS Channels used by flexContent'
    ENGINE=InnoDB
    AUTO_INCREMENT=1
    DEFAULT CHARSET=utf8
    COLLATE='utf8_general_ci'
EOD;
        try {
            $this->app['db']->query($SQL);
            $this->app['monolog']->addInfo("Created table 'flexcontent_rss_channel_counter'", array(__METHOD__, __LINE__));
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
     * Check if a record exists for the given Channel ID, Hash and Date
     *
     * @param integer $channel_id
     * @param string $hash
     * @param string $date
     * @throws \Exception
     * @return <boolean, integer>
     */
    public function existsChannelHashDate($channel_id, $hash, $date)
    {
        try {
            $SQL = "SELECT `counter_id` FROM `".self::$table_name."` WHERE `channel_id`=$channel_id AND ".
                "`counter_hash`='$hash' AND `counter_date`='$date'";
            $counter_id = $this->app['db']->fetchColumn($SQL);
            return ($counter_id > 0) ? $counter_id : false;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Insert a new RSS Channel counter record
     *
     * @param integer $channel_id
     * @param string $hash
     * @param string $date
     * @throws \Exception
     * @return integer Counter ID
     */
    public function insert($channel_id, $hash, $date)
    {
        try {
            $this->app['db']->insert(self::$table_name, array(
                'channel_id' => $channel_id,
                'counter_hash' => $hash,
                'counter_count' => 1,
                'counter_date' => $date
            ));
            return $this->app['db']->lastInsertId();
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Increase the counter field by one for the given counter ID
     *
     * @param integer $counter_id
     * @throws \Exception
     */
    public function increase($counter_id)
    {
        try {
            $SQL = "UPDATE `".self::$table_name."` SET `counter_count`=`counter_count`+1 WHERE `counter_id`=$counter_id";
            $this->app['db']->query($SQL);
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Track the remote address (hashed IP) for the given RSS Channel ID
     *
     * @param integer $channel_id
     * @throws \Exception
     */
    public function trackRemoteAddress($channel_id)
    {
        try {
            $hash = md5($_SERVER['REMOTE_ADDR']);
            $date = date('Y-m-d');

            if (false === ($counter_id = $this->existsChannelHashDate($channel_id, $hash, $date))) {
                // create a new record
                $counter_id = $this->insert($channel_id, $hash, $date);
            }
            else {
                // increase the counter
                $this->increase($counter_id);
            }
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Delete records with the given Channel ID and Counter Date
     *
     * @param integer $channel_id
     * @param string $counter_date date
     * @throws \Exception
     */
    public function deleteChannelDate($channel_id, $counter_date)
    {
        try {
            $this->app['db']->delete(self::$table_name, array(
                'channel_id' => $channel_id,
                'counter_date' => $counter_date
            ));
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Cleanup the records - sum up records from dates in the past and insert
     * them into the RSS Channel Statistic and then delete this records from
     * the Counter
     *
     * @param integer $channel_id
     * @throws \Exception
     */
    public function cleanup($channel_id)
    {
        try {
            $SQL = "SELECT `counter_date` FROM `".self::$table_name."` WHERE `channel_id`=$channel_id ".
                "AND `counter_date`<'".date('Y-m-d')."' GROUP BY `counter_date`";
            $dates = $this->app['db']->fetchAll($SQL);
            if (is_array($dates)) {
                $StatisticData = new RSSChannelStatistic($this->app);
                foreach ($dates as $date) {
                    $SQL = "SELECT COUNT(`counter_hash`) AS `callers`, SUM(`counter_count`) AS `views` ".
                        "FROM `".self::$table_name."` WHERE `counter_date`='".$date['counter_date']."' ".
                        "AND `channel_id`=$channel_id";
                    $statistic = $this->app['db']->fetchAssoc($SQL);
                    if (is_array($statistic)) {
                        // add the data to the statistic table
                        $StatisticData->insert($channel_id, $statistic['callers'], $statistic['views'], $date['counter_date']);
                        // delete the old records
                        $this->deleteChannelDate($channel_id, $date['counter_date']);
                    }
                }
            }
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }
}
