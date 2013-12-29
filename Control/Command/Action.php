<?php

/**
 * flexContent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/event
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\flexContent\Control\Command;

use phpManufaktur\Basic\Control\kitCommand\Basic;
use Silex\Application;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;

class Action extends Basic
{

    public function controllerAction(Application $app)
    {
        $this->initParameters($app);
        // get the kitCommand parameters
        $parameters = $this->getCommandParameters();

        // check the CMS GET parameters
        $GET = $this->getCMSgetParameters();
        if (isset($GET['command']) && ($GET['command'] == 'flexcontent')) {
            // the command and parameters are set as GET from the CMS
            foreach ($GET as $key => $value) {
                if ($key == 'command') continue;
                $parameters[$key] = $value;
            }
            $this->setCommandParameters($parameters);
        }

        if (!isset($parameters['action'])) {
            // there is no 'mode' parameter set, so we show the "Welcome" page
            $subRequest = Request::create('/basic/help/flexcontent/welcome', 'GET');
            return $app->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
        }

        switch (strtolower($parameters['action'])) {
            case 'view':
                $View = new ActionView();
                return $View->controllerView($app);
            default:
                $this->setAlert('The parameter <code>%parameter%[%value%]</code> for the kitCommand <code>~~ %command% ~~</code> is unknown, please check the parameter and the given value!',
                    array('%parameter%' => 'action', '%value%' => $parameters['action'], '%command%' => 'flexContent'), self::ALERT_TYPE_DANGER);
                return $this->promptAlert();
        }


    }
}
