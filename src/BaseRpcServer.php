<?php
namespace larasaas\DistributedTransaction;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class BaseRpcServer
{
    protected $connection;
    protected $channel;

    public function __construct()
    {
        $connection = new AMQPStreamConnection(
            config('rpc.mq_host','localhost'),
            config('rpc.mq_port','5672'),
            config('rpc.mq_user','guest'),
            config('rpc.mq_password','guest')
        );
        $channel = $connection->channel();

        $channel->queue_declare(
            config('rpc.server.queue.queue','rpc_queue'),
            config('rpc.server.queue.passive',false),
            config('rpc.server.queue.durable',false),
            config('rpc.server.queue.exclusive',false),
            config('rpc.server.queue.auto_delete'),false);
        $this->channel = $channel;
    }

    public function run()
    {
        echo " [x] Awaiting RPC requests\n";
        $callback = function ($req) {
            $n = intval($req->body);
            echo ' [.] fib(', $n, ")\n";

            $msg = new AMQPMessage(
                (string) fib($n),
                array('correlation_id' => $req->get('correlation_id'))
            );

            $req->delivery_info['channel']->basic_publish(
                $msg,
                '',
                $req->get('reply_to')
            );
            $req->delivery_info['channel']->basic_ack(
                $req->delivery_info['delivery_tag']
            );
        };

        $this->channel->basic_qos(
            config('rpc.server.qos.perfetch_size',null),
            config('rpc.server.qos.perfetch_count',1),
            config('rpc.server.qos.a_global',null)
        );
        $this->channel->basic_consume(
            config('rpc.server.consume.queue','rpc_queue'),
            config('rpc.server.consume.consumer_tag',''),
            config('rpc.server.consume.no_local',false),
            config('rpc.server.consume.no_ack',false),
            config('rpc.server.consume.exclusive',false),
            config('rpc.server.consume.nowait',false),
            $callback
        );

        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }

    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();

    }

    //后面的是用于远程调用的方法
    function fib($n)
    {
        if ($n == 0) {
            return 0;
        }
        if ($n == 1) {
            return 1;
        }
        return $this->fib($n-1) + $this->fib($n-2);
    }
}


//调用例子，具体业务需要继承本类
//$baseRpcserver=new BaseRpcServer();
//$baseRpcserver->run();







