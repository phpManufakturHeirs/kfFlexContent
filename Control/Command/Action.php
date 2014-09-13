<?php

/**
 * flexContent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/flexContent
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\flexContent\Control\Command;

use phpManufaktur\Basic\Control\kitCommand\Basic;
use Silex\Application;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use phpManufaktur\flexContent\Control\Configuration;
use phpManufaktur\flexContent\Data\Content\Content;
use phpManufaktur\flexContent\Control\RemoteClient;

class Action extends Basic
{
    protected static $config = null;

    protected function initParameters(Application $app, $parameter_id=-1)
    {
        parent::initParameters($app, $parameter_id);

        $ConfigurationData = new Configuration($app);
        self::$config = $ConfigurationData->getConfiguration();
    }

    /**
     * The default ACTION controller for flexContent - check the action and
     * return the result of the assigned flexContent Class
     *
     * @param Application $app
     * @return string
     */
    public function controllerAction(Application $app)
    {
        $this->initParameters($app);
        // get the kitCommand parameters
        $parameter = $this->getCommandParameters();

        // access the default parameters for action -> view from the configuration
        $default_parameter = self::$config['kitcommand']['parameter']['action']['view'];

        // check wether to use the flexcontent.css or not
        $parameter['load_css'] = (isset($parameter['load_css']) && (($parameter['load_css'] == 0) || (strtolower($parameter['load_css']) == 'false'))) ? false : $default_parameter['load_css'];

        if (!isset($parameter['action'])) {
            // there is no 'action' parameter set, so we show the "Welcome" page
            $subRequest = Request::create('/basic/help/flexcontent/welcome', 'GET');
            return $app->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
        }

        // check the CMS GET parameters
        $GET = $this->getCMSgetParameters();

        if (isset($GET['command']) && (strtolower($GET['command']) == 'flexcontent') &&
            isset($GET['action']) && (($GET['action'] == $parameter['action']) ||
            (($GET['action'] == 'view') && ($parameter['action'] == 'category')) ||
            (($GET['action'] == 'tag') && ($parameter['action'] == 'category')) ||
            (($GET['action'] == 'faq') && ($parameter['action'] == 'category')))) {
            // the command and parameters are set as GET from the CMS
            foreach ($GET as $key => $value) {
                if (strtolower($key) == 'command') continue;
                $parameter[strtolower($key)] = $value;
            }
            $this->setCommandParameters($parameter);
            // create also a new parameter ID!
            $this->createParameterID($parameter);
        }

        $parameter['type'] = isset($parameter['type']) ? strtolower($parameter['type']) : 'default';

        // before executing any action check the records and update the status fields
        $ContentData = new Content($app);
        $ContentData->autoUpdateStatus();

        switch (strtolower($parameter['action'])) {
            case 'view':
                $View = new ActionView();
                return $View->controllerView($app);
            case 'category':
                $Category = new ActionCategory();
                return $Category->ControllerCategory($app);
            case 'tag':
                $Tag = new ActionTag();
                return $Tag->ControllerTag($app);
            case 'archive':
            case 'list':
                $List = new ActionList();
                return $List->ControllerList($app);
            case 'faq':
                $FAQ = new ActionFAQ();
                return $FAQ->ControllerFAQ($app);
            case 'info':
                if (isset($parameter['remote'])) {
                    $Remote = new RemoteClient($app);
                    if (false === ($result = $Remote->getInfo($parameter, self::$config,
                        $this->getCMSlocale(), $this->getBasicSettings()))) {
                        $result = $this->getAlert();
                    }
                    return $result;
                }
            default:
                $this->setAlert('The parameter <code>%parameter%[%value%]</code> for the kitCommand <code>~~ %command% ~~</code> is unknown, please check the parameter and the given value!',
                    array('%parameter%' => 'action', '%value%' => $parameter['action'], '%command%' => 'flexContent'), self::ALERT_TYPE_DANGER);
                return $this->promptAlert();
        }
    }

}
