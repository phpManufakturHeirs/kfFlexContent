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
 * Class to import TOPICS contents from the CMS WebsiteBaker,
 * LEPTON CMS or BlackCat CMS
 *
 * @author Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 *
 */
class Topics
{
    protected $app = null;
    protected $PagesData = null;
    protected $flexContentData = null;
    protected $cmsUsers = null;

    protected static $config = null;

    /**
     * Constructor
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->PagesData = new Page($app);
        $this->flexContentData = new Content($app);
        $this->cmsUsers = new Users($app);

        $Configuration = new Configuration($app);
        self::$config = $Configuration->getConfiguration();
    }

    /**
     * Check if TOPICS is installed
     *
     * @return boolean
     */
    public function isInstalled()
    {
        return $this->app['db.utils']->tableExists(CMS_TABLE_PREFIX.'mod_topics');
    }

    /**
     * Select Topics posts for the given language
     *
     * @param string $language
     * @throws \Exception
     * @return array
     */
    public function selectTopicsPosts($language)
    {
        try {
            $topics = CMS_TABLE_PREFIX.'mod_topics';
            $pages = CMS_TABLE_PREFIX.'pages';
            $SQL = "SELECT `topic_id`, $topics.`link`, `posted_modified`, $topics.title, `language` ".
                "FROM `$topics`, `$pages` WHERE $pages.page_id=$topics.page_id AND ".
                "$pages.language='$language' ORDER BY $topics.link ASC";
            $posts = $this->app['db']->fetchAll($SQL);
            return $posts;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Get the relative page link for the given TOPIC ID
     *
     * @param integer $topic_id
     * @throws \Exception
     * @return boolean|string
     */
    public function getRelativeTopicLink($topic_id)
    {
        try {
            $SQL = "SELECT `link` FROM `".CMS_TABLE_PREFIX."mod_topics` WHERE `topic_id`=$topic_id";
            $link = $this->app['db']->fetchColumn($SQL);

            if (empty($link)) {
                // ID does not exists
                return false;
            }

            if (false === ($topics_directory = $this->PagesData->getTopicsDirectory())) {
                return false;
            }
            $extension = $this->PagesData->getPageExtension();
            return $topics_directory.$link.$extension;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select the content for the TOPICS ID for import
     *
     * @param integer $topic_id
     * @throws \Exception
     * @return boolean|array
     */
    public function selectTopicID($topic_id)
    {
        try {
            $SQL = "SELECT * FROM `".CMS_TABLE_PREFIX."mod_topics` WHERE `topic_id`=$topic_id";
            $topic = $this->app['db']->fetchAssoc($SQL);

            if (!isset($topic['topic_id'])) {
                // the record does not exists
                return false;
            }

            $language = $this->PagesData->getPageLanguage($topic['page_id']);
            $language = strtoupper($language);

            // get the last part of the link (page name) as permalink
            $permalink = substr($topic['link'], strrpos($topic['link'], '/'));
            if ($this->flexContentData->existsPermaLink($permalink, $language)) {
                // this permalink is already in use!
                $count = $this->flexContentData->countPermaLinksLikeThis($permalink, $language);
                $count++;
                // add a counter to the new permanet link
                $permalink = sprintf('%s-%d', $permalink, $count);
            }

            $cmsUsers = new Users($this->app);
            if (false === ($user = $cmsUsers->select($topic['posted_by']))) {
                $this->app['monolog']->addDebug('Failed to get the user data for CMS user ID '.$topic['posted_by'],
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

            $picture = $topic['picture'];
            if (!empty($picture)) {
                $SQL = "SELECT `picture_dir` FROM `".CMS_TABLE_PREFIX."mod_topics_settings` WHERE `page_id`=".$topic['page_id'];
                $picture_dir = $this->app['db']->fetchColumn($SQL);
                if (!empty($picture_dir)) {
                    $picture = CMS_URL.$picture_dir.'/'.$picture;
                }
            }

            $content = array(
                'language' =>  $language,
                'title' => $this->app['utils']->unsanitizeText($topic['title']),
                'description' => $this->app['utils']->unsanitizeText($topic['description']),
                'keywords' => $this->app['utils']->unsanitizeText($topic['keywords']),
                'permalink' => $permalink,
                'publish_from' => date('Y-m-d H:i:s', $topic['published_when']),
                'breaking_to' => date('Y-m-d H:i:s', $topic['published_when']+((60*60)*self::$config['content']['field']['breaking_to']['add']['hours'])),
                'archive_from' => ($topic['published_until'] > 0) ? date('Y-m-d H:i:s', $topic['published_until']) :
                    date('Y-m-d H:i:s', $topic['published_when']+((60*60*24)*self::$config['content']['field']['archive_from']['add']['days'])),
                'status' => 'UNPUBLISHED',
                'teaser' => $this->app['utils']->unsanitizeText($topic['content_short']),
                'teaser_image' => $picture,
                'content' => $this->app['utils']->unsanitizeText($topic['content_long']),
                'author_username' => $username,
                'update_username' => date('Y-m-d H:i:s')
            );
            return $content;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Check if the short or long content of a TOPICS article contains a
     * flexContent kitCommand
     *
     * @param integer $topic_id
     * @throws \Exception
     * @return boolean
     */
    public function checkTopicIDforFlexContentCommand($topic_id)
    {
        try {
            $SQL = "SELECT `topic_id` FROM `".CMS_TABLE_PREFIX."mod_topics` WHERE `topic_id`='$topic_id' ".
                "AND (`content_long` LIKE '%~~ flexcontent %' OR `content_short` LIKE '%~~ flexcontent %')";
            $check_id = $this->app['db']->fetchColumn($SQL);
            return ($check_id === $topic_id);
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }
}
