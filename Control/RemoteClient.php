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

use phpManufaktur\Basic\Control\Pattern\Alert;

class RemoteClient extends Alert
{
    protected static $parameter = null;
    protected static $config = null;
    protected static $locale = null;
    protected static $client_name = null;
    protected static $server_name = null;
    protected static $token = null;
    protected static $server_url = null;
    protected static $server_request_url = null;
    protected static $action = null;

    /**
     * Execute the Remote Server Query
     *
     * @param unknown $url
     * @param unknown $query
     * @return boolean|mixed
     */
    protected function cURLexec($query)
    {
        // init cURL
        $ch = curl_init();

        if (!isset($query['name'])) {
            $query['name'] = self::$server_name;
        }
        if (!isset($query['token'])) {
            $query['token'] = self::$token;
        }
        if (!isset($query['locale'])) {
            $query['locale'] = self::$locale;
        }

        // set the general cURL options
        $options = array(
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => 'kitFramework::flexContent',
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($query, '', '&'),
            CURLOPT_URL => self::$server_request_url
        );

        // set the cURL options
        curl_setopt_array($ch, $options);

        // set proxy if needed
        $this->app['utils']->setCURLproxy($ch);

        if (false === ($result = curl_exec($ch))) {
            // cURL error
            $this->setAlert('cURL error: %error%', array('%error%' => curl_error($ch)), self::ALERT_TYPE_DANGER);
            return false;
        }

        curl_close($ch);

        return ($this->app['utils']->isJSON($result)) ? json_decode($result, true) : $result;
    }

    /**
     * Initialize the Client and set the properties for the Server Access
     *
     * @param array $parameter
     * @param array $config
     * @param string $locale
     * @return boolean
     */
    protected function initializeClient($parameter, $config, $locale)
    {
        self::$parameter = $parameter;
        self::$config = $config;
        self::$locale = strtolower($locale);

        if (!isset(self::$parameter['action']) || empty(self::$parameter['action'])) {
            $this->setAlert('Missing the parameter: %parameter%', array('%parameter%' => 'action'),
                self::ALERT_TYPE_DANGER);
            return false;
        }
        self::$action = $parameter['action'];

        if (!isset(self::$parameter['remote']) ||
            empty(self::$parameter['remote']) ||
            !isset(self::$config['remote']) ||
            !is_array(self::$config['remote'])) {
            $this->setAlert('To make use of the <strong>remote</strong> feature you must define a <strong>remote</strong> section in the config.flexcontent.json!',
                array(), self::ALERT_TYPE_DANGER);
            return false;
        }

        if (!isset(self::$config['remote']['client'][self::$parameter['remote']]) ||
            empty(self::$config['remote']['client'][self::$parameter['remote']])) {
            $this->setAlert('You must define the <strong>client name</strong> for the remote connection in the config.flexcontent.json!',
                array(), self::ALERT_TYPE_DANGER);
            return false;
        }

        self::$client_name = self::$parameter['remote'];

        if (!isset(self::$config['remote']['client'][self::$client_name]['server_name']) ||
            empty(self::$config['remote']['client'][self::$client_name]['server_name'])) {
            $this->setAlert('You must specify the <strong>server name</strong> for the remote connection in the config.flexcontent.json!',
                array(), self::ALERT_TYPE_DANGER);
            return false;
        }

        self::$server_name = self::$config['remote']['client'][self::$client_name]['server_name'];

        if (!isset(self::$config['remote']['client'][self::$client_name]['token']) ||
            empty(self::$config['remote']['client'][self::$client_name]['token'])) {
            $this->setAlert('You must specify the <strong>token</strong> for the remote connection in the config.flexcontent.json!',
                array(), self::ALERT_TYPE_DANGER);
            return false;
        }

        self::$token = self::$config['remote']['client'][self::$client_name]['token'];

        if (!isset(self::$config['remote']['client'][self::$client_name]['url']) ||
            empty(self::$config['remote']['client'][self::$client_name]['url'])) {
            $this->setAlert('You must specify the <strong>server url</strong> for the remote connection in the config.flexcontent.json!');
            return false;
        }

        $url = rtrim(self::$config['remote']['client'][self::$client_name]['url'], "/");
        if (false === (filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED))) {
            $this->setAlert('Please check the <strong>server url</strong> for the remote connection, actual %url% is not valid!',
                array('%url%' => $url), self::ALERT_TYPE_DANGER);
            return false;
        }

        self::$server_url = $url.'/kit2';
        self::$server_request_url = $url . '/kit2/flexcontent/json';

        return true;
    }

    /**
     * Get the content from a remote connection
     *
     * @param array $parameter
     * @param array $config
     * @param string $locale
     * @return boolean|array
     */
    public function getContent($parameter, $config, $locale)
    {
        // initialize the client
        if (!$this->initializeClient($parameter, $config, $locale)) {
            return false;
        }

        $query = array();
        switch (self::$action) {
            case 'list':
                $query = array(
                    'action' => self::$action,
                    'content_status' => isset($parameter['content_status']) ? $parameter['content_status'] : array('BREAKING', 'PUBLISHED'),
                    'content_limit' => isset($parameter['content_limit']) ? $parameter['content_limit'] : 100,
                    'order_by' => isset($parameter['order_by']) ? $parameter['order_by'] : 'publish_from',
                    'order_direction' => isset($parameter['order_direction']) ? $parameter['order_direction'] : 'DESC',
                    'category_type' => isset($parameter['category_type']) ? $parameter['category_type'] : 'DEFAULT'
                );
                break;
            case 'category':
                $query = array(
                    'action' => self::$action,
                    'category_id' => isset($parameter['category_id']) ? $parameter['category_id'] : -1,
                    'content_status' => isset($parameter['content_status']) ? $parameter['content_status'] : array('BREAKING', 'PUBLISHED'),
                    'content_limit' => isset($parameter['content_limit']) ? $parameter['content_limit'] : 100
                );
                break;
            case 'faq':
                $query = array(
                    'action' => 'faq',
                    'category_id' => isset($parameter['category_id']) ? $parameter['category_id'] : -1,
                    'faq_ids' => isset($parameter['faq_ids']) ? $parameter['faq_ids'] : array(),
                    'content_status' => isset($parameter['content_status']) ? $parameter['content_status'] : array('BREAKING', 'PUBLISHED'),
                    'content_limit' => isset($parameter['content_limit']) ? $parameter['content_limit'] : 100
                );
                break;
            case 'view':
                $query = array(
                    'action' => 'view',
                    'content_id' => isset($parameter['content_id']) ? $parameter['content_id'] : -1,
                    'permalink' => isset($parameter['permalink']) ? $parameter['permalink'] : '',
                );
                break;
            default:
                $this->setAlert('The parameter action[%action%] is not supported for remote connections!',
                    array('%action%' => self::$action), self::ALERT_TYPE_DANGER);
                return false;
        }

        if (false === ($result = $this->cURLexec($query))) {
            return false;
        }

        if (isset($result['response']) && is_array($result['response'])) {
            return $result['response'];
        }

        $this->setAlert("Oooops, got a server response, but don't know how to handle it: %response%",
            array('%response%' => is_array($result) ? urldecode(http_build_query($result, '', ', ')) : $result), self::ALERT_TYPE_DANGER);
        return false;
    }

    /**
     * Get an info about the categories which will be served for the given client
     *
     * @param array $parameter
     * @param array $config
     * @param string $locale
     * @param array $basic
     * @return boolean
     */
    public function getInfo($parameter, $config, $locale, $basic)
    {
        if (!$this->initializeClient($parameter, $config, $locale)) {
            return false;
        }

        if (false === ($result = $this->cURLexec(array('action' => 'info')))) {
            return false;
        }

        if (!isset($result['status'])) {
            $this->setAlert($result, array(), self::ALERT_TYPE_DANGER);
            return false;
        }
        if ($result['status'] != 200) {
            if (isset($result['message'])) {
                $this->setAlert(sprintf('[%s] %s', $result['status'], $result['message']),
                    array(), self::ALERT_TYPE_DANGER);
            }
            else {
                $this->setAlert('Server Request failed, returned status code: %status%',
                    array('%status%' => $result['status']), self::ALERT_TYPE_DANGER);
            }
            return false;
        }

        if (isset($result['response'])) {
            return $this->app['twig']->render($this->app['utils']->getTemplateFile(
                '@phpManufaktur/flexContent/Template', 'command/server.info.categories.twig',
                FRAMEWORK_TEMPLATE_PREFERRED),
                array(
                    'server_name' => self::$server_name,
                    'server_url' => self::$server_url,
                    'basic' => $basic,
                    'parameter' => self::$parameter,
                    'response' => $result['response']
                ));
        }

        $this->setAlert("Oooops, got a server response, but don't know how to handle it: %response%",
            array('%response%' => is_array($result) ? urldecode(http_build_query($result, '', ', ')) : $result), self::ALERT_TYPE_DANGER);
        return false;
    }
}
