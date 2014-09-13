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

class flexContentFrame extends Basic
{
    /**
     * Controller to create the iFrame for the Content kitCommands.
     * Execute the route /content/action
     *
     * @param Application $app
     */
    public function controllerFlexContentFrame(Application $app)
    {
        $this->initParameters($app);

        $ConfigurationData = new Configuration($app);
        $config = $ConfigurationData->getConfiguration();

        // get the preferred template
        $template = $this->getPreferredTemplateStyle();

        if (!file_exists(MANUFAKTUR_PATH.'/flexContent/Template/'.$template.'/command/content.twig')) {
            $template = 'default';
        }

        $subRequest = Request::create('/flexcontent/action', 'GET', array(
            'pid' => self::getParameterID()));

        return $app->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    }
}
