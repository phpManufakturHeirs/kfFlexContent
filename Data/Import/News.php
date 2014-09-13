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
 * Class to import NEWS contents from the CMS WebsiteBaker,
 * LEPTON CMS or BlackCat CMS
 *
 * @author Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 *
 */
class News
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
     * Check if the NEWS addon is installed
     *
     * @return boolean
     */
    public function isInstalled()
    {
        return $this->app['db.utils']->tableExists(CMS_TABLE_PREFIX.'mod_news_posts');
    }

    /**
     * Select NEWS posts for the given language
     *
     * @param string $language
     * @throws \Exception
     * @return array
     */
    public function selectNewsPosts($language)
    {
        try {
            $news = CMS_TABLE_PREFIX.'mod_news_posts';
            $pages = CMS_TABLE_PREFIX.'pages';
            $SQL = "SELECT `post_id`, $news.`link`, `posted_when`, $news.title, `language` FROM `$news`, `$pages` WHERE $pages.page_id=$news.page_id AND $pages.language='$language' ".
                "ORDER BY $news.link ASC";
            $posts = $this->app['db']->fetchAll($SQL);
            return $posts;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Get the relative page link for the given post ID
     *
     * @param integer $post_id
     * @throws \Exception
     * @return boolean|string
     */
    public function getRelativePostLink($post_id)
    {
        try {
            $SQL = "SELECT `link` FROM `".CMS_TABLE_PREFIX."mod_news_posts` WHERE `post_id`=$post_id";
            $link = $this->app['db']->fetchColumn($SQL);
            if (empty($link)) {
                return false;
            }
            $directory = $this->PagesData->getPageDirectory();
            $extension = $this->PagesData->getPageExtension();
            return $directory.$link.$extension;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select the information for the given NEWS ID for import to flexContent
     *
     * @param integer $post_id
     * @throws \Exception
     * @return boolean|array
     */
    public function selectPostID($post_id)
    {
        try {
            $SQL = "SELECT * FROM `".CMS_TABLE_PREFIX."mod_news_posts` WHERE `post_id`=$post_id";
            $post = $this->app['db']->fetchAssoc($SQL);

            if (!isset($post['post_id'])) {
                // the record does not exists
                return false;
            }

            $language = $this->PagesData->getPageLanguage($post['page_id']);
            $language = strtoupper($language);

            // get the last part of the link (page name) as permalink
            $permalink = substr($post['link'], strrpos($post['link'], '/')+1);
            if ($this->flexContentData->existsPermaLink($permalink, $language)) {
                // this permalink is already in use!
                $count = $this->flexContentData->countPermaLinksLikeThis($permalink, $language);
                $count++;
                // add a counter to the new permanet link
                $permalink = sprintf('%s-%d', $permalink, $count);
            }

            $cmsUsers = new Users($this->app);
            if (false === ($user = $cmsUsers->select($post['posted_by']))) {
                $this->app['monolog']->addDebug('Failed to get the user data for CMS user ID '.$post['posted_by'],
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
                'language' =>  $language,
                'title' => $this->app['utils']->unsanitizeText($post['title']),
                'description' => '',
                'keywords' => '',
                'permalink' => $permalink,
                'publish_from' => date('Y-m-d H:i:s', $post['published_when']),
                'breaking_to' => date('Y-m-d H:i:s', $post['published_when']+((60*60)*self::$config['content']['field']['breaking_to']['add']['hours'])),
                'archive_from' => ($post['published_until'] > 0) ? date('Y-m-d H:i:s', $post['published_until']) :
                    date('Y-m-d H:i:s', $post['published_when']+((60*60*24)*self::$config['content']['field']['archive_from']['add']['days'])),
                'status' => 'UNPUBLISHED',
                'teaser' => $this->app['utils']->unsanitizeText($post['content_short']),
                'teaser_image' => '',
                'content' => $this->app['utils']->unsanitizeText($post['content_long']),
                'author_username' => $username,
                'update_username' => date('Y-m-d H:i:s')
            );
            return $content;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Check if the short or long content of a News article contains a
     * flexContent kitCommand
     *
     * @param integer $post_id
     * @throws \Exception
     * @return boolean
     */
    public function checkNewsIDforFlexContentCommand($post_id)
    {
        try {
            $SQL = "SELECT `post_id` FROM `".CMS_TABLE_PREFIX."mod_news_posts` WHERE `post_id`=$post_id ".
                "AND (`content_long` LIKE '%~~ flexcontent %' OR `content_short` LIKE '%~~ flexcontent %')";
            $check_id = $this->app['db']->fetchColumn($SQL);
            return ($check_id === $post_id);
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }
}
