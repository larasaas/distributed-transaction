<?php
namespace larasaas\DistributedTransaction;

use PhpAmqpLib\Connection\AMQPStreamConnection;

class ReceiveTransaction
{

    protected $connection;
    protected $channel;
    protected $queue;
    protected $exchange;
    protected $queue_name;
    public function __construct()
    {
        $this->connection = new AMQPStreamConnection(
            config('transaction.mq_host','localhost'),      // 'localhost',
            config('transaction.mq_port',5672),      //  5672,
            config('transaction.mq_user','guest'),      // 'guest',
            config('transaction.mq_password','guest')   // 'guest'
        );
        $channel = $this->connection->channel();
        $this->exchange = $channel->exchange_declare(
            config('transaction.receive.exchange.name','topic_message'),        // 'topic_message'
            config('transaction.receive.exchange.type','topic'),        // 'topic'
            config('transaction.receive.exchange.passive',false),     // false
            config('transaction.receive.exchange.durable',false),     // false
            config('transaction.receive.exchange.auto_delete',false)  // false

        );
        $this->queue = $channel->queue_declare(
            config('transaction.receive.queue.queue',""),      // ""
            config('transaction.receive.queue.passive',false),    // false
            config('transaction.receive.queue.durable',false),    // false
            config('transaction.receive.queue.exclusive',true),  // true
            config('transaction.receive.queue.auto_delete',false) // false
        );
        $this->channel = $channel;
    }

    public function add($binding_key)
    {
        list($queue_name, ,) =$this->queue;
        $this->queue_name=$queue_name;
        $this->channel->queue_bind($queue_name, config('transaction.receive.exchange.name','topic_message'), $binding_key);

    }

    public function run()
    {
        echo " [*] Waiting for transaction. To exit press CTRL+C\n";

        $callback = function ($msg) {
            echo ' [x] ', $msg->delivery_info['routing_key'], ':', $msg->body, "\n";
        };

        $this->channel->basic_consume(
            $this->queue_name,
            config('transaction.receive.consume.consumer_tag',""), // ""
            config('transaction.receive.consume.no_local',false),     // false
            config('transaction.receive.consume.no_ack',true),       // true
            config('transaction.receive.consume.exclusive',false),    // false
            config('transaction.receive.consume.nowait',false),       // false
            $callback
        );

        while (count($this->channel->callbacks)) {
            $this->channel->$this->channel();
        }
    }

    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
    }
}