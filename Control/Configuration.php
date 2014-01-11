<?php

/**
 * flexContent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/event
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
        if (self::$config['content']['language']['select']) {
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
            if (!$this->app['filesystem']->exists($path)) {
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
            'content' => array(
                'field' => array(
                    'title' => array(
                        'required' => true,
                        'length' => array(
                            'minimum' => 10,
                            'maximum' => 128
                        )
                    ),
                    'language' => array(
                        'required' => true
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
                )
            ),
            'admin' => array(
                'import' => array(
                    'enabled' => true,
                    'data' => array(
                        'handling' => 'CLEAN_UP',
                        'htmlpurifier' => array(
                            'enabled' => true
                        ),
                        'remove' => array(
                            'nbsp' => true,
                            'double-space' => true,
                            'style' => true,
                            'class' => true
                        ),
                        'images' => array(
                            'move' => true,
                            'teaser' => array(
                                'get_from_content' => true,
                                'min_width' => 150,
                                'min_height' => 150
                             )
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
                'template' => array(
                    'default' => array(
                        'iframe' => false
                    ),
                    'iframe' => array(
                        'iframe' => true
                    )
                ),
                'parameter' => array(
                    'action' => array(
                        'content' => array(
                            'css' => true,
                            'title' => true,
                            'title_level' => 1,
                            'description' => false,
                            'categories' => true,
                            'tags' => true,
                            'permalink' => true,
                            'control' => true,
                            'author' => true,
                            'date' => true
                        ),
                        'category' => array(
                            'css' => true,
                            'title_level' => 1,
                            'category_name' => true,
                            'category_description' => true,
                            'category_image' => true,
                            'category_image_max_width' => 150,
                            'category_image_max_height' => 150,
                            'content_limit' => 100,
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
                            'content_teaser' => true,
                            'content_content' => false,
                            'content_tags' => true,
                            'content_author' => true,
                            'content_date' => true,
                            'content_categories' => false
                        ),
                        'tag' => array(
                            'css' => true,
                            'title_level' => 1,
                            'tag_name' => true,
                            'tag_description' => true,
                            'tag_image' => true,
                            'tag_image_max_width' => 150,
                            'tag_image_max_height' => 150,
                            'content_limit' => 100,
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
                            'content_teaser' => true,
                            'content_content' => false,
                            'content_tags' => true,
                            'content_author' => true,
                            'content_date' => true,
                            'content_categories' => true
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
