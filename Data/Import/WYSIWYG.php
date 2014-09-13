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
use phpManufaktur\flexContent\Data\Content\Content;
use phpManufaktur\flexContent\Control\Configuration;
use phpManufaktur\Basic\Data\CMS\Users;
use phpManufaktur\Basic\Data\CMS\Page;

/**
 * Class to import WYSIWYG contents from the CMS WebsiteBaker,
 * LEPTON CMS or BlackCat CMS
 *
 * @author Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 *
 */
class WYSIWYG
{
    protected $app = null;
    protected $PagesData = null;

    /**
     * Constructor
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->PagesData = new Page($app);
    }

    /**
     * Check if the WYSIWYG addon is installed
     *
     * @return boolean
     */
    public function isInstalled()
    {
        return $this->app['db.utils']->tableExists(CMS_TABLE_PREFIX.'mod_wysiwyg');
    }

    /**
     * Select WYSIWYG pages for the given language
     *
     * @param string $language
     * @throws \Exception
     * @return array with the page records
     */
    public function selectWYSIWYGpages($language)
    {
        try {
            $pages = CMS_TABLE_PREFIX.'pages';
            $sections = CMS_TABLE_PREFIX.'sections';

            $SQL = "SELECT DISTINCT $pages.page_id, $pages.link, $pages.page_title, $pages.language, $pages.modified_when ".
                "FROM `$pages`, `$sections` WHERE $pages.page_id=$sections.page_id AND ".
                "$sections.module='wysiwyg' AND $pages.language='$language' ORDER BY $pages.link ASC";
            $pages = $this->app['db']->fetchAll($SQL);
            return $pages;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select the information for the given page ID
     *
     * @param integer $page_id
     * @throws \Exception
     * @return boolean|array
     */
    public function selectPageID($page_id)
    {
        try {
            $pages = CMS_TABLE_PREFIX.'pages';
            $sections = CMS_TABLE_PREFIX.'sections';
            $wysiwyg = CMS_TABLE_PREFIX.'mod_wysiwyg';

            $SQL = "SELECT * FROM `$pages`,`$sections`,`$wysiwyg` WHERE $pages.page_id=$sections.page_id AND ".
                "$wysiwyg.page_id=$pages.page_id AND $sections.module='wysiwyg' AND $pages.page_id = '$page_id' ".
                "ORDER BY $sections.position ASC";
            $results = $this->app['db']->fetchAll($SQL);

            if (!isset($results[0]['page_id'])) {
                return false;
            }

            $html = '';
            foreach ($results as $result) {
                // collect the HTML from the sections
                $html .= $this->app['utils']->unsanitizeText($result['content']);
            }

            $Configuration = new Configuration($this->app);
            $config = $Configuration->getConfiguration();

            $ContentData = new Content($this->app);

            // get the last part of the link (page name) as permalink
            $permalink = substr($results[0]['link'], strrpos($results[0]['link'], '/')+1);
            if ($ContentData->existsPermaLink($permalink, $results[0]['language'])) {
                // this permalink is already in use!
                $count = $ContentData->countPermaLinksLikeThis($permalink, $results[0]['language']);
                $count++;
                // add a counter to the new permanet link
                $permalink = sprintf('%s-%d', $permalink, $count);
            }

            $cmsUsers = new Users($this->app);
            if (false === ($user = $cmsUsers->select($results[0]['modified_by']))) {
                $this->app['monolog']->addDebug('Failed to get the user data for CMS user ID '.$results[0]['modified_by'],
                    array(__METHOD__, __LINE__));
                $username = $this->app['account']->getUserName();
            }
            else {
                if (!$this->app['account']->checkUserHasFrameworkAccount($user['username'])) {
                    $this->app['monolog']->addDebug('The user '.$user['username'].' has no kitFramework account.',
                        array(__METHOD__, __LINE__));
                    $username = $this->app['account']->getUserName();
                }
                else {
                    $username = $user['username'];
                }
            }

            $content = array(
                'language' => strtoupper($results[0]['language']),
                'title' => $this->app['utils']->unsanitizeText($results[0]['page_title']),
                'description' => $this->app['utils']->unsanitizeText($results[0]['description']),
                'keywords' => $this->app['utils']->unsanitizeText($results[0]['keywords']),
                'permalink' => $permalink,
                'publish_from' => date('Y-m-d H:i:s', $results[0]['modified_when']),
                'breaking_to' => date('Y-m-d H:i:s', $results[0]['modified_when']+((60*60)*$config['content']['field']['breaking_to']['add']['hours'])),
                'archive_from' => date('Y-m-d H:i:s', $results[0]['modified_when']+((60*60*24)*$config['content']['field']['archive_from']['add']['days'])),
                'status' => 'UNPUBLISHED',
                'teaser' => '',
                'teaser_image' => '',
                'content' => $html,
                'author_username' => $username,
                'update_username' => date('Y-m-d H:i:s')
            );
            return $content;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Get the relative page link for the given page ID
     *
     * @param integer $page_id
     * @return boolean|string
     */
    public function getRelativePageLink($page_id)
    {
        if (false === ($link = $this->PagesData->getPageLinkByPageID($page_id))) {
            return false;
        }
        $directory = $this->PagesData->getPageDirectory();
        $extension = $this->PagesData->getPageExtension();
        return $directory.$link.$extension;
    }

    /**
     * Check if the page with the given PAGE_ID contain a flexContent kitCommand
     *
     * @param integer $page_id
     * @throws \Exception
     * @return boolean
     */
    public function checkPageIDforFlexContentCommand($page_id)
    {
        try {
            $wysiwyg = CMS_TABLE_PREFIX.'mod_wysiwyg';
            $SQL = "SELECT `page_id` FROM $wysiwyg WHERE `page_id`='$page_id' AND `content` LIKE '%~~ flexcontent %'";
            $check_id = $this->app['db']->fetchColumn($SQL);
            return ($check_id == $page_id);
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }
}
