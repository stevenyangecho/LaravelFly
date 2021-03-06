<?php

namespace LaravelFly;

use LaravelFly\Exception\LaravelFlyException as Exception;
use LaravelFly\Exception\LaravelFlyException;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Fly
{

    /**
     * @var \LaravelFly\Server\ServerInterface | \LaravelFly\Server\HttpServer
     */
    protected static $server;

    /**
     * @var Fly
     */
    protected static $instance;

    static $flyMap = [
        'Container.php' => '/vendor/laravel/framework/src/Illuminate/Container/Container.php',
        'Application.php' => '/vendor/laravel/framework/src/Illuminate/Foundation/Application.php',
        'ServiceProvider.php' => '/vendor/laravel/framework/src/Illuminate/Support/ServiceProvider.php',
        'FileViewFinder.php' => '/vendor/laravel/framework/src/Illuminate/View/FileViewFinder.php',
        'Router.php' => '/vendor/laravel/framework/src/Illuminate/Routing/Router.php',
        'ViewConcerns/ManagesComponents.php' => '/vendor/laravel/framework/src/Illuminate/View/Concerns/ManagesComponents.php',
        'ViewConcerns/ManagesLayouts.php' => '/vendor/laravel/framework/src/Illuminate/View/Concerns/ManagesLayouts.php',
        'ViewConcerns/ManagesLoops.php' => '/vendor/laravel/framework/src/Illuminate/View/Concerns/ManagesLoops.php',
        'ViewConcerns/ManagesStacks.php' => '/vendor/laravel/framework/src/Illuminate/View/Concerns/ManagesStacks.php',
        'ViewConcerns/ManagesTranslations.php' => '/vendor/laravel/framework/src/Illuminate/View/Concerns/ManagesTranslations.php',
        'Facade.php' => '/vendor/laravel/framework/src/Illuminate/Support/Facades/Facade.php',

        //blackhole
        'Collection.php' => '/vendor/laravel/framework/src/Illuminate/Support/Collection.php',
        'Controller.php' => '/vendor/laravel/framework/src/Illuminate/Routing/Controller.php',
        'Relation.php' => '/vendor/laravel/framework/src/Illuminate/Database/Eloquent/Relations/Relation.php',
    ];

    /**
     * @param array $options
     * @param EventDispatcher $dispatcher
     */
    static function init(array $options, EventDispatcher $dispatcher = null): self
    {
        if (self::$instance) return self::$instance;

        static::initEnv();

        if (null === $dispatcher)
            $dispatcher = new EventDispatcher();

        printf("[INFO] server dispatcher created\n");

        static::$instance = new static();

        $class = LARAVELFLY_MODE === 'FpmLike' ? \LaravelFly\Server\FpmHttpServer::class : $options['server'];

        static::$server = new $class($dispatcher);

        static::$server->config($options);

        static::$server->createSwooleServer();

        return self::$instance;
    }

    static protected function initEnv()
    {

        require_once __DIR__ . '/../functions.php';

        if (class_exists('NunoMaduro\Collision\Provider'))
            (new \NunoMaduro\Collision\Provider)->register();

        if (defined('LARAVELFLY_MODE') && LARAVELFLY_MODE === 'Map') {
            foreach (static::$flyMap as $f => $offical) {
                require __DIR__ . "/../fly/" . $f;
            }
        }

    }

    public static function getInstance($options = null):self
    {

        if (!self::$instance) {
            static::init($options);
        }
        return self::$instance;
    }

    function start()
    {
        static::$server->start();
    }


    function getDispatcher()
    {
        return static::$server->getDispatcher();
    }

    function getServer()
    {
        return static::$server;
    }

}
