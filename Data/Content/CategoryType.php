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
use phpManufaktur\flexContent\Data\Content\Category;

class CategoryType
{
    protected $app = null;
    protected static $table_name = null;

    public static $forbidden_chars = array('|',';');

    /**
     * Constructor
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        self::$table_name = FRAMEWORK_TABLE_PREFIX.'flexcontent_category_type';
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
        `category_id` INT(11) NOT NULL AUTO_INCREMENT,
        `category_name` VARCHAR(64) NOT NULL DEFAULT '',
        `category_description` TEXT NOT NULL DEFAULT '',
        `category_image` TEXT NOT NULL,
        `target_url` TEXT NOT NULL,
        `timestamp` TIMESTAMP,
        PRIMARY KEY (`category_id`),
        UNIQUE INDEX (`category_name`)
        )
    COMMENT='The category types used by the flexContent records'
    ENGINE=InnoDB
    AUTO_INCREMENT=1
    DEFAULT CHARSET=utf8
    COLLATE='utf8_general_ci'
EOD;
        try {
            $this->app['db']->query($SQL);
            $this->app['monolog']->addInfo("Created table 'flexcontent_category_type'", array(__METHOD__, __LINE__));
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
     * Select a list from the TagType table in paging view
     *
     * @param integer $limit_from start selection at position
     * @param integer $rows_per_page select max. rows per page
     * @param array $order_by fields to order by
     * @param string $order_direction 'ASC' (default) or 'DESC'
     * @throws \Exception
     * @return array selected records
     */
    public function selectList($limit_from, $rows_per_page, $order_by=null, $order_direction='ASC', $columns)
    {
        try {
            $SQL = "SELECT * FROM `".self::$table_name."`";
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
            $SQL .= " LIMIT $limit_from, $rows_per_page";
            $results = $this->app['db']->fetchAll($SQL);

            $CategoryData = new Category($this->app);

            $categories = array();
            foreach ($results as $result) {
                $category = array();
                foreach ($columns as $column) {
                    if ($column == 'used_by_content_id') {
                        // get all flexContent ID's which are using this CATEGORY
                        $category['used_by_content_id'] = $CategoryData->selectByCategoryID($result['category_id']);
                    }
                    else {
                        foreach ($result as $key => $value) {
                            if ($key == $column) {
                                $category[$key] = is_string($value) ? $this->app['utils']->unsanitizeText($value) : $value;
                            }
                        }
                    }
                }
                $categories[] = $category;
            }
            return $categories;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select a Category Type record by the given TAG ID
     *
     * @param integer $category_id
     * @throws \Exception
     * @return Ambigous <boolean, array>
     */
    public function select($category_id) {
        try {
            $SQL = "SELECT * FROM `".self::$table_name."` WHERE `category_id`='$category_id'";
            $result = $this->app['db']->fetchAssoc($SQL);
            $category = array();
            foreach ($result as $key => $value) {
                $category[$key] = is_string($value) ? $this->app['utils']->unsanitizeText($value) : '';
            }
            return (!empty($category)) ? $category : false;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Delete the record with the given CATEGORY ID
     *
     * @param integer $category_id
     * @throws \Exception
     */
    public function delete($category_id)
    {
        try {
            $this->app['db']->delete(self::$table_name, array('category_id' => $category_id));
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Check if a CATEGORY NAME already exists
     *
     * @param string $category_name
     * @throws \Exception
     * @return boolean
     */
    public function existsName($category_name)
    {
        try {
            $SQL = "SELECT `category_name` FROM `".self::$table_name."` WHERE LOWER(`category_name`) = '".
                $this->app['utils']->sanitizeVariable(strtolower($category_name))."'";
            $category = $this->app['db']->fetchColumn($SQL);
            return !empty($category);
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Insert a new record
     *
     * @param array $data
     * @param integer reference $category_id
     * @throws \Exception
     */
    public function insert($data, &$category_id)
    {
        try {
            $insert = array();
            foreach ($data as $key => $value) {
                if (($key == 'category_id') || ($key == 'timestamp')) continue;
                if ($key == 'category_name') {
                    foreach (self::$forbidden_chars as $forbidden) {
                        if (false !== strpos($value, $forbidden)) {
                            throw new \Exception("The category name $value contains the forbidden character : $forbidden");
                        }
                    }
                }
                $insert[$this->app['db']->quoteIdentifier($key)] = is_string($value) ? $this->app['utils']->sanitizeText($value) : $value;
            }
            $this->app['db']->insert(self::$table_name, $insert);
            $category_id = $this->app['db']->lastInsertId();
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Update the given CATEGORY TYPE record
     *
     * @param integer $category_id
     * @param array $data
     * @throws \Exception
     */
    public function update($category_id, $data)
    {
        try {
            $update = array();
            foreach ($data as $key => $value) {
                if ($key == 'category_id') {
                    continue;
                }
                $update[$key] = is_string($value) ? $this->app['utils']->sanitizeText($value) : $value;
            }
            $this->app['db']->update(self::$table_name, $update, array('category_id' => $category_id));
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Get the categories for a SELECT in form.factory / Twig
     *
     * @throws \Exception
     * @return multitype:NULL
     */
    public function getListForSelect()
    {
        try {
            $SQL = "SELECT `category_id`, `category_name` FROM `".self::$table_name."` ORDER BY `category_name` ASC";
            $results = $this->app['db']->fetchAll($SQL);
            $categories = array();
            foreach ($results as $category) {
                $categories[$category['category_id']] = $this->app['utils']->unsanitizeText($category['category_name']);
            }
            return $categories;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

}
