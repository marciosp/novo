<?php

/**
 * 
 * This file is part of the PhpExtJS plugin for S framework.
 * 
 * @license http://opensource.org/licenses/GPL-3.0 GPL-3.0
 * 
 * @author Vitor de Souza <vitor_souza@outlook.com>
 * @date 31/07/2013
 * 
 */

namespace S\Plugins\PhpExtJS;

// V.Hook
use \V\Hook\Manager as HookManager;
use \V\Hook\Hook;

/**
 * 
 * Loads a Controller, checking first if it is the Repo (you must use this class in you SystemLocator class)
 * 
 * @author Vitor de Souza <vitor_souza@outlook.com>
 * @date 31/07/2013
 * 
 */
class Loader
{

    /**
     * 
     * Creates the Controller
     * 
     * @param string $controller_name The Controller's name
     * @param string $controller_url_id The Controller's URL ID
     * 
     * @return Controller
     * 
     * @author Vitor de Souza <vitor_souza@outlook.com>
     * @date 31/07/2013
     * 
     */
    public static function getController($controller_name, $controller_url_id)
    {

        // first of all, we can configure the hook before_action, so we can handle the request before it gets to the action
        $before_action = new Hook('S\\App', 'before_action', function($app, $manager, $request) {
                            if (isset($request->params['i']))
                                $request->params = json_decode($request->params['i']);
                        });
        HookManager::hook($before_action);

        // get the controller's ID
        $id = $controller_name::id();
        // get the controller from the repo
        $controller = Repo::get($id);

        // if the controller is in the Repo
        if (is_object($controller))
            return $controller;

        // return a new instance if the controller isn't in the repo
        $new_controller = new $controller_name;
        $new_controller->url_id = $controller_url_id;

        // return the new Controller
        return $new_controller;
    }

}