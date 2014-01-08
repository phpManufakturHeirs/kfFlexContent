<?php

/**
 * flexContent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/event
 * @copyright 2014 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\flexContent\Data\Import;

use Silex\Application;
use phpManufaktur\Basic\Data\CMS\Page;

class ImportControl
{
    protected $app = null;
    protected $WYSIWYG = null;

    protected static $table_name = null;


    public function __construct(Application $app)
    {
        $this->app = $app;
        self::$table_name = FRAMEWORK_TABLE_PREFIX.'flexcontent_import_control';

        $this->WYSIWYG = new WYSIWYG($app);
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
        `import_id` INT(11) NOT NULL AUTO_INCREMENT,
        `identifier_type` ENUM('WYSIWYG','NEWS','TOPICS','UNKNOWN') DEFAULT 'UNKNOWN',
        `identifier_id` INT(11) NOT NULL DEFAULT '-1',
        `identifier_language` VARCHAR(2) NOT NULL DEFAULT 'EN',
        `import_status` ENUM('PENDING','IGNORE','IMPORTED') DEFAULT 'PENDING',
        `timestamp` TIMESTAMP,
        PRIMARY KEY (`import_id`),
        INDEX (`identifier_id`, `identifier_type`, `identifier_language`)
        )
    COMMENT='flexContent control table for importing foreign articles'
    ENGINE=InnoDB
    AUTO_INCREMENT=1
    DEFAULT CHARSET=utf8
    COLLATE='utf8_general_ci'
EOD;
        try {
            $this->app['db']->query($SQL);
            $this->app['monolog']->addInfo("Created table 'flexcontent_import_control'", array(__METHOD__, __LINE__));
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
     * Get the ENUM values of import_status as associated array for usage in form
     *
     * @return array
     */
    public function getStatusValuesForForm()
    {
        $enums = $this->app['db.utils']->getEnumValues(self::$table_name, 'import_status');
        $result = array();
        foreach ($enums as $enum) {
            $result[$enum] = $enum;
        }
        return $result;
    }

    /**
     * Get the ENUM values of import_type as associated array for usage in form
     *
     * @return array
     */
    public function getTypeValuesForForm()
    {
        $enums = $this->app['db.utils']->getEnumValues(self::$table_name, 'identifier_type');
        $result = array();
        foreach ($enums as $enum) {
            $result[$enum] = $enum;
        }
        return $result;
    }

    /**
     * Select the import control record for the given ID
     *
     * @param integer $import_id
     * @throws \Exception
     * @return Ambigous <boolean, array>
     */
    public function select($import_id)
    {
        try {
            $SQL = "SELECT * FROM `".self::$table_name."` WHERE `import_id`='$import_id'";
            $import = $this->app['db']->fetchAssoc($SQL);
            return (isset($import['import_id'])) ? $import : false;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
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
            $insert = array();
            foreach ($data as $key => $value) {
                if (($key == 'import_id') || ($key == 'timestamp')) {
                    continue;
                }
                $insert[$key] = (is_string($value)) ? $this->app['utils']->sanitizeText($value) : $value;
            }
            if (empty($insert)) {
                return false;
            }
            $this->app['db']->insert(self::$table_name, $insert);
            $id = $this->app['db']->lastInsertId();
            return $id;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Delete the record with the given $import_id
     *
     * @param integer $import_id
     * @throws \Exception
     */
    public function delete($import_id)
    {
        try {
            $this->app['db']->delete(self::$table_name, array('import_id' => $import_id));
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Update the given record with the given $import_id
     *
     * @param integer $import_id
     * @param array $data
     * @throws \Exception
     */
    public function update($import_id, $data)
    {
        try {
            $update = array();
            foreach ($data as $key => $value) {
                if ($key == 'import_id') {
                    continue;
                }
                $update[$key] = is_string($value) ? $this->app['utils']->sanitizeText($value) : $value;
            }
            $this->app['db']->update(self::$table_name, $update, array('import_id' => $import_id));
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Check if the import record with given parameters exists
     *
     * @param string $identifier_type
     * @param integer $identifier_id
     * @param string $identifier_language
     * @return Ambigous <boolean, integer>
     */
    public function existsRecord($identifier_type, $identifier_id, $identifier_language)
    {
        try {
            $SQL = "SELECT `import_id` FROM `".self::$table_name."` WHERE ".
                "`identifier_type`='$identifier_type' AND `identifier_id`=$identifier_id AND ".
                "`identifier_language`='$identifier_language'";
            $import_id = $this->app['db']->fetchColumn($SQL);
            return ($import_id > 0) ? $import_id : false;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select all records
     *
     * @throws \Exception
     */
    public function selectAll()
    {
        try {
            $SQL = "SELECT * FROM `".self::$table_name."`";
            return $this->app['db']->fetchAll($SQL);
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Check the import table for WYSIWYG pages, add new pages, remove deleted pages
     *
     * @param string $language
     * @throws \Exception
     */
    public function checkWYSIWYGpages($language)
    {
        try {
            $pages = $this->WYSIWYG->selectWYSIWYGpages($language);
            $page_ids = array();
            foreach ($pages as $page) {
                $page_ids[] = $page['page_id'];
                if (!$this->existsRecord('WYSIWYG', $page['page_id'], $page['language'])) {
                    // insert a new record
                    $data = array(
                        'identifier_type' => 'WYSIWYG',
                        'identifier_id' => $page['page_id'],
                        'identifier_language' => strtoupper($page['language'])
                    );
                    $this->insert($data);
                }
            }
            $imports = $this->selectAll();
            foreach ($imports as $import) {
                if (($import['identifier_type'] == 'WYSIWYG') &&
                    (strtoupper($import['identifier_language']) == strtoupper($language)) &&
                    !in_array($import['identifier_id'], $page_ids)) {
                    // delete this record, the page does no longer exists!
                    $this->delete($import['import_id']);
                }
            }
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select the WYSIWYG import control list for the given language and status
     *
     * @param string $language
     * @param string $status PENDING, IGNORE or IMPORTED
     * @throws \Exception
     * @return array
     */
    protected function selectWYSIWYGimportControlList($language, $status)
    {
        try {
            $import = self::$table_name;
            $pages = CMS_TABLE_PREFIX.'pages';
            $SQL = "SELECT `import_id`,`import_status`,`identifier_type`,`identifier_id`,`identifier_language`,`timestamp`,".
                "`link` AS `identifier_link`,`page_title` AS `identifier_title`, `modified_when` AS `identifier_modified` ".
                "FROM `$import`, `$pages` WHERE `identifier_language`='$language' AND `import_status`='$status' AND ".
                "identifier_id=page_id ORDER BY `link` ASC";
            $results = $this->app['db']->fetchAll($SQL);
            $Page = new Page($this->app);
            $page_directory = $Page->getPageDirectory();
            $page_extension = $Page->getPageExtension();
            $list = array();
            foreach ($results as $result) {
                $item = array();
                foreach ($result as $key => $value) {
                    if ($key == 'identifier_link') {
                        $item[$key] = $page_directory.$value.$page_extension;
                        $item['identifier_url'] = CMS_URL.$page_directory.$value.$page_extension;
                    }
                    elseif ($key == 'identifier_modified') {
                        $item[$key] = date('Y-m-d H:i:s', $value);
                    }
                    else {
                        $item[$key] = (is_string($value)) ? $this->app['utils']->unsanitizeText($value) : $value;
                    }
                }
                $list[] = $item;
            }
            return $list;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select the import control list for the given language, type and status
     *
     * @param string $language
     * @param string $type WYSIWYG, NEWS, TOPICS or UNKNOWN
     * @param string $status PENDING, IGNORE or IMPORTED
     * @throws \UnexpectedValueException
     * @return array
     */
    public function selectImportControlList($language, $type, $status)
    {
        switch ($type) {
            case 'WYSIWYG':
                return $this->selectWYSIWYGimportControlList($language, $status);
            case 'NEWS':
                return array();
            case 'TOPICS':
                return array();
            case 'UNKNOWN':
                return array();
            default:
                throw new \UnexpectedValueException("The type $type is not supported for the import control list!");
        }
    }


    /**
     * Select the content for the given import ID
     *
     * @param integer $import_id
     * @throws \UnexpectedValueException
     * @return boolean
     */
    public function selectContentData($import_id)
    {
        if (false === ($import = $this->select($import_id))) {
            return false;
        }
        switch ($import['identifier_type']) {
            case 'WYSIWYG':
                // return WYSIWYG content
                $WYSIWYG = new WYSIWYG($this->app);
                return $WYSIWYG->selectPageID($import['identifier_id']);
            default:
                // unknown type ...
                throw new \UnexpectedValueException("The type ".$import['identifier_type']." is not supported for the import control list!");
        }
    }

}
