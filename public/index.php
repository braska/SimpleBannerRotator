<?php
try
{
    defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(__DIR__."/../app/"));

    require_once APPLICATION_PATH . '/../vendor/autoload.php';

    $config = new \Phalcon\Config\Adapter\Ini(APPLICATION_PATH.'/config/config.ini');

    define('ENVIRONMENT_DEVELOPMENT', 'development');
    define('ENVIRONMENT_PRODUCTION', 'production');
    
    date_default_timezone_set($config->app->timezone);
    setlocale(LC_ALL, $config->app->locale);

    define('ENVIRONMENT', (($config->app->environment == ENVIRONMENT_DEVELOPMENT or $config->app->environment == ENVIRONMENT_PRODUCTION) ? $config->app->environment : ENVIRONMENT_PRODUCTION));

    if(ENVIRONMENT == ENVIRONMENT_DEVELOPMENT) {
        ini_set('display_errors', 1);
        error_reporting(E_ALL);
    }

    $loader = new \Phalcon\Loader();
    $loader->registerNamespaces(array(
        'App\Controllers' => APPLICATION_PATH.$config->app->controllersDir,
        'App\Models' => APPLICATION_PATH.$config->app->modelsDir,
        'App\Library' => APPLICATION_PATH.$config->app->libraryDir,
    ))->register();

    $di = new Phalcon\DI\FactoryDefault();

    $di->set('config', $config);

    $di->set('router', function(){
        require APPLICATION_PATH.'/config/routes.php';
        $router->removeExtraSlashes(true);
        return $router;
    });

    $di->set('url', function() use ($di){
        $url = new \Phalcon\Mvc\Url();
        $url->setBaseUri('http://'.$_SERVER['HTTP_HOST'].'/');
        return $url;
    });

    $di->set('view', function() use ($config) {
        $view = new \Phalcon\Mvc\View();
        $view->setViewsDir(APPLICATION_PATH.$config->app->viewsDir);
        return $view;
    });

    $di->setShared('db', function() use ($config, $di) {

        $connection = new \Phalcon\Db\Adapter\Pdo\Mysql(array(
            "host" => $config->database->host,
            "username" => $config->database->username,
            "password" => $config->database->password,
            "dbname" => $config->database->dbname,
            "charset"   => $config->database->charset
        ));

        return $connection;
    });

    $di->set('dispatcher', function() use ($di){
        $dispatcher = new \Phalcon\Mvc\Dispatcher();
        $eventsManager = $di->getShared('eventsManager');
        if(ENVIRONMENT == ENVIRONMENT_PRODUCTION) {

            $eventsManager->attach(
                'dispatch:beforeException',
                function($event, $dispatcher, $exception) {
                    switch ($exception->getCode()) {
                        case 404:
                        case \Phalcon\Mvc\Dispatcher::EXCEPTION_HANDLER_NOT_FOUND:
                        case \Phalcon\Mvc\Dispatcher::EXCEPTION_ACTION_NOT_FOUND:
                            $dispatcher->forward(
                                array(
                                    "namespace" => 'App\Controllers',
                                    'controller' => 'error',
                                    'action' => 'notFound',
                                )
                            );
                            return false;
                            break;
                        default:
                            $dispatcher->forward(
                                array(
                                    "namespace" => 'App\Controllers',
                                    'controller' => 'error',
                                    'action' => 'uncaughtException',
                                )
                            );
                            return false;
                            break;
                    }
                }
            );
        }
        $eventsManager->attach('dispatch', new \App\Library\Zonner());

        $dispatcher->setEventsManager($eventsManager);
        $dispatcher->setDefaultNamespace('App\Controllers');
        $dispatcher->setDefaultController('index');
        $dispatcher->setDefaultAction('index');
        return $dispatcher;
    });

    $di->setShared('cookies', function() {
        $cookies = new Phalcon\Http\Response\Cookies();
        $cookies->useEncryption(true);
        return $cookies;
    });

    $di->setShared('session', function() use ($di) {
        $session = new Phalcon\Session\Adapter\Files(array(
            'uniqueId' => 'banners'
        ));
        session_name("bannerssessid");
        $session->start();
        return $session;
    });

    $di->setShared('auth', function() {
        return new \App\Library\Auth();
    });

    $di->set('crypt', function() use ($config) {
        $crypt = new Phalcon\Crypt();
        $crypt->setKey($config->crypt->key);
        return $crypt;
    });

    $di->setShared('flashSession', function() {
        $flash = new \Phalcon\Flash\Session(array(
            'warning' => 'alert alert-warning',
            'notice' => 'alert alert-info',
            'success' => 'alert alert-success',
            'error' => 'alert alert-danger',
            'dismissable' => 'alert alert-dismissable',
        ));
        return $flash;
    });

    $di->set("request", 'Phalcon\Http\Request');

    require '../app/config/vars.php';
    $di->setShared('vars', $vars);

    if(ENVIRONMENT == ENVIRONMENT_PRODUCTION) {
        $di->set('modelsMetadata', function() use ($config) {
            $metaData = new \Phalcon\Mvc\Model\MetaData\Files(array(
                "lifetime" => 86400,
                "prefix"   => "goldkamea",
                'metaDataDir' => APPLICATION_PATH.$config->app->cacheDir.'metadata/'
            ));

            return $metaData;
        });
    }


    $di->set('viewCache', function() use ($config){

        //Cache data for one day by default
        $frontCache = new Phalcon\Cache\Frontend\Output(array(
            "lifetime" => 86400
        ));

        //File backend settings
        $cache = new Phalcon\Cache\Backend\File($frontCache, array(
            "cacheDir" => APPLICATION_PATH.$config->app->cacheDir,
            "prefix" => "php"
        ));

        return $cache;
    });

    //Handle the request
    $application = new \Phalcon\Mvc\Application();
    $application->setDI($di);
    echo $application->handle()->getContent();
}
catch(\Phalcon\Exception $e)
{
     echo "PhalconException: ", $e->getMessage();
}