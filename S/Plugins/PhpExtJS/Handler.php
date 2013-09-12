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

/**
 * 
 * The object that will convert some PHP config to a JS function
 * 
 * @author Vitor de Souza <vitor_souza@outlook.com>
 * @date 31/07/2013
 * 
 */
class Handler
{

    /**
     * 
     * Returns an JSONP function call to get back to the controller
     * 
     * @param array $cfg An config array
     * 
     * @return string the JS function
     * 
     * @author Vitor de Souza <vitor_souza@outlook.com>
     * @date 31/07/2013 | 13/08/2013
     * 
     * The config array must have these keys:
     *      'controller' - The controller instance
     *      'method' - The controller's method to execute in the request
     *      'params' - The parameters to pass (an array of identifier => javascript codes that get values of the template)
     *      'form_id' - The form ID (optional - if passed, will exec the form.isValid() function before anything and only proceed if it returns true)
     * 
     */
    public static function ajax(array $cfg)
    {

        // the controller
        $controller = $cfg['controller'];

        // store the controller
        Repo::store($controller->id, $controller);

        // S pattern URL of method call
        $url = 'systems/' . $controller->url_id . '/' . $cfg['method'];

        // the params
        $params = isset($cfg['params']) ? str_replace(array('"%', '%"'), '', Encoder::encode($cfg['params'])) : '[]';

        // form validation
        $check_form = isset($cfg['form_id']) ? "if(!Ext.getCmp('{$cfg['form_id']}').getForm().isValid())return;" : '';

        // the JS function
        return "%function() { {$check_form} S.s();Ext.data.JsonP.request({url:'{$url}',params: {i:JSON.stringify({$params})},failure: S.failure, success: S.success[Ext.getCmp('s-win') ? 'win' : 'normal']}); }%";
    }

    /**
     * 
     * Returns an pure JS function to execute only JS stuff
     * 
     * @param array $codes an array of JS codes, without the ";" at the end
     * 
     * @return string the JS function
     * 
     * @author Vitor de Souza <vitor_souza@outlook.com>
     * @date 31/07/2013
     * 
     */
    public static function js(array $codes)
    {
        return "%function() { " . implode(';', $codes) . " }%";
    }

    /**
     * 
     * Returns a pure JS function to execute when a given KEY is pressed (must be put in a listener specialkey, for example)
     * 
     * @param array $cfg a config array
     * 
     * @return string the JS function
     * 
     * @author Vitor de Souza <vitor_souza@outlook.com>
     * @date 31/07/2013
     * 
     * The config array must have these keys:
     *      'key' - The KEY (e.MYKEY - used by ExtJS, for example 'ENTER')
     *      'exec' - an array of JS codes like the one used in Handler::js
     * 
     */
    public static function key(array $cfg)
    {
        return "%function() { if(e.getKey() === e.{$cfg['key']}) { " . implode(';', $cfg['exec']) . " } }%";
    }

}