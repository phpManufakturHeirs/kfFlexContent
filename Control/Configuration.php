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
     * Return the default configuration array for flexContent
     *
     * @return array
     */
    public static function getDefaultConfigArray()
    {
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
                    'directory' => '/content'
                ),
                'images' => array(
                    'directory' => array(
                        'start' => '/media/public',
                        'select' => '/media/public/content/teaser'
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
                )
            ),
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
