<?php

/**
 * flexContent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/flexContent
 * @copyright 2014 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\flexContent\Data\Import;

use Silex\Application;

class ImportControl
{
    protected $app = null;
    protected $WYSIWYG = null;
    protected $NEWS = null;
    protected $TOPICS = null;

    protected static $table_name = null;

    /**
     * Constructor
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        self::$table_name = FRAMEWORK_TABLE_PREFIX.'flexcontent_import_control';

        $this->WYSIWYG = new WYSIWYG($app);
        $this->NEWS = new News($app);
        $this->TOPICS = new Topics($app);
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
        `identifier_link` TEXT NOT NULL,
        `flexcontent_id` INT(11) NOT NULL DEFAULT -1,
        `import_status` ENUM('PENDING','IGNORE','IMPORTED','DELETED') DEFAULT 'PENDING',
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
            if ($enum == 'DELETED') {
                continue;
            }
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
            if (($enum != 'UNKNOWN') && $this->isInstalled($enum)) {
                $result[$enum] = $enum;
            }
        }
        return $result;
    }

    /**
     * Check if the submitted addon type is installed
     *
     * @param string $type
     * @return boolean
     */
    public function isInstalled($type)
    {
        switch (strtoupper($type)) {
            case 'WYSIWYG':
                return $this->WYSIWYG->isInstalled();
            case 'NEWS':
                return $this->NEWS->isInstalled();
            case 'TOPICS':
                return $this->TOPICS->isInstalled();
            default:
                return false;
        }
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
     * @param string $type WYSIWYG, NEWS or TOPICS
     * @param string $language
     * @throws \Exception
     */
    public function selectAll($type=null, $language=null)
    {
        try {
            if (!is_null($type) && !is_null($language)) {
                $SQL = "SELECT * FROM `".self::$table_name."` WHERE `identifier_type`='$type' ".
                    "AND `identifier_language`='$language'";
            }
            elseif (!is_null($type)) {
                $SQL = "SELECT * FROM `".self::$table_name."` WHERE `identifier_type`='$type'";
            }
            elseif (!is_null($language)) {
                $SQL = "SELECT * FROM `".self::$table_name."` WHERE `identifier_language`='$language'";
            }
            else {
                $SQL = "SELECT * FROM `".self::$table_name."`";
            }
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
    protected function checkWYSIWYGpages($language)
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
                        'identifier_language' => strtoupper($page['language']),
                        'identifier_link' => $this->WYSIWYG->getRelativePageLink($page['page_id'])
                    );
                    $this->insert($data);
                }
            }
            $imports = $this->selectAll('WYSIWYG', $language);
            foreach ($imports as $import) {
                if (!in_array($import['identifier_id'], $page_ids)) {
                    // mark the record as deleted, the page does no longer exists!
                    $data = array(
                        'import_status' => 'DELETED'
                    );
                    $this->update($import['import_id'], $data);
                }
            }
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Check the control table for NEWS posts, add new and remove deleted
     *
     * @param string $language
     * @throws \Exception
     * @return boolean
     */
    protected function checkNewsPosts($language)
    {
        try {
            if (!$this->NEWS->isInstalled()) {
                return false;
            }
            $articles= $this->NEWS->selectNewsPosts($language);
            $post_ids = array();
            foreach ($articles as $article) {
                $post_ids[] = $article['post_id'];
                if (!$this->existsRecord('NEWS', $article['post_id'], $language)) {
                    // insert a new record
                    $data = array(
                        'identifier_type' => 'NEWS',
                        'identifier_id' => $article['post_id'],
                        'identifier_language' => strtoupper($article['language']),
                        'identifier_link' => $this->NEWS->getRelativePostLink($article['post_id'])
                    );
                    $this->insert($data);
                }
            }
            $imports = $this->selectAll('NEWS', $language);
            foreach ($imports as $import) {
                if (!in_array($import['identifier_id'], $post_ids)) {
                    // mark the record as deleted, the article does no longer exists!
                    $data = array(
                        'import_status' => 'DELETED'
                    );
                    $this->update($import['import_id'], $data);
                }
            }
            return true;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Check for existing TOPICS posts and update the control table
     *
     * @param string $language
     * @throws \Exception
     * @return boolean
     */
    protected function checkTopicsPosts($language)
    {
        try {
            if (!$this->TOPICS->isInstalled()) {
                return false;
            }
            $topics = $this->TOPICS->selectTopicsPosts($language);
            $topic_ids = array();
            foreach ($topics as $topic) {
                $topic_ids[] = $topic['topic_id'];
                if (!$this->existsRecord('TOPICS', $topic['topic_id'], $language)) {
                    // insert a new record
                    $data = array(
                        'identifier_type' => 'TOPICS',
                        'identifier_id' => $topic['topic_id'],
                        'identifier_language' => strtoupper($topic['language']),
                        'identifier_link' => $this->TOPICS->getRelativeTopicLink($topic['topic_id'])
                    );
                    $this->insert($data);
                }
            }
            $imports = $this->selectAll('TOPICS', $language);
            foreach ($imports as $import) {
                if (!in_array($import['identifier_id'], $topic_ids)) {
                    // mark the record as deleted, the article does no longer exists!
                    $data = array(
                        'import_status' => 'DELETED'
                    );
                    $this->update($import['import_id'], $data);
                }
            }
            return true;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Check for external articles to enable an import
     *
     * @param string $language
     */
    public function checkExternals($language)
    {
        $this->checkWYSIWYGpages($language);
        $this->checkNewsPosts($language);
        $this->checkTopicsPosts($language);

        $this->checkWYSIWYGpagesForFlexContentCommand($language);
        $this->checkNewsPostsForFlexContentCommand($language);
        $this->checkTopicsForFlexContentCommand($language);
    }

    /**
     * Check PENDING WYSIWYG pages for a flexContent kitCommand and set them
     * to status IGNORE
     *
     * @param string $language
     */
    protected function checkWYSIWYGpagesForFlexContentCommand($language)
    {
         $pages = $this->selectWYSIWYGimportControlList($language, 'PENDING');
         foreach ($pages as $page) {
             if ($this->WYSIWYG->checkPageIDforFlexContentCommand($page['identifier_id'])) {
                 // this WYSIWYG page contain a flexContent kitCommand ...
                 $data = array(
                     'import_status' => 'IGNORE'
                 );
                 $this->update($page['import_id'], $data);
             }
         }
    }

    /**
     * Check PENDING POSTs for a flexContent kitCommand and set then to
     * status IGNORE
     *
     * @param string $language
     */
    protected function checkNewsPostsForFlexContentCommand($language)
    {
        $posts = $this->selectNewsImportControlList($language, 'PENDING');
        foreach ($posts as $post) {
            if ($this->NEWS->checkNewsIDforFlexContentCommand($post['identifier_id'])) {
                // this POST contain a flexContent kitCommand ...
                $data = array(
                    'import_status' => 'IGNORE'
                );
                $this->update($post['import_id'], $data);
            }
        }
    }

    /**
     * Check PENDING TOPICS for a flexContent kitCommand and set then to
     * status IGNORE
     *
     * @param string $language
     */
    protected function checkTopicsForFlexContentCommand($language)
    {
        $topics = $this->selectTopicsImportControlList($language, 'PENDING');
        foreach ($topics as $topic) {
            if ($this->TOPICS->checkTopicIDforFlexContentCommand($topic['identifier_id'])) {
                // this TOPIC contain a flexContent kitCommand ...
                $data = array(
                    'import_status' => 'IGNORE'
                );
                $this->update($topic['import_id'], $data);
            }
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
                "`identifier_link`,`page_title` AS `identifier_title`, `modified_when` AS `identifier_modified` ".
                "FROM `$import`, `$pages` WHERE `identifier_language`='$language' AND `import_status`='$status' AND ".
                "identifier_id=page_id ORDER BY `link` ASC";
            $results = $this->app['db']->fetchAll($SQL);
            $list = array();
            foreach ($results as $result) {
                $item = array();
                foreach ($result as $key => $value) {
                    if ($key == 'identifier_link') {
                        $item[$key] = $value;
                        $item['identifier_url'] = CMS_URL.$value;
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
     * Select the NEWS import control list for the given language and status
     *
     * @param string $language
     * @param string $status
     * @throws \Exception
     * @return array
     */
    protected function selectNewsImportControlList($language, $status)
    {
        try {
            if (!$this->NEWS->isInstalled()) {
                return array();
            }
            $import = self::$table_name;
            $news = CMS_TABLE_PREFIX.'mod_news_posts';
            $SQL = "SELECT `import_id`, `import_status`,`identifier_type`,`identifier_id`,`identifier_language`,`timestamp`,".
                "`identifier_link`,`title` AS `identifier_title`, `posted_when` AS `identifier_modified` ".
                "FROM `$import`, `$news` WHERE `identifier_language`='$language' AND `import_status`='$status' AND ".
                "identifier_id=post_id ORDER BY `link` ASC";
            $results = $this->app['db']->fetchAll($SQL);
            $list = array();
            foreach ($results as $result) {
                $item = array();
                foreach ($result as $key => $value) {
                    if ($key == 'identifier_link') {
                        $item[$key] = $value;
                        $item['identifier_url'] = CMS_URL.$value;
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
     * Select the TOPICS import control list for the given language and status
     *
     * @param string $language
     * @param string $status
     * @throws \Exception
     * @return array
     */
    protected function selectTopicsImportControlList($language, $status)
    {
        try {
            if (!$this->TOPICS->isInstalled()) {
                return array();
            }
            $import = self::$table_name;
            $topics = CMS_TABLE_PREFIX.'mod_topics';
            $SQL = "SELECT `import_id`, `import_status`,`identifier_type`,`identifier_id`,`identifier_language`,`timestamp`,".
                "`identifier_link`,`title` AS `identifier_title`, `posted_modified` AS `identifier_modified` ".
                "FROM `$import`, `$topics` WHERE `identifier_language`='$language' AND `import_status`='$status' AND ".
                "identifier_id=topic_id ORDER BY `link` ASC";
            $results = $this->app['db']->fetchAll($SQL);
            $list = array();
            foreach ($results as $result) {
                $item = array();
                foreach ($result as $key => $value) {
                    if ($key == 'identifier_link') {
                        $item[$key] = $value;
                        $item['identifier_url'] = CMS_URL.$value;
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
                return $this->selectNewsImportControlList($language, $status);
            case 'TOPICS':
                return $this->selectTopicsImportControlList($language, $status);
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
            // import ID does not exists!
            return false;
        }

        switch ($import['identifier_type']) {
            case 'WYSIWYG':
                // return WYSIWYG content
                return $this->WYSIWYG->selectPageID($import['identifier_id']);
            case 'NEWS':
                // return NEWS content
                return $this->NEWS->selectPostID($import['identifier_id']);
            case 'TOPICS':
                // return TOPICS content
                return $this->TOPICS->selectTopicID($import['identifier_id']);
            default:
                // unknown type ...
                throw new \UnexpectedValueException("The type ".$import['identifier_type']." is not supported for the import control list!");
        }
    }

    /**
     * Select all existing indentifier links, permalinks and language to enable
     * the creation of an .htaccess redirection file
     *
     * @throws \Exception
     * @return Ambigous <boolean, array>
     */
    public function selectRedirects()
    {
        try {
            $import = self::$table_name;
            $content = FRAMEWORK_TABLE_PREFIX.'flexcontent_content';
            $SQL = "SELECT `identifier_link`, `permalink`, `language` FROM `$import`, `$content` WHERE ".
                "`flexcontent_id`=`content_id` AND `import_status`='IMPORTED' AND ".
                "`identifier_link`!='' AND `flexcontent_id`>0 ORDER BY `identifier_link` ASC";
            $result = $this->app['db']->fetchAll($SQL);
            return (!empty($result)) ? $result : false;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

}
