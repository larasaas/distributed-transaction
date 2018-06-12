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
        $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
        $channel = $connection->channel();

        $channel->queue_declare('rpc_queue', false, false, false, false);
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

        $this->channel->basic_qos(null, 1, null);
        $this->channel->basic_consume('rpc_queue', '', false, false, false, false, $callback);

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







