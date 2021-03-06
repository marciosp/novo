<?php

/**
 * 
 * This file is part of the S framework for PHP, a framework based on the O framework.
 * 
 * @license http://opensource.org/licenses/GPL-3.0 GPL-3.0
 * 
 * @author Vitor de Souza <vitor_souza@outlook.com>
 * @date 17/07/2013
 * 
 */

namespace S;

// V.Hook
use \V\Hook\Manager as HookManager;
use \V\Hook\Hook;
// V.Http
use \V\Http\Header;
use \V\Http\Message;
use \V\Http\Response;
// V.Session
use \V\Session\Manager as SessionManager;
use \V\Session\Segment;
// V.Router
use \V\Router\Request;
use \V\Router\RequestInterface;
use \V\Router\RouteInterface;
// V.I18n
use V\I18n\Manager as I18nManager;
use V\I18n\Locale;
use V\I18n\LocaleLocator;
use V\I18n\Translation;

/**
 * 
 * Your bootstrap file must instanciate this class
 * 
 * @author Vitor de Souza <vitor_souza@outlook.com>
 * @date 17/07/2013
 * 
 */
class App
{

    /**
     * 
     * The config passed in the constructor
     * 
     * @var array
     * 
     * @author Vitor de Souza <vitor_souza@outlook.com>
     * @date 17/07/2013
     * 
     */
    private static $cfg;

    /**
     * 
     * The Translator object
     * 
     * @var V\I18n\Translator
     * 
     * @author Vitor de Souza <vitor_souza@outlook.com>
     * @date 25/07/2013
     * 
     */
    private static $t;

    /**
     * 
     * Sets the config array
     * 
     * @param array $cfg App configuration
     * 
     * @return App
     * 
     * @author Vitor de Souza <vitor_souza@outlook.com>
     * @date 17/07/2013
     */
    public function __construct(array $cfg)
    {
        self::$cfg = $cfg;
    }

    /**
     * 
     * Starts the App
     * 
     * @return void
     * 
     * @author Vitor de Souza <vitor_souza@outlook.com>
     * @date 17/07/2013
     */
    public function run()
    {

        // loads O framework
        $this->loadO();
    }

    /**
     * 
     * Loads the translations
     * 
     * @return void
     * 
     * @author Vitor de Souza <vitor_souza@outlook.com>
     * @date 22/07/2013
     */
    private function loadTranslations()
    {
        $cfg = self::$cfg;

        // init the translator
        $locale_locator = new LocaleLocator;
        $manager = new I18nManager($locale_locator);

        // Portuguese translations
        $locale1 = new Locale('pt_BR');
        $locale1->addTranslations(array(
            'WRONG_CREDENTIALS' => new Translation('Usu&aacute;rio ou senha inv&aacute;lidos!'),
            'SIGNIN' => new Translation('Entrar'),
            'RESET' => new Translation('Limpar'),
            'TRYAGAINLATER' => new Translation('Tente novamente mais tarde.'),
            'FAILED' => new Translation('Falha'),
            'USERFIELD' => new Translation('Usu&aacute;rio'),
            'PASSWORDFIELD' => new Translation('Senha'),
            'LOGOUT' => new Translation('Sair')
        ));

        // English translations
        $locale2 = new Locale('en_US');
        $locale2->addTranslations(array(
            'WRONG_CREDENTIALS' => new Translation('Wrong user or password!'),
            'SIGNIN' => new Translation('Sign in'),
            'RESET' => new Translation('Reset'),
            'TRYAGAINLATER' => new Translation('Try again later.'),
            'FAILED' => new Translation('Failed'),
            'USERFIELD' => new Translation('User'),
            'PASSWORDFIELD' => new Translation('Password'),
            'LOGOUT' => new Translation('Logout')
        ));

        // set the translations
        $manager->set('S', $locale1);
        $manager->set('S', $locale2);

        // default en_US locale
        $manager->setLocale($cfg['locale'] ? $cfg['locale'] : 'en_US');

        // get the translator object
        self::$t = $manager->get('S');
    }

    /**
     * 
     * Loads O framework
     * 
     * @return void
     * 
     * @author Vitor de Souza <vitor_souza@outlook.com>
     * @date 17/07/2013
     */
    private function loadO()
    {
        $cfg = self::$cfg;

        // includes the O App file (because autoload is started by O)
        include rtrim($cfg['paths']['O'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'App.php';

        $me = $this;

        // creates the O App
        $app = new \O\App(array(
                    'environment' => $cfg['environment'],
                    'paths' => array(
                        // V Path
                        'V' => $cfg['paths']['V']
                    ),
                    'router' => array(
                        // S config base path
                        'base_path' => $cfg['paths']['base_path'],
                        //
                        // Default S Routes
                        'routes' => array_merge(array(
                            //
                            // Webservices
                            'webservices/{api}[/*]' => array(
                                'do' => function($api, $params = '') use($cfg) {
									
                                    $request = new Request;

                                    // get the API LOCATOR (check whether the request is coming from a module)
                                    if (isset($_GET['module']) && $_GET['module'] #
                                            && isset($_GET['module_name']) && $_GET['module_name']) {
                                        $module_cfg = include rtrim($cfg['paths']['modules_path'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $_GET['module_name'] . DIRECTORY_SEPARATOR . 'Module.php';
                                        $locator = $module_cfg['locator']['apis']();
                                    } else {
                                        $locator = $cfg['locator']['apis']();
                                    }

                                    // creates the O\Manager
                                    $manager = new \O\Manager(
                                                    $locator, #
                                                    $api, #
                                                    // check for custom services that are not named "get", "post", "put" and "delete" in the __METHOD parameter
                                                    isset($_GET['__METHOD']) ? $_GET['__METHOD'] : strtolower($request->getMethod()) #
                                    );

                                    // exec the service
                                    try {
                                        $response = $manager->exec($request, $params ? explode('/', $params) : array());
                                    }
                                    // check for HTTP errors
                                    catch (\O\Exceptions\E501 $e) {
                                        $cfg['REST']['errors']['501']($e);
                                    } catch (\O\Exceptions\E500 $e) {
                                        $cfg['REST']['errors']['500']($e);
                                    } catch (\O\Exceptions\E404 $e) {
                                        $cfg['REST']['errors']['404']($e);
                                    }

                                    // if your HTTP errors treatment don't stop the script, we stop here in case of a failure
                                    (isset($response) && is_object($response)) || die();

                                    // send the response
                                    $response->send();
                                },
                                //
                                // route filters
                                'filters' => array('auth_apis')
                            ),
                            //
                            // Systems
                            'systems/{page}[/{action}]' => array(
                                'do' => function($page, $action = 'init') use($cfg, $me) {
                                    ob_start();

                                    $request = new Request;

                                    // get the SYSTEM LOCATOR (check whether the request is coming from a module)
                                    if (isset($_GET['module']) && $_GET['module'] #
                                            && isset($_GET['module_name']) && $_GET['module_name']) {
                                        $module_cfg = include rtrim($cfg['paths']['modules_path'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $_GET['module_name'] . DIRECTORY_SEPARATOR . 'Module.php';
                                        $locator = $module_cfg['locator']['systems']();
                                    } else {
                                        $locator = $cfg['locator']['systems']();
                                    }

                                    // Create the O\Manager
                                    $manager = new \O\Manager($locator, $page, $action);

                                    // fires before execute the controller action, so plugins can configure somethings
                                    HookManager::fire($me, 'before_action', array($this, $manager, $request, $page, $action));

                                    // execute the action
                                    try {
                                        $response = $manager->exec($request);
                                    }
                                    // check for HTTP errors
                                    catch (\O\Exceptions\E501 $e) {
                                        $cfg['REST']['errors']['501']($e);
                                    } catch (\O\Exceptions\E500 $e) {
                                        $cfg['REST']['errors']['500']($e);
                                    } catch (\O\Exceptions\E404 $e) {
                                        $cfg['REST']['errors']['404']($e);
                                    }

                                    // if your HTTP errors treatment don't stop the script, we stop here in case of a failure
                                    (isset($response) && is_object($response)) || die();

                                    // get the output untill now
                                    $untill_now = ob_get_clean();

                                    // add the output untill now to the response object (to avoid: cannot modify headers inform...)
                                    $response->message->setBody($untill_now . $response->message->getBody());

                                    // send the response
                                    $response->send();
                                },
                                //
                                // route filters
                                'filters' => array('auth_systems')
                            ),
                            //
                            // Main Page (default S main page)
                            '/' => array(
                                'do' => function() {

                                    // if we got here, so we have to open the S main view
                                    $controller = new Main\Controller;
                                    $content = $controller->init();

                                    $message = new Message();
                                    $message->setStatusCode(200);
                                    $message->setBody($content['body']);

                                    $response = new Response($message);
                                    $response->send();
                                },
                                //
                                // route filters
                                'filters' => array('auth_systems')
                            ),
                            //
                            // Logout (default S logout)
                            '/logout' => array(
                                'do' => function() use($cfg) {

                                    // starts and destroy the session (S login control is made upon session)
                                    $manager = SessionManager::instance();
                                    $manager->start();
                                    $manager->destroy();

                                    // send back to login page
                                    $message = new Message();

                                    $header = new Header('Location', $cfg['paths']['base_path']);
                                    $message->addHeader($header);

                                    $response = new Response($message);
                                    $response->send();
                                }
                            ),
							//
							// External scripts that may want to use S Framework can be loaded through this URL /load/var/www/html/myscript.php
                            'load[/*]' => array(
                                'do' => function($file = '') use($cfg) {
									$file = DIRECTORY_SEPARATOR . ltrim($file, DIRECTORY_SEPARATOR);
									if($file && file_exists($file))
										include $file;
								}
							)
                        ), isset($cfg['extra_routes']) ? $cfg['extra_routes'] : array()),
                        // 
                        // route filters (default lock for access to systems and webservices)
                        'filters' => array(
                            //
                            // this one protects your webservices/apis (if you don't want to protect it, just create a function that returns true in your config :D)
                            'auth_apis' => function(RouteInterface $route, RequestInterface $request) use($cfg) {
								
                                //
                                // call user API auth closure (expects return of true or false), passing HTTP BASIC USER and HTTP BASIC PASSWORD 
                                // (by default, S expects you'll use basic authentication with your webservices)
                                $result = $cfg['auth']['apis'](
                                        $request->basic ? $request->basic->user : '', #
                                        $request->basic ? $request->basic->pass : '', #
                                        $route, #
                                        $request
                                );

                                // if the user and password are OK!
                                if ($result && $request->basic) {

                                    // store the user in the session (log in the user)
                                    $manager = SessionManager::instance();
                                    $user = new Segment($manager, 'user');
                                    $user->login = $request->basic->user;
                                }

                                // return the result
                                return $result;
                            },
                            'auth_systems' => function(RouteInterface $route, RequestInterface $request) use($cfg) {

                                // check for "return true", in the cases the App has no login page
                                if ($cfg['auth']['systems']('', '', $route, $request))
                                    return true;

                                // checks the Session (if we are logged)
                                $manager = SessionManager::instance();
                                $user = new Segment($manager, 'user');
                                return isset($user->login);
                            }
                        )
                    ),
                    //
                    // UI config
                    'UI' => $cfg['UI'],
                    //
                    // REST config
                    'REST' => array(
                        'base_url' => $cfg['REST']['base_url']
                    )
                ));

        // error & exception handling
        isset($cfg['error_handler']) && is_callable($cfg['error_handler']) && set_error_handler($cfg['error_handler']);
        isset($cfg['exception_handler']) && is_callable($cfg['exception_handler']) && set_exception_handler($cfg['exception_handler']);
		
        // load translations stuff
        $this->loadTranslations();

        // some events (Right before route anything)
        $register_s = new Hook($app, 'before_route', function($app) use($cfg) {

                            // the Loader
                            $loader = $app->getLoader();

                            // register S in the Autoload, so we can create S classes as well as we create O and V classes
                            $loader->registerNamespace('S', __DIR__);

                            // if we've passed some extra namespace to add to our Autoloader, here we configure them
                            if (isset($cfg['autoload'])) {
                                if (isset($cfg['autoload']['prefixes'])) {
                                    $loader->registerPrefixes($cfg['autoload']['prefixes']);
                                } elseif (isset($cfg['autoload']['namespaces'])) {
                                    $loader->registerNamespaces($cfg['autoload']['namespaces']);
                                }
                            }
                        });

        // some events (When we have a route that exists but there is a filter that invalidated it)
        $invalid_route = new Hook($app, 'invalid_route', function($app, $e, $router, $routes, $request, $dispatcher) use($cfg) {

                            // webservices auth
                            if (0 === strpos($request->getUri(), rtrim($cfg['paths']['base_path'], '/') . '/webservices/')) {

                                // HTTP BASIC AUTH
                                $message = new Message();
                                $message->setStatusCode(401);

                                $header = new Header('WWW-Authenticate', 'Basic realm="Password protected webservices!"');
                                $message->addHeader($header);

                                $response = new Response($message);
                                $response->send();
                            }
                            // login page
                            elseif (rtrim($request->getUri(), '/') === rtrim($cfg['paths']['base_path'], '/')) {

                                // if we submitted the login form
                                if ($request->getMethod() === 'post' && isset($request->params['user']) && isset($request->params['pass'])) {

                                    // check the user and password with your auth function
                                    if ($cfg['auth']['systems']($request->params['user'], $request->params['pass'])) {
                                        //
                                        // store the user in the session (log in the user)
                                        $manager = SessionManager::instance();
                                        $user = new Segment($manager, 'user');
                                        $user->login = $request->params['user'];

                                        // tell extjs everything is fine
                                        die(json_encode(array('success' => true)));
                                    } else {

                                        // tell extjs that we have wrong user or password
                                        die(json_encode(array(
                                                    'failure' => true,
                                                    'msg' => (string) App::t('WRONG_CREDENTIALS')
                                                )));
                                    }
                                } else {

                                    // if we got here, so we have to open the login form
                                    $controller = new Login\Controller;
                                    $content = $controller->init();

                                    $message = new Message();
                                    $message->setStatusCode(200);
                                    $message->setBody($content['body']);

                                    $response = new Response($message);
                                    $response->send();
                                }
                            }
                            // systems auth
                            else {

                                // if we got here we are trying to enter a URL we do not have access to, so we'll redirect the user to the login form
                                if (!isset($_GET['callback']) && !(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {

                                    // for NON JSONP requests and NON AJAX requests
                                    $message = new Message();

                                    $header = new Header('Location', $cfg['paths']['base_path']);
                                    $message->addHeader($header);

                                    $response = new Response($message);
                                    $response->send();
                                } else {

                                    // check for a JSONP/AJAX request (in this case we can't simple send an header with Location to the homepage, we need to send a javascript code
                                    // that redirects the user to the homepage instead)
                                    die("document.location.href = '{$cfg['paths']['base_path']}';");
                                }
                            }
                        });

        // some events (NOT FOUND treatment)
        $route_not_found = new Hook($app, 'route_not_found', function($app, $e, $router, $routes, $request, $dispatcher) use($cfg) {
							
                            $cfg['REST']['errors']['404']($app, $e, $router, $routes, $request, $dispatcher);
                        });

        // Hook the hooks :D
        HookManager::hook($invalid_route, $route_not_found, $register_s);

        // init the O application
        $app->init();
    }

    /**
     * 
     * CFG Acessor
     * 
     * @return array
     * 
     * @author Vitor de Souza <vitor_souza@outlook.com>
     * @date 19/07/2013
     */
    public static function cfg()
    {
        return self::$cfg;
    }

    /**
     * 
     * Get the translation of a given text
     * 
     * @param $text string The ID of the text to be translated
     * 
     * @return \V\I18n\Translation
     * 
     * @author Vitor de Souza <vitor_souza@outlook.com>
     * @date 25/07/2013
     */
    public static function t($text)
    {
        return self::$t->get($text);
    }

    /**
     * 
     * User's session data access
     * 
     * @return V\Session\Segment
     * 
     * @author Vitor de Souza <vitor_souza@outlook.com>
     * @date 02/08/2013
     */
    public static function user()
    {
        $manager = SessionManager::instance();
        return new Segment($manager, 'user');
    }

}
