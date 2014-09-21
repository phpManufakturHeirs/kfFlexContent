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

class Category
{
    protected $app = null;
    protected static $table_name = null;
    protected $CategoryType = null;

    /**
     * Constructor
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        self::$table_name = FRAMEWORK_TABLE_PREFIX.'flexcontent_category';
        $this->CategoryType = new CategoryType($app);
    }

    /**
     * Create the table
     *
     * @throws \Exception
     */
    public function createTable()
    {
        $table = self::$table_name;
        $table_category_type = FRAMEWORK_TABLE_PREFIX.'flexcontent_category_type';
        $table_content = FRAMEWORK_TABLE_PREFIX.'flexcontent_content';
        $SQL = <<<EOD
    CREATE TABLE IF NOT EXISTS `$table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `category_id` INT(11) NOT NULL DEFAULT '-1',
        `is_primary` TINYINT(1) NOT NULL DEFAULT '0',
        `content_id` INT(11) NOT NULL DEFAULT '-1',
        `timestamp` TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX (`category_id`, `content_id`),
        CONSTRAINT
            FOREIGN KEY (`category_id`)
            REFERENCES `$table_category_type` (`category_id`)
            ON DELETE CASCADE,
        CONSTRAINT
            FOREIGN KEY (`content_id`)
            REFERENCES `$table_content` (`content_id`)
            ON DELETE CASCADE
        )
    COMMENT='The categories used by the flexContent records'
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
     * Insert a new record
     *
     * @param array $data
     * @param integer reference $id
     * @throws \Exception
     */
    public function insert($data, &$id=null)
    {
        try {
            $this->app['db']->insert(self::$table_name, $data);
            $id = $this->app['db']->lastInsertId();
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Delete Categories by the given content_id
     *
     * @param integer $content_id
     * @throws \Exception
     */
    public function deleteByContentID($content_id)
    {
        try {
            $this->app['db']->delete(self::$table_name, array('content_id' => $content_id));
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Delete Categories by the given Category TYPE ID
     *
     * @param integer $category_id
     * @throws \Exception
     */
    public function deleteByCategoryID($category_id)
    {
        try {
            $this->app['db']->delete(self::$table_name, array('category_id' => $category_id));
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Delete ID by the given CONTENT ID and CATEGORY ID
     *
     * @param integer $content_id
     * @param integer $category_id
     * @throws \Exception
     */
    public function deleteByContentIDandCategoryID($content_id, $category_id)
    {
        try {
            $this->app['db']->delete(self::$table_name, array(
                'content_id' => $content_id,
                'category_id' => $category_id
            ));
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select the flexContent IDs which are using the given CATEGORY ID
     *
     * @param integer $category_id
     * @param boolean $primary_only default false
     * @throws \Exception
     * @return array with content IDs
     */
    public function selectByCategoryID($category_id, $primary_only=false)
    {
        try {
            $SQL = "SELECT `content_id` FROM `".self::$table_name."` WHERE `category_id`='$category_id' ";
            if ($primary_only) {
                $SQL .= "AND `is_primary`=1 ";
            }
            $SQL .= "ORDER BY `content_id` ASC";
            $results = $this->app['db']->fetchAll($SQL);
            $ids = array();
            foreach ($results as $result) {
                $ids[] = $result['content_id'];
            }
            return $ids;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select the category records for the given flexContent ID
     *
     * @param integer $content_id
     * @throws \Exception
     * @return array
     */
    public function selectByContentID($content_id)
    {
        try {
            $SQL = "SELECT * FROM `".self::$table_name."` WHERE `content_id`='$content_id' ORDER BY `is_primary` DESC, `category_name` ASC";
            $results = $this->app['db']->fetchAll($SQL);
            $categories = array();
            foreach ($results as $result) {
                $category = array();
                foreach ($result as $key => $value) {
                    $category[$key] = is_string($value) ? $this->app['utils']->unsanitizeText($value) : $value;
                }
                $categories[] = $category;
            }
            return $categories;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select the primary category ID for the given flexContent ID
     *
     * @param integer $content_id
     * @throws \Exception
     * @return Ambigous <boolean, integer>
     */
    public function selectPrimaryCategoryIDbyContentID($content_id)
    {
        try {
            $SQL = "SELECT `category_id` FROM `".self::$table_name."` WHERE `content_id`='$content_id' AND `is_primary`='1'";
            $id = $this->app['db']->fetchColumn($SQL);
            return ($id > 0) ? $id : false;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select the secondary category ID's for the given flexContent ID
     *
     * @param integer $content_id
     * @throws \Exception
     * @return Ambigous <boolean, array>
     */
    public function selectSecondaryCategoryIDsByContentID($content_id)
    {
        try {
            $SQL = "SELECT `category_id` FROM `".self::$table_name."` WHERE `content_id`='$content_id' AND `is_primary`='0'";
            $results = $this->app['db']->fetchAll($SQL);
            $ids = array();
            foreach ($results as $result) {
                $ids[] = $result['category_id'];
            }
            return (!empty($ids)) ? $ids : false;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Update the given CATEGORY record
     *
     * @param integer $id
     * @param array $data
     * @throws \Exception
     */
    public function update($id, $data)
    {
        try {
            $update = array();
            foreach ($data as $key => $value) {
                if ($key == 'id') {
                    continue;
                }
                $update[$key] = is_string($value) ? $this->app['utils']->sanitizeText($value) : $value;
            }
            $this->app['db']->update(self::$table_name, $update, array('id' => $id));
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select the target URL for the given flexContent ID by the primary assigned category
     *
     * @param integer $content_id
     * @throws \Exception
     * @return Ambigous <boolean, string>
     */
    public function selectTargetURLbyContentID($content_id)
    {
        try {
            $category_table = self::$table_name;
            $type_table = FRAMEWORK_TABLE_PREFIX.'flexcontent_category_type';
            $SQL = "SELECT `target_url` FROM $category_table, $type_table WHERE $category_table.category_id=$type_table.category_id ".
                "AND $category_table.is_primary=1 AND `content_id`=$content_id";
            $target_url = $this->app['db']->fetchColumn($SQL);
            return (!empty($target_url)) ? $target_url : false;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select the target URL for the given Category ID
     *
     * @param integer $category_id
     * @throws \Exception
     * @return Ambigous <boolean, string>
     */
    public function selectTargetURLbyCategoryID($category_id)
    {
        try {
            $type_table = FRAMEWORK_TABLE_PREFIX.'flexcontent_category_type';
            $SQL = "SELECT `target_url` FROM $type_table WHERE category_id='$category_id'";
            $target_url = $this->app['db']->fetchColumn($SQL);
            return (!empty($target_url)) ? $target_url : false;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }


    /**
     * Select all categories and type information for the given flexContent ID
     *
     * @param integer $content_id
     * @throws \Exception
     * @return Ambigous <boolean, array>
     */
    public function selectCategoriesByContentID($content_id)
    {
        try {
            $category_table = self::$table_name;
            $type_table = FRAMEWORK_TABLE_PREFIX.'flexcontent_category_type';

            $SQL = "SELECT * FROM `$category_table` ".
                "LEFT JOIN `$type_table` ON `$type_table`.`category_id`=`$category_table`.`category_id` ".
                "WHERE `content_id`=$content_id  ORDER BY `is_primary` DESC, `category_name` ASC";

            $results = $this->app['db']->fetchAll($SQL);
            $categories = array();
            foreach ($results as $result) {
                $category = array();
                foreach ($result as $key => $value) {
                    $category[$key] = is_string($value) ? $this->app['utils']->unsanitizeText($value) : $value;
                }
                $categories[] = $category;
            }
            return (!empty($categories)) ? $categories : false;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select the Category ID by the given PermanentLink
     *
     * @param string $permalink
     * @throws \Exception
     * @return Ambigous <boolean, array>
     */
    public function selectCategoryIDbyPermaLink($permalink)
    {
        return $this->CategoryType->selectCategoryIDbyPermaLink($permalink);
    }

    /**
     * Check if the given category ID is the primary category for the content ID
     *
     * @param integer $category_id
     * @param integer $content_id
     * @throws \Exception
     * @return boolean
     */
    public function isPrimaryCategory($category_id, $content_id)
    {
        try {
            $SQL = "SELECT `is_primary` FROM `".self::$table_name."` WHERE `category_id`=$category_id AND `content_id`=$content_id";
            $result = $this->app['db']->fetchColumn($SQL);
            return ($result == 1);
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }
}
