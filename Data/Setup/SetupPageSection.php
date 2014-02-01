<?php

/**
 * flexContent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/flexContent
 * @copyright 2014 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\flexContent\Data\Setup;

use Silex\Application;
use phpManufaktur\Basic\Control\CMS\InstallPageSection;

class SetupPageSection
{
    public function ControllerSetupPageSection(Application $app)
    {
        // setup kit_framework_flexcontent_section_access
        $section_access = new InstallPageSection($app);
        $section_access->exec(MANUFAKTUR_PATH.'/flexContent/extension.json', '/flexcontent/cms');

        $app['monolog']->addDebug('[flexContent] Successfull installed flexContent as CMS Page Section');

        return $app['translator']->trans('[flexContent] Successfull installed flexContent as CMS Page Section');
    }
}
