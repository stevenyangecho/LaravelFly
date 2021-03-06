<?php

namespace LaravelFly\Tests\Map\Feature;

use LaravelFly\Tests\BaseTestCase;
use Symfony\Component\EventDispatcher\GenericEvent;

class ObjectsInWorkerTest extends BaseTestCase
{
    /**
     * @var \Swoole\Channel
     */
    static protected $chan;

    protected $instances = [
        'path',
        'path.base',
        'path.lang',
        'path.config',
        'path.public',
        'path.storage',
        'path.database',
        'path.resources',
        'path.bootstrap',
        'app',
        'Illuminate\Foundation\Container',
        'Illuminate\Foundation\PackageManifest',
        'events',
        'router',
        'Illuminate\Contracts\Http\Kernel',
        'config',
        'db.factory',
        'db',
        'view.engine.resolver',
        'files',
        'view',
        'Illuminate\Contracts\Auth\Access\Gate',
        'routes',
        'url',
        'Illuminate\Contracts\Debug\ExceptionHandler',
        'translation.loader',
        'translator',
        'validation.presence',
        'validator',
        'session',
        'session.store',
        'Illuminate\Session\Middleware\StartSession',
        'hash',
        'filesystem',
        'filesystem.disk',
        'encrypter',
        'cookie',
        'cache',
        'cache.store',
        'auth',
        'log',
        'blade.compiler',
    ];

    protected $allStaticProperties = [
        'app' => ['instance'],
        'Illuminate\Foundation\Container' => ['instance'],
        'router' => ['macros','verbs'],
        'files' => ['macros'],
        'view' => ['parentPlaceholder'],
        'url' => ['macros'],
        'translator' => ['macros'],
        'cache.store' => ['macros'],
        'blade.compiler' => ['mapFly'],
    ];

    static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        static::makeServer(['LARAVELFLY_MODE' => 'Map'], ['worker_num' => 1]);

        static::$chan = $chan = new \Swoole\Channel(1024 * 256);

        static::$dispatcher->addListener('worker.ready', function (GenericEvent $event) use ($chan) {
            $appR = new \ReflectionObject($event['app']);
            $corDictR = $appR->getProperty('corDict');
            $corDictR->setAccessible(true);
            $instances = $corDictR->getValue()[WORKER_COROUTINE_ID]['instances'];

            $chan->push(array_keys($instances));

            $allStaticProperties = [];
            foreach ($instances as $name => $instance) {
                if (!is_object($instance)) continue;
                $instanceR = new \ReflectionObject($instance);
                $staticProperties = array_keys($instanceR->getStaticProperties());
                if ($staticProperties) {
                    $clean = array_diff($staticProperties, ['corDict', 'corStaticDict',
                        'normalAttriForObj','arrayAttriForObj','normalStaticAttri','arrayStaticAttri'
                        ]);
                    if ($clean){
                        sort($clean); // force it index from 0 ,otherwise self::assertEqual fail
                        $allStaticProperties[$name] = $clean;
                    }
                }
            }
            $chan->push($allStaticProperties);

            sleep(3);
            $event['server']->getSwoole()->shutdown();
        });

        static::$server->start();

    }

    function testInstances()
    {
        $instances = static::$chan->pop();
        self::assertEquals($this->instances, $instances);
    }

    function testStaticProperties()
    {
        $allStaticProperties =  static::$chan->pop();
        self::assertEquals($this->allStaticProperties, $allStaticProperties);
    }
}

