<?php

/**
 * flexContent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/flexContent
 * @copyright 2014 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\flexContent\Control\Filter;

use Silex\Application;
use phpManufaktur\flexContent\Control\Configuration;

class Glossary
{
    protected $app = null;
    protected static $cms = null;
    protected static $content = null;
    protected static $filter_expression = null;
    protected static $config = null;

    /**
     * Initialize the Glossary filter
     *
     * @param Application $app
     * @throws \Exception
     */
    protected function initialize(Application $app)
    {
        $this->app = $app;

        if (null === (self::$cms = $app['request']->request->get('cms', null))) {
            throw new \Exception('Missing the CMS information bag!');
        }

        if (null === (self::$content = $app['request']->request->get('content', null))) {
            throw new \Exception('Missing the content for the filter execution.');
        }

        if (null === (self::$filter_expression = $app['request']->request->get('filter_expression', null))) {
            throw new \Exception('Missing the filter expression.');
        }

        if (isset(self::$cms['locale'])) {
            // set the locale from the CMS locale
            $this->app['translator']->setLocale(self::$cms['locale']);
        }

        // remove the filter expression (clean up)
        self::$content = str_replace(self::$filter_expression, '', self::$content);

        $Configuration = new Configuration($app);
        self::$config = $Configuration->getConfiguration();
    }

    /**
     * Process the Glossary filter
     *
     * @return mixed
     */
    protected function processGlossary()
    {
        preg_match_all('/(@@)(|&nbsp;)(.){2,64}(|&nbsp;)(@@)/', self::$content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $filter_expression = $match[0];

            // get the expression without leading and trailing @@
            $filter_string = trim(str_ireplace(array('@@', '&nbsp;'), array('', ' '), $filter_expression));
            if (empty($filter_string)) {
                // nothing to do ...
                self::$content = str_replace($filter_expression, $filter_string, self::$content);
                continue;
            }

            if (substr_count($filter_string, '|') === 1) {
                list($text, $search) = explode('|', $filter_string);
                $text = trim($text);
                $search = strtolower($search);
                $search = trim($search);
            }
            else {
                $text = filter_string;
                $search = strtolower($filter_string);
            }

//echo $filter_string;
            self::$content = str_replace($filter_expression, $filter_string, self::$content);
        }

        return self::$content;
    }

    /**
     * Controller for the Glossary filter function
     *
     * @param Application $app
     */
    public function Controller(Application $app)
    {
        $this->initialize($app);

        if (!self::$config['glossary']['filter']['enabled']) {
            // the filter is disabled, just return content
            return self::$content;
        }

        // process the filter
        return $this->processGlossary();
    }
}
