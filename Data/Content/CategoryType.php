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
        `language` VARCHAR(2) NOT NULL DEFAULT 'EN',
        `category_name` VARCHAR(64) NOT NULL DEFAULT '',
        `category_permalink` VARCHAR(255) NOT NULL DEFAULT '',
        `category_description` TEXT NOT NULL,
        `category_image` TEXT NOT NULL,
        `category_type` ENUM ('DEFAULT','EVENT','FAQ','GLOSSARY') NOT NULL DEFAULT 'DEFAULT',
        `target_url` TEXT NOT NULL,
        `timestamp` TIMESTAMP,
        PRIMARY KEY (`category_id`),
        UNIQUE KEY (`category_name`)
        )
    COMMENT='The category types used by the flexContent records'
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
            $ContentData = new Content($this->app);

            $categories = array();
            foreach ($results as $result) {
                $category = array();
                foreach ($columns as $column) {
                    if ($column == 'used_by_content_id') {
                        // get all flexContent ID's which are using this CATEGORY
                        $content_ids = $CategoryData->selectByCategoryID($result['category_id']);
                        $items = array();
                        foreach ($content_ids as $content_id) {
                            $items[] = array(
                                'content_id' => $content_id,
                                'title' => $ContentData->selectTitleByID($content_id),
                                'is_primary' => $CategoryData->isPrimaryCategory($result['category_id'], $content_id)
                            );
                        }
                        $category['used_by_content_id'] = $items;
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
     * Select a Category Type record by the given CATEGORY ID
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
    public function existsName($category_name, $language)
    {
        try {
            $SQL = "SELECT `category_name` FROM `".self::$table_name."` WHERE LOWER(`category_name`) = '".
                $this->app['utils']->sanitizeVariable(strtolower($category_name))."' ".
                "AND `language`='$language'";
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
                $insert[$key] = is_string($value) ? $this->app['utils']->sanitizeText($value) : $value;
            }
            $not_null = array('category_description', 'category_image', 'target_url');
            foreach ($not_null as $field) {
                if (!isset($insert[$field]) || is_null($insert[$field])) {
                    $insert[$field] = '';
                }
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
     * @param string $language default null
     * @param boolean $main_only select only category type DEFAULT or EVENT
     * @throws \Exception
     * @return multitype:NULL
     */
    public function getListForSelect($language=null, $main_only=false)
    {
        try {
            $SQL = "SELECT `category_id`, `category_name` FROM `".self::$table_name."` ";
            if (!is_null($language)) {
                $SQL .= "WHERE `language`='$language' ";
                if ($main_only) {
                    $SQL .= "AND `category_type` IN ('DEFAULT','EVENT') ";
                }
            }
            elseif ($main_only) {
                $SQL .= "WHERE `category_type` IN ('DEFAULT','EVENT') ";
            }
            $SQL .= "ORDER BY `category_name` ASC";

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

    /**
     * Get the category TYPES for a SELECT in form.factory / Twig
     * @throws \Exception
     * @return array
     */
    public function getTypesForSelect()
    {
        try {
            $enums = $this->app['db.utils']->getEnumValues(self::$table_name, 'category_type');
            $types = array();
            foreach ($enums as $enum) {
                $types[$enum] = $this->app['utils']->humanize($enum);
            }
            return $types;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Check if a permalink already exists
     *
     * @param link $permalink
     * @throws \Exception
     * @return boolean
     */
    public function existsPermaLink($permalink, $language)
    {
        try {
            $SQL = "SELECT `category_permalink` FROM `".self::$table_name."` WHERE `category_permalink`='$permalink' AND `language`='$language'";
            $result = $this->app['db']->fetchColumn($SQL);
            return ($result == $permalink);
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Count PermaLinks which starts LIKE the given $this
     *
     * @param string $this
     * @throws \Exception
     */
    public function countPermaLinksLikeThis($permalink, $language)
    {
        try {
            $SQL = "SELECT COUNT(`category_permalink`) FROM `".self::$table_name."` WHERE ".
                "`language`='$language' AND `category_permalink` LIKE '$permalink%'";
            return $this->app['db']->fetchColumn($SQL);
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
    public function selectCategoryIDbyPermaLink($permalink, $language)
    {
        try {
            $SQL = "SELECT `category_id` FROM `".self::$table_name."` WHERE `category_permalink`='$permalink' ".
                "AND `language`='$language'";
            $result = $this->app['db']->fetchColumn($SQL);
            return ($result > 0) ? $result : false;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select category IDs for the given CMS target link and language
     *
     * @param string $target_link
     * @param string $language
     * @throws \Exception
     * @return Ambigous <boolean, array>
     */
    public function selectCategoryIDsByTargetLink($target_link, $language)
    {
        try {
            $SQL = "SELECT `category_id` FROM `".self::$table_name."` WHERE `target_url`='$target_link' ".
                "AND `language`='$language'";
            $result = $this->app['db']->fetchAll($SQL);
            $categories = array();
            foreach ($result as $category) {
                $categories[] = $category['category_id'];
            }
            return (!empty($categories)) ? $categories : false;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Search for words in the given category ID and return a excert string
     * for the CMS search.
     *
     * @param integer $category_id
     * @param array $words
     * @param boolean $or
     * @throws \Exception
     * @return Ambigous <boolean, string>
     */
    public function cmsSearch($category_id, $words, $or=true)
    {
        try {
            $search = '';
            foreach ($words as $word) {
                if (!empty($search)) {
                    $search .= $or ? ' OR ' : ' AND ';
                }
                $search .= "(`category_name` LIKE '%$word%' OR `category_description` LIKE '%$word%')";
            }
            $SQL = "SELECT `category_name`, `category_description` FROM `".self::$table_name."` WHERE `category_id`='$category_id' ".
                "AND ($search)";
            $results = $this->app['db']->fetchAll($SQL);
            $excerpt = '';
            foreach ($results as $result) {
                $excerpt .= '.'.strip_tags($this->app['utils']->unsanitizeText($result['category_name']));
                $excerpt .= '.'.strip_tags($this->app['utils']->unsanitizeText($result['category_description']));
            }
            return (!empty($excerpt)) ? $excerpt : false;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select the category TYPE for the given category ID
     *
     * @param integer $category_id
     * @throws \Exception
     */
    public function selectType($category_id)
    {
        try {
            $SQL = "SELECT `category_type` FROM `".self::$table_name."` WHERE `category_id`='$category_id'";
            return $this->app['db']->fetchColumn($SQL);
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    public function selectCategoriesByType($category_type)
    {
        try {
            $SQL = "SELECT * FROM `".self::$table_name."` WHERE `category_type`='$category_type'";
            $results = $this->app['db']->fetchAll($SQL);
            $categories = array();
            if (is_array($results)) {
                foreach ($results as $result) {
                    $category = array();
                    foreach ($result as $key => $value) {
                        $category[$key] = is_string($value) ? $this->app['utils']->unsanitizeText($value) : $value;
                    }
                    $categories[] = $category;
                }
            }
            return (!empty($categories)) ? $categories : false;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }
}
