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

class RSSViewStatistic
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
        self::$table_name = FRAMEWORK_TABLE_PREFIX.'flexcontent_rss_view_statistic';
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
        `content_id` INT(11) NOT NULL DEFAULT -1,
        `content_callers` INT(11) NOT NULL DEFAULT 0,
        `content_views` INT(11) NOT NULL DEFAULT 0,
        `content_date` DATE NOT NULL DEFAULT '0000-00-00',
        `timestamp` TIMESTAMP,
        PRIMARY KEY (`statistic_id`),
        INDEX (`content_id`, `content_date`)
        )
    COMMENT='Statistic for the flexContent Content Views referred by RSS Links'
    ENGINE=InnoDB
    AUTO_INCREMENT=1
    DEFAULT CHARSET=utf8
    COLLATE='utf8_general_ci'
EOD;
        try {
            $this->app['db']->query($SQL);
            $this->app['monolog']->addInfo("Created table 'flexcontent_rss_view_statistic'", array(__METHOD__, __LINE__));
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
     * Insert a statistic record for the given Content ID
     *
     * @param integer $content_id
     * @param integer $content_callers
     * @param integer $content_views
     * @param string $content_date date
     * @throws \Exception
     */
    public function insert($content_id, $content_callers, $content_views, $content_date)
    {
        try {
            $this->app['db']->insert(self::$table_name, array(
                'content_id' => $content_id,
                'content_callers' => $content_callers,
                'content_views' => $content_views,
                'content_date' => $content_date
            ));
            return $this->app['db']->lastInsertId();
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }
}
