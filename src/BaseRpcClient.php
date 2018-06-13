<?php
namespace larasaas\DistributedTransaction;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class BaseRpcClient
{
    protected $connection;
    protected $channel;
    protected $callback_queue;
    protected $response;
    protected $corr_id;

    public function __construct()
    {
        $this->connection = new AMQPStreamConnection(
            config('rpc.mq_host','localhost'),
            config('rpc.mq_port',5672),
            config('rpc.mq_user','guest'),
            config('rpc.mq_password','guest')
        );
        $this->channel = $this->connection->channel();
        list($this->callback_queue, ,) = $this->channel->queue_declare(
            config('rpc.client.queue.queue',''),       // "",
            config('rpc.client.queue.passive',false),     // false,  //被动
            config('rpc.client.queue.durable',false),     // false,  //可持久化
            config('rpc.client.queue.exclusive',true),   // true,   //专用的; 高级的; 排外的; 单独的
            config('rpc.client.queue.auto_delete',false)  // false   //自动删除
        );
//
        $this->channel->basic_consume(
            $this->callback_queue,
            config('rpc.client.consume.consumer_tag',''),      // '', //消费者标记
            config('rpc.client.consume.no_local',false),          // false
            config('rpc.client.consume.no_ack',false),            // false
            config('rpc.client.consume.exclusive',false),         // false
            config('rpc.client.consume.nowait',false),            // false
            array(
                $this,
                'onResponse'
            )
        );
    }

    public function onResponse($rep)
    {
        if ($rep->get('correlation_id') == $this->corr_id) {
            $this->response = $rep->body;
        }
    }

    public function call($n)
    {
        $this->response = null;
        $this->corr_id = uniqid();

        $msg = new AMQPMessage(
            (string) $n,
            array(
                'correlation_id' => $this->corr_id,
                'reply_to' => $this->callback_queue
            )
        );
        $this->channel->basic_publish(
            $msg,
            config('rpc.client.public.exchange',''),
            config('rpc.client.public.routing_key','rpc_queue')
        );
        while (!$this->response) {
            $this->channel->wait();
        }
        return intval($this->response);
    }
}

//
//$fibonacci_rpc = new FibonacciRpcClient();
//$response = $fibonacci_rpc->call(30);
//echo ' [.] Got ', $response, "\n";
