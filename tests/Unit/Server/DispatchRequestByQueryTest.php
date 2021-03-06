<?php

namespace LaravelFly\Tests\Unit\Server;

//use LaravelFly\Tests\BaseTestCase;

use Symfony\Component\EventDispatcher\GenericEvent;

class DispatchRequestByQueryTest extends BaseTestCase
{
    function testDispatchCallbackByWorkerId()
    {

        $server = static::$commonServer;

        $data = [
            ['worker_num' => 5, 'raw' => 'GET /fly?worker-id=0 HTTP/1.1', 'fd' => 99, 'selected' => 0],
            ['worker_num' => 5, 'raw' => 'GET /fly?worker-id=2 HTTP/1.1', 'fd' => 99, 'selected' => 2],
            ['worker_num' => 5, 'raw' => 'GET /fly?worker-id=3 HTTP/1.1', 'fd' => 99, 'selected' => 3],
            ['worker_num' => 5, 'raw' => "GET /fly HTTP/1.1\nWorker-Id: 0", 'fd' => 99, 'selected' => 0],
            ['worker_num' => 5, 'raw' => "GET /fly HTTP/1.1\nWorker-Id: 9", 'fd' => 99, 'selected' => 4],
        ];

        foreach ($data as $one) {
            $this->resetConfigAndDispatcher();
            $options = array_merge(self::$default, ['worker_num' => $one['worker_num'], 'compile' => false]);
            $server->config($options);
            $swoole_server = $this->setSwooleServer($options);
            self::assertEquals($one['selected'], $server->dispatch($swoole_server, $one['fd'], '', $one['raw']));

        }
    }

    function testDispatchCallbackByWorkerPid()
    {

        $server = static::$commonServer;

        // todo
        // now only one is allowed to test , otherwise : eventLoop has already been created. unable to create swoole_server
        // https://github.com/swoole/swoole-src/blob/master/swoole_async.c
        $data = [
//            ['worker_num' => 5, 'raw' => 'GET /fly?worker-pid=%d HTTP/1.1', 'fd' => 99],
            ['worker_num' => 5, 'raw' => "GET /fly HTTP/1.1\nWorker-Pid: %d", 'fd' => 99],
        ];


        foreach ($data as $one) {

            $this->resetConfigAndDispatcher();

            $options = array_merge(self::$default, ['dispatch_by_query' => true, 'worker_num' => $one['worker_num'], 'compile' => false]);

            $server->config($options);

            $dispatcher = $server->getDispatcher();
            /**
             *  stop run this statement: static::$workerIds->del($event['workerid']);
             *  then $server->getWorkerIds() can get the data even swoole is shutdown
             */
            $dispatcher->addListener('worker.stopped', function (GenericEvent $event) {
                $event->stopPropagation();
            },9);

            $swoole_server = $server->createSwooleServer();

            $dispatcher->addListener('worker.ready', function (GenericEvent $event) use ($server) {

                $ids = $server->getWorkerIds();
                if (count($ids) === $server->getConfig('worker_num')) {

                    $server->getSwooleServer()->shutdown();
                }
            });


            $server->start();

            $ids = $server->getWorkerIds();

            $selected_worker_id = random_int(0,$one['worker_num']-1);

            $a_line = $ids->get($selected_worker_id);

            self::assertEquals($selected_worker_id, $a_line['id']);

            self::assertEquals($selected_worker_id,
                $server->dispatch($swoole_server,
                    $one['fd'], '',
                    sprintf($one['raw'], $a_line['pid'])
                )
            );

//            exit();
        }


    }

}