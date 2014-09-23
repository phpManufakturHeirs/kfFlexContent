<?php

/**
 * flexContent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/flexContent
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\flexContent\Control;

use Silex\Application;
use phpManufaktur\Basic\Data\CMS\Settings;
use phpManufaktur\flexContent\Data\Setup\Setup;

class Configuration
{
    protected $app = null;
    protected static $config = null;
    protected static $config_path = null;

    /**
     * Constructor
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        self::$config_path = MANUFAKTUR_PATH.'/flexContent/config.flexcontent.json';
        $this->readConfiguration();
    }

    /**
     * Check the permalink directories and create them and the routes if needed
     *
     */
    public function checkPermalinkDirectory()
    {
        if (isset(self::$config['content']['language']['select']) &&
            self::$config['content']['language']['select']) {
            // create directories for all supported languages
            $languages = self::$config['content']['language']['support'];
        }
        else {
            // create a directory for the default language
            $languages = array();
            foreach (self::$config['content']['language']['support'] as $language) {
                if ($language['code'] == self::$config['content']['language']['default']) {
                    $languages[] = $language;
                    break;
                }
            }
        }

        $exists = true;
        foreach ($languages as $language) {
            $path = self::$config['content']['permalink']['directory'];
            $path = str_ireplace('{language}', strtolower($language['code']), $path);
            if (!$this->app['filesystem']->exists(CMS_PATH.$path)) {
                $exists = false;
                break;
            }
            $rss_path = self::$config['rss']['permalink']['directory'];
            $rss_path = str_ireplace('{language}', strtolower($language['code']), $rss_path);
            if (!$this->app['filesystem']->exists(CMS_PATH.$rss_path)) {
                $exists = false;
                break;
            }
        }

        if (!$exists) {
            // a permanent directory does not exists - create the permanent routes and directories
            $Setup = new Setup();
            $Setup->createPermalinkRoutes($this->app, self::$config);
            $Setup->createPermalinkDirectories($this->app, self::$config);
        }
    }

    /**
     * Return the default configuration array for flexContent
     *
     * @return array
     */
    public function getDefaultConfigArray()
    {
        $cmsSettings = new Settings($this->app);
        $default_language = $cmsSettings->getSetting('default_language');

        return array(
            'nav_tabs' => array(
                'order' => array(
                    'list',
                    'edit',
                    'tags',
                    'categories',
                    'rss',
                    'import',
                    'about'
                ),
                'default' => 'about'
            ),
            'list' => array(
                'category' => array(
                    // possible: null, ARTICLE (alias for DEFAULT), DEFAULT, EVENT, FAQ, GLOSSARY or category ID
                    'default' => 'ARTICLE'
                )
            ),
            'content' => array(
                'field' => array(
                    'title' => array(
                        'required' => true,
                        'length' => array(
                            'minimum' => 2,
                            'maximum' => 128
                        )
                    ),
                    'language' => array(
                        'required' => true
                    ),
                    'page_title' => array(
                        'required' => false,
                        'length' => array(
                            'minimum' => 2,
                            'maximum' => 128
                        )
                    ),
                    'description' => array(
                        'required' => false,
                        'length' => array(
                            'minimum' => 50,
                            'maximum' => 180
                        )
                    ),
                    'keywords' => array(
                        'required' => false,
                        'separator' => 'comma', // alternate: 'space'
                        'words' => array(
                            'minimum' => 3,
                            'maximum' => 20
                        )
                    ),
                    'permalink' => array(
                        'required' => true
                    ),
                    'redirect_url' => array(
                        'required' => false
                    ),
                    'redirect_target' => array(
                        'required' => false,
                        'default' => '_blank'
                    ),
                    'publish_from' => array(
                        'required' => true,
                        'add' => array(
                            'hours' => 0
                        )
                    ),
                    'breaking_to' => array(
                        'required' => false,
                        'add' => array(
                            'hours' => 168
                        )
                    ),
                    'archive_from' => array(
                        'required' => false,
                        'add' => array(
                            'days' => 365
                        )
                    ),
                    'teaser' => array(
                        'required' => false
                    ),
                    'content' => array(
                        'required' => false
                    ),
                    'status' => array(
                        'required' => true
                    ),
                    'rss' => array(
                        'required' => false
                    ),
                    'event_date_from' => array(
                        'required' => true
                    ),
                    'event_date_to' => array(
                        'required' => true
                    ),
                    'event_organizer' => array(
                        'required' => false,
                        'tags' => array()
                    ),
                    'event_location' => array(
                        'required' => false,
                        'tags' => array()
                    ),
                    'glossary_type' => array(
                        'required' => true
                    )
                ),
                'permalink' => array(
                    'directory' => '/{language}/content'
                ),
                'images' => array(
                    'directory' => array(
                        'start' => '/media/public',
                        'select' => '/media/public/content/teaser'
                     )
                ),
                'language' => array(
                    'select' => false,
                    'default' => $default_language,
                    'support' => array(
                        'DE' => array(
                            'code' => 'DE',
                            'name' => 'German'
                        ),
                        'EN' => array(
                            'code' => 'EN',
                            'name' => 'English'
                        )
                    )
                ),
                'tag' => array(
                    'auto-link' => array(
                        'enabled' => true,
                        'remove-sharp' => false,
                        'replacement' => array(
                            'link' => '<a class="tag auto-link" href="{link}" title="{description}">{tag}</a>',
                            'invalid' => '<span class="tag invalid" title="The hashtag {tag} does not exist!"><i class="fa fa-ban"></i> {tag}</span>',
                            'unassigned' => '<span class="tag unassigned" title="The hashtag {tag} exists but is not assigend to any content!"><i class="fa fa-chain-broken"></i> {tag}</span>'
                        ),
                        'ellipsis' => 64
                    )
                )
            ),
            'rss' => array(
                'enabled' => true,
                'permalink' => array(
                    'directory' => '/{language}/rss'
                ),
                'channel' => array(
                    'limit' => 50,
                    'image' => array(
                        'max_width' => 100,
                        'max_height' => 100
                    )
                ),
                'tracking' => array(
                    'enabled' => true
                )
            ),
            'admin' => array(
                'import' => array(
                    'enabled' => false,
                    'timelimit' => 60,
                    'data' => array(
                        'handling' => 'CLEAN_UP',
                        'htmlpurifier' => array(
                            'enabled' => true,
                            'config' => array(
                                'URI.MakeAbsolute' => true,
                                'AutoFormat.RemoveEmpty' => true,
                                'AutoFormat.RemoveEmpty.RemoveNbsp' => true,
                                'HTML.ForbiddenElements' => array(
                                    'b',
                                    'em',
                                    'i',
                                    'span',
                                    'strong'
                                )
                            )
                        ),
                        'remove' => array(
                            'double-space' => true,
                        ),
                        'replace' => array(
                            '„' => '"',
                            '“' => '"',
                            "‚" => "'",
                            "’" => "'"
                        ),
                        'images' => array(
                            'move' => true,
                            'teaser' => array(
                                'get_from_content' => true,
                                'min_width' => 150,
                                'min_height' => 150
                             ),
                            'sanitize' => true
                        ),
                        'teaser' => array(
                            'create' => true,
                            'ellipsis' => 500,
                            'html' => true
                        ),
                        'description' => array(
                            'create' => true,
                            'source' => 'teaser',
                            'ellipsis' => 150
                        ),
                        'htaccess' => array(
                            'create' => true,
                            'file' => 'flexcontent.htaccess'
                        )
                    )
                )
            ),
            'kitcommand' => array(
                'permalink' => array(
                    'category' => array(
                        'robots' => 'index,follow'
                    ),
                    'content' => array(
                        'robots' => 'index,follow',
                    ),
                    'faq' => array(
                        'robots' => 'index,follow'
                    ),
                    'tag' => array(
                        'robots' => 'noindex,follow'
                    )
                ),
                'parameter' => array(
                    'action' => array(
                        'view' => array(
                            'load_css' => true,
                            'load_jquery' => true,
                            'title_level' => 1,
                            'content_title' => true,
                            'content_description' => false,
                            'content_view' => 'content',
                            'content_categories' => true,
                            'content_tags' => true,
                            'content_permalink' => true,
                            'content_control' => true,
                            'content_author' => true,
                            'content_date' => true,
                            'content_rating' => array(
                                'enabled' => true,
                                'maximum_rate' => 5,
                                'size' => 'big',
                                'stars' => 5,
                                'step' => true,
                                'template' => 'default'
                            ),
                            'content_comments' => array(
                                'enabled' => true,
                                'captcha' => false,
                                'gravatar' => true,
                                'publish' => 'admin',
                                'rating' => true
                            )
                        ),
                        'category' => array(
                            'load_css' => true,
                            'load_jquery' => true,
                            'title_level' => 1,
                            'category_name' => true,
                            'category_description' => true,
                            'category_image' => true,
                            'category_image_max_width' => 150,
                            'category_image_max_height' => 150,
                            'content_limit' => 100,
                            'content_exposed' => 2,
                            'content_status' => array(
                                'BREAKING',
                                'PUBLISHED'
                            ),
                            'content_image' => true,
                            'content_image_max_width' => 350,
                            'content_image_max_height' => 350,
                            'content_image_small_max_width' => 100,
                            'content_image_small_max_height' => 100,
                            'content_title' => true,
                            'content_description' => false,
                            'content_view' => 'teaser',
                            'content_tags' => true,
                            'content_author' => true,
                            'content_date' => true,
                            'content_categories' => false
                        ),
                        'tag' => array(
                            'load_css' => true,
                            'load_jquery' => true,
                            'title_level' => 1,
                            'tag_name' => true,
                            'tag_description' => true,
                            'tag_image' => true,
                            'tag_image_max_width' => 150,
                            'tag_image_max_height' => 150,
                            'content_limit' => 100,
                            'content_exposed' => 2,
                            'content_status' => array(
                                'BREAKING',
                                'PUBLISHED'
                            ),
                            'content_image' => true,
                            'content_image_max_width' => 350,
                            'content_image_max_height' => 350,
                            'content_image_small_max_width' => 100,
                            'content_image_small_max_height' => 100,
                            'content_title' => true,
                            'content_description' => false,
                            'content_view' => 'teaser',
                            'content_tags' => true,
                            'content_author' => true,
                            'content_date' => true,
                            'content_categories' => true
                        ),
                        'list' => array(
                            'load_css' => true,
                            'load_jquery' => true,
                            'title_level' => 1,
                            'categories' => array(),
                            'categories_exclude' => array(),
                            'order_by' => 'publish_from',
                            'order_direction' => 'DESC',
                            'content_limit' => 100,
                            'paging' => 0,
                            'content_exposed' => 2,
                            'content_status' => array(
                                'BREAKING',
                                'PUBLISHED'
                            ),
                            'content_image' => true,
                            'content_image_max_width' => 350,
                            'content_image_max_height' => 350,
                            'content_image_small_max_width' => 100,
                            'content_image_small_max_height' => 100,
                            'content_title' => true,
                            'content_description' => false,
                            'content_view' => 'teaser',
                            'content_tags' => true,
                            'content_author' => true,
                            'content_date' => true,
                            'content_categories' => true
                        ),
                        'list_simple' => array(
                            'load_css' => true,
                            'load_jquery' => true,
                            'title_level' => 1,
                            'categories' => array(),
                            'categories_exclude' => array(),
                            'order_by' => 'publish_from',
                            'order_direction' => 'DESC',
                            'content_limit' => 10,
                            'paging' => 0,
                            'content_status' => array(
                                'BREAKING',
                                'PUBLISHED'
                            ),
                            'content_image' => true,
                            'content_image_max_width' => 350,
                            'content_image_max_height' => 350,
                            'content_image_small_max_width' => 100,
                            'content_image_small_max_height' => 100,
                            'content_title' => true,
                            'content_description' => false,
                            'content_view' => 'teaser',
                            'content_tags' => true,
                            'content_author' => true,
                            'content_date' => true,
                            'content_categories' => true
                        ),
                        'faq' => array(
                            'load_css' => true,
                            'load_jquery' => true,
                            'title_level' => 1,
                            'category_name' => true,
                            'category_description' => true,
                            'category_image' => true,
                            'category_image_max_width' => 150,
                            'category_image_max_height' => 150,
                            'faq_rating' => array(
                                'enabled' => true,
                                'maximum_rate' => 5,
                                'size' => 'big',
                                'stars' => 5,
                                'step' => true,
                                'template' => 'default'
                            ),
                            'faq_comments' => array(
                                'enabled' => true,
                                'captcha' => false,
                                'gravatar' => true,
                                'publish' => 'admin',
                                'rating' => true
                            ),
                            'faq_permalink' => true,
                            'faq_control' => true,
                            'order_by' => 'title',
                            'order_direction' => 'ASC',
                            'content_status' => array(
                                'BREAKING',
                                'PUBLISHED'
                            ),
                            'content_limit' => 100,
                            'content_date' => false,
                            'content_author' => false,
                            'content_view' => 'teaser',
                            'content_categories' => false,
                            'content_tags' => true,
                            'content_image' => true,
                            'content_image_max_width' => 150,
                            'content_image_max_height' => 150,
                            'content_rating' => array(
                                'enabled' => false,
                                'maximum_rate' => 5,
                                'size' => 'big',
                                'stars' => 5,
                                'step' => true,
                                'template' => 'default'
                            )
                        )
                    )
                ),
                'content' => array(
                    'kitcommand' => array(
                        'enabled' => true,
                        'libraries' => array(
                            'enabled' => false,
                            'jquery' => array(
                                'jquery/awesomecloud/latest/jquery.awesomeCloud.min.js'
                            ),
                            'css' => array(

                            )
                        )
                    )
                ),
                'libraries' => array(
                    'enabled' => true,
                    'jquery' => array(
                        'jquery/jquery/latest/jquery.min.js',
                        'bootstrap/latest/js/bootstrap.min.js'
                    ),
                    'css' => array(
                        'bootstrap/latest/css/bootstrap.min.css',
                        'font-awesome/latest/css/font-awesome.min.css'
                    ),
                    'extra' => array(
                        'cloud' => array(
                            'jquery' => array(
                                'jquery/awesomecloud/latest/jquery.awesomeCloud.min.js'
                            )
                        )
                    )
                )
            ),
            'glossary' => array(
                'filter' => array(
                    'enabled' => true,
                    'replacement' => array(
                        'not_exists' => array(
                            'title' => "There exists no Glossary entry for '{search}'!",
                            'html' => '<span class="glossary not-exists" title="{title}">{text}</span>'
                        ),
                        'inactive' => array(
                            'html' => '<span class="glossary inactive">{text}</span>'
                        ),
                        'link' => array(
                            'html' => '<a class="glossary link" href="{url}" target="{target}">{replacement}</a>'
                        ),
                        'abbreviation' => array(
                            'html' => '<abbr class="glossary abbreviation" title="{explain}">{text}</abbr>'
                        ),
                        'acronym' => array(
                            'html' => '<abbr class="glossary acronym" title="{explain}">{text}</abbr>'
                        ),
                        'keyword' => array(
                            'html' => '<span class="glossary keyword" title="{explain}">{text}</span>'
                        )
                    )
                )
            ),
            'search' => array(
                'cms' => array(
                    'enabled' => true,
                    'image' => array(
                        'enabled' => true,
                        'max_width' => 100,
                        'max_height' => 100
                    )
                ),
                'result' => array(
                    'highlight' => true,
                    'replacement' => '<span class="highlight">{word}</span>'
                ),
                'content' => array(
                    'status' => array('PUBLISHED', 'BREAKING', 'HIDDEN', 'ARCHIVED')
                ),
                'category' => array(
                    'title' => array(
                        'prefix' => array(
                            'enabled' => true,
                            'prefix' => 'Category',
                            'replacement' => '{prefix}: {title}'
                        )
                    )
                ),
                'tag' => array(
                    'title' => array(
                        'prefix' => array(
                            'enabled' => true,
                            'prefix' => 'Tag (#tag)',
                            'replacement' => '{prefix}: {title}'
                        )
                    )
                )
            )
        );
    }

    /**
     * Read the configuration file
     */
    protected function readConfiguration()
    {
        if (!file_exists(self::$config_path)) {
            self::$config = $this->getDefaultConfigArray();
            $this->saveConfiguration();
        }
        self::$config = $this->app['utils']->readConfiguration(self::$config_path);
        // check the permanent link directories ...
        $this->checkPermalinkDirectory();
    }

    /**
     * Save the configuration file
     */
    public function saveConfiguration()
    {
        // write the formatted config file to the path
        file_put_contents(self::$config_path, $this->app['utils']->JSONFormat(self::$config));
        $this->app['monolog']->addDebug('Save configuration to '.basename(self::$config_path));
    }

    /**
     * Get the configuration array
     *
     * @return array
     */
    public function getConfiguration()
    {
        return self::$config;
    }

    /**
     * Set the configuration array
     *
     * @param array $config
     */
    public function setConfiguration($config)
    {
        self::$config = $config;
    }

}
