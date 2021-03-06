<?php

/**
 * 
 * This file is part of the S framework for PHP, a framework based on the O framework.
 * 
 * @license http://opensource.org/licenses/GPL-3.0 GPL-3.0
 * 
 * @author Vitor de Souza <vitor_souza@outlook.com>
 * @date 19/07/2013
 * 
 */

namespace S\Login;

/**
 * 
 * Login Controller
 * 
 * @author Vitor de Souza <vitor_souza@outlook.com>
 * @date 19/07/2013
 * 
 */
class Controller extends \O\Controller
{

    /**
     * 
     * Fetch the login form view
     * 
     * @author Vitor de Souza <vitor_souza@outlook.com>
     * @date 19/07/2013
     * 
     */
    public function init()
    {
        return array(
            'body' => $this->getTemplate()->fetch(__DIR__ . '/Views/Index.php')
        );
    }

}
