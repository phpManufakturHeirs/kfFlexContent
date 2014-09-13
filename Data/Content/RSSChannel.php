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

class RSSChannel
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
        self::$table_name = FRAMEWORK_TABLE_PREFIX.'flexcontent_rss_channel';
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
        `channel_id` INT(11) NOT NULL AUTO_INCREMENT,
        `channel_category` VARCHAR(255) NOT NULL DEFAULT '',
        `channel_copyright` VARCHAR(255) NOT NULL DEFAULT '',
        `channel_description` TEXT NOT NULL,
        `channel_image` TEXT NOT NULL,
        `language` VARCHAR(2) NOT NULL DEFAULT 'EN',
        `channel_limit` INT(11) NOT NULL DEFAULT '50',
        `channel_link` VARCHAR(255) NOT NULL DEFAULT '',
        `channel_title` VARCHAR(255) NOT NULL DEFAULT '',
        `channel_webmaster` VARCHAR(255) NOT NULL DEFAULT '',
        `status` ENUM ('ACTIVE','LOCKED','DELETED') NOT NULL DEFAULT 'LOCKED',
        `content_categories` TEXT NOT NULL,
        `timestamp` TIMESTAMP,
        PRIMARY KEY (`channel_id`)
        )
    COMMENT='RSS Channels used by flexContent'
    ENGINE=InnoDB
    AUTO_INCREMENT=1
    DEFAULT CHARSET=utf8
    COLLATE='utf8_general_ci'
EOD;
        try {
            $this->app['db']->query($SQL);
            $this->app['monolog']->addInfo("Created table 'flexcontent_rss_channel'", array(__METHOD__, __LINE__));
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
     * Get the columns of the category type table
     *
     * @param string $table
     * @throws \Exception
     * @return array
     */
    public function getColumns()
    {
        return $this->app['db.utils']->getColumns(self::$table_name);
    }

    /**
     * Count the records in the table
     *
     * @throws \Exception
     * @return integer number of records
     */
    public function count()
    {
        try {
            $SQL = "SELECT COUNT(*) FROM `".self::$table_name."`";
            return $this->app['db']->fetchColumn($SQL);
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Get the ENUM values of field publish_type as associated array for
     * usage in form
     *
     * @return array
     */
    public function getStatusTypeValuesForForm()
    {
        $enums = $this->app['db.utils']->getEnumValues(self::$table_name, 'status');
        $result = array();
        foreach ($enums as $enum) {
            $result[$enum] = $enum;
        }
        return $result;
    }

    /**
     * Count ChannelLinks which starts LIKE the given $channellink
     *
     * @param string $this
     * @throws \Exception
     */
    public function countChannelLinksLikeThis($channellink, $language)
    {
        try {
            $SQL = "SELECT COUNT(`channel_link`) FROM `".self::$table_name."` WHERE `channel_link` LIKE '$channellink%' ".
                "AND `language`='$language'";
            return $this->app['db']->fetchColumn($SQL);
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Check if the given channel link already exists
     *
     * @param string $channellink
     * @param unknown $language
     * @throws \Exception
     * @return boolean
     */
    public function existsChannelLink($channellink, $language)
    {
        try {
            $SQL = "SELECT `channel_link` FROM `".self::$table_name."` WHERE `channel_link`='$channellink' AND `language`='$language'";
            $result = $this->app['db']->fetchColumn($SQL);
            return ($result == $channellink);
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    public function selectList($columns, $order_by=null, $order_direction='ASC', $status=array('ACTIVE','LOCKED'))
    {
        try {
            $in_status = "('".implode("','", $status)."')";
            $SQL = "SELECT * FROM `".self::$table_name."` WHERE `status` IN $in_status";
            if (is_array($order_by) && !empty($order_by)) {
                $SQL .= " ORDER BY ";
                $start = true;
                foreach ($order_by as $by) {
                    if (!$start) {
                        $SQL .= ", ";
                    }
                    else {
                        $start = false;
                    }
                    $SQL .= "$by";
                }
                $SQL .= " $order_direction";
            }
            $results = $this->app['db']->fetchAll($SQL);

            $channels = array();
            foreach ($results as $result) {
                $channel = array();
                foreach ($columns as $column) {
                    if ($column == 'content_categories') {
                        $channel[$column] = (strpos($result[$column], ',')) ? explode(',', $result[$column]) : array($result[$column]);
                    }
                    else {
                        foreach ($result as $key => $value) {
                            if ($key == $column) {
                                $channel[$key] = is_string($value) ? $this->app['utils']->unsanitizeText($value) : $value;
                            }
                        }
                    }
                }
                $channels[] = $channel;
            }
            return $channels;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select the RSS Channel ID by the given Channel Link
     *
     * @param string $channel_link
     * @throws \Exception
     * @return Ambigous <boolean, array>
     */
    public function selectChannelIDbyChannelLink($channel_link, $language, $status='ACTIVE')
    {
        try {
            $SQL = "SELECT `channel_id` FROM `".self::$table_name."` WHERE `channel_link`='$channel_link' ".
                "AND `language`='$language' AND `status`='$status'";
            $result = $this->app['db']->fetchColumn($SQL);
            return ($result > 0) ? $result : false;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Insert a new record
     *
     * @param array $data
     * @param integer reference $channel_id
     * @throws \Exception
     */
    public function insert($data, &$channel_id)
    {
        try {
            $insert = array();
            $not_null = array('channel_image', 'channel_category', 'channel_copyright', 'channel_webmaster');
            foreach ($not_null as $key) {
                if (is_null($data[$key])) {
                    $data[$key] = '';
                }
            }

            foreach ($data as $key => $value) {
                if (($key == 'channel_id') || ($key == 'timestamp')) continue;
                $insert[$this->app['db']->quoteIdentifier($key)] = is_string($value) ? $this->app['utils']->sanitizeText($value) : $value;
            }
            $this->app['db']->insert(self::$table_name, $insert);
            $channel_id = $this->app['db']->lastInsertId();
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Update the given RSS Channel record
     *
     * @param integer $channel_id
     * @param array $data
     * @throws \Exception
     */
    public function update($channel_id, $data)
    {
        try {
            $update = array();
            foreach ($data as $key => $value) {
                if (($key == 'channel_id') || ($key == 'timestamp')) {
                    continue;
                }
                $update[$key] = is_string($value) ? $this->app['utils']->sanitizeText($value) : $value;
            }
            $this->app['db']->update(self::$table_name, $update, array('channel_id' => $channel_id));
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select a RSS Channel record by the given ID
     *
     * @param integer $channel_id
     * @throws \Exception
     * @return Ambigous <boolean, array>
     */
    public function select($channel_id) {
        try {
            $SQL = "SELECT * FROM `".self::$table_name."` WHERE `channel_id`='$channel_id'";
            $result = $this->app['db']->fetchAssoc($SQL);
            $channel = array();
            foreach ($result as $key => $value) {
                $channel[$key] = is_string($value) ? $this->app['utils']->unsanitizeText($value) : '';
            }
            return (!empty($channel)) ? $channel : false;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select the RSS Channel for creating an XML Feed
     *
     * @param integer $channel_id
     * @param integer $channel_limit the maximumn channel items
     * @param array reference $channel_data will hold the channel record
     * @param array reference $channel_items will hold the channel item records
     * @throws \Exception
     * @return boolean
     */
    public function selectChannel($channel_id, $channel_limit, &$channel_data=array(), &$channel_items=array())
    {
        try {
            $channel_table = self::$table_name;
            $content_table = FRAMEWORK_TABLE_PREFIX.'flexcontent_content';
            $category = FRAMEWORK_TABLE_PREFIX.'flexcontent_category';

            $SQL = "SELECT * FROM `$channel_table` WHERE `channel_id`='$channel_id' AND `status`='ACTIVE'";
            $result = $this->app['db']->fetchAssoc($SQL);
            if (!isset($result['channel_id'])) {
                // no data for this channel ID!
                return false;
            }
            $channel_data = array();
            foreach ($result as $key => $value) {
                $channel_data[$key] = is_string($value) ? $this->app['utils']->unsanitizeText($value) : $value;
            }

            $categories = strpos($channel_data['content_categories'], ',') ?
                explode(',', $channel_data['content_categories']) : array($channel_data['content_categories']);
            $in_categories = '('.implode(',', $categories).')';

            $SQL = "SELECT `$content_table`.content_id,title,permalink,redirect_url,publish_from,teaser,teaser_image,author_username ".
                "FROM `$channel_table`, `$content_table`, `$category` WHERE `$category`.content_id=`$content_table`.content_id ".
                "AND rss='YES' AND `$channel_table`.channel_id='$channel_id' AND `$channel_table`.status='ACTIVE' AND ".
                "`$category`.category_id IN $in_categories AND `$content_table`.status IN ('PUBLISHED','BREAKING') ".
                "GROUP BY `$content_table`.content_id ORDER BY `publish_from` DESC LIMIT $channel_limit";

            $results = $this->app['db']->fetchAll($SQL);
            $channel_items = array();
            foreach ($results as $result) {
                $content = array();
                foreach ($result as $key => $value) {
                    $content[$key] = is_string($value) ? $this->app['utils']->unsanitizeText($value) : $value;
                }
                $channel_items[] = $content;
            }
            return true;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }
}

