<?php

/**
 * flexContent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://addons.phpmanufaktur.de/flexContent
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\flexContent\Control\Backend;

use Symfony\Component\HttpFoundation\Response;
use Silex\Application;
use phpManufaktur\flexContent\Data\Content\TagType;

class TagResponse
{
    /**
     * Controller for the TAG autocomplete in the flexContent dialog
     *
     * @param Application $app
     * @throws \Exception
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function ControllerAutocomplete(Application $app)
    {
        if (null == ($search = $app['request']->query->get('term'))) {
            throw new \Exception('Missing the GET parameter `term`!');
        }

        $TagType = new TagType($app);
        $results = $TagType->selectLikeName($search);

        $result = array();

        foreach ($results as $tag) {
            // loop through the results and create the autocomplete response
            $result[] = '{"id":'.$tag['tag_id'].',"label":"'.$tag['tag_name'].'","value":"'.$tag['tag_name'].'"}';
        }
        return new Response("[".implode(',', $result)."]");
    }

}
