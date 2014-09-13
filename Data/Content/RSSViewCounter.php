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

class RSSViewCounter
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
        self::$table_name = FRAMEWORK_TABLE_PREFIX.'flexcontent_rss_view_counter';
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
        `view_id` INT(11) NOT NULL AUTO_INCREMENT,
        `content_id` INT(11) NOT NULL DEFAULT -1,
        `view_hash` VARCHAR(32) NOT NULL DEFAULT '',
        `view_count` INT(11) NOT NULL DEFAULT 0,
        `view_date` DATE NOT NULL DEFAULT '0000-00-00',
        `timestamp` TIMESTAMP,
        PRIMARY KEY (`view_id`),
        INDEX (`content_id`, `view_hash`)
        )
    COMMENT='Counter for views of flexContent Content referred by an RSS Channel link'
    ENGINE=InnoDB
    AUTO_INCREMENT=1
    DEFAULT CHARSET=utf8
    COLLATE='utf8_general_ci'
EOD;
        try {
            $this->app['db']->query($SQL);
            $this->app['monolog']->addInfo("Created table 'flexcontent_rss_view_counter'", array(__METHOD__, __LINE__));
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
     * Check if a record exists for the given Content ID, Hash and Date
     *
     * @param integer $content_id
     * @param string $hash
     * @param string $date
     * @throws \Exception
     * @return <boolean, integer>
     */
    public function existsContentHashDate($content_id, $hash, $date)
    {
        try {
            $SQL = "SELECT `view_id` FROM `".self::$table_name."` WHERE `content_id`=$content_id AND ".
                "`view_hash`='$hash' AND `view_date`='$date'";
            $view_id = $this->app['db']->fetchColumn($SQL);
            return ($view_id > 0) ? $view_id : false;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Insert a new RSS View counter record
     *
     * @param integer $content_id
     * @param string $hash
     * @param string $date
     * @throws \Exception
     * @return integer Counter ID
     */
    public function insert($content_id, $hash, $date)
    {
        try {
            $this->app['db']->insert(self::$table_name, array(
                'content_id' => $content_id,
                'view_hash' => $hash,
                'view_count' => 1,
                'view_date' => $date
            ));
            return $this->app['db']->lastInsertId();
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Increase the counter field by one for the given view ID
     *
     * @param integer $view_id
     * @throws \Exception
     */
    public function increase($view_id)
    {
        try {
            $SQL = "UPDATE `".self::$table_name."` SET `view_count`=`view_count`+1 WHERE `view_id`=$view_id";
            $this->app['db']->query($SQL);
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Track the remote address (hashed IP) for the given Content ID
     *
     * @param integer $channel_id
     * @throws \Exception
     */
    public function trackRemoteAddress($content_id)
    {
        try {
            $hash = md5($_SERVER['REMOTE_ADDR']);
            $date = date('Y-m-d');

            if (false === ($view_id = $this->existsContentHashDate($content_id, $hash, $date))) {
                // create a new record
                $view_id = $this->insert($content_id, $hash, $date);
            }
            else {
                // increase the counter
                $this->increase($view_id);
            }
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Delete records with the given Content ID and View Date
     *
     * @param integer $content_id
     * @param string $view_date date
     * @throws \Exception
     */
    public function deleteContentDate($content_id, $view_date)
    {
        try {
            $this->app['db']->delete(self::$table_name, array(
                'content_id' => $content_id,
                'view_date' => $view_date
            ));
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Cleanup the records - sum up records from dates in the past and insert
     * them into the RSS Content View Statistic and then delete this records from
     * the Counter
     *
     * @param integer $content_id
     * @throws \Exception
     */
    public function cleanup($content_id)
    {
        try {
            $SQL = "SELECT `view_date` FROM `".self::$table_name."` WHERE `content_id`=$content_id ".
                "AND `view_date`<'".date('Y-m-d')."' GROUP BY `view_date`";
            $dates = $this->app['db']->fetchAll($SQL);
            if (is_array($dates)) {
                $StatisticData = new RSSViewStatistic($this->app);
                foreach ($dates as $date) {
                    $SQL = "SELECT COUNT(`view_hash`) AS `callers`, SUM(`view_count`) AS `views` ".
                        "FROM `".self::$table_name."` WHERE `view_date`='".$date['view_date']."' ".
                        "AND `content_id`=$content_id";
                    $statistic = $this->app['db']->fetchAssoc($SQL);
                    if (is_array($statistic)) {
                        // add the data to the statistic table
                        $StatisticData->insert($content_id, $statistic['callers'], $statistic['views'], $date['view_date']);
                        // delete the old records
                        $this->deleteContentDate($content_id, $date['view_date']);
                    }
                }
            }
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }
}
