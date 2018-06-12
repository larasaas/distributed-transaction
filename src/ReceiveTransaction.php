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
            config('transaction.mq_host'),      // 'localhost',
            config('transaction.mq_port'),      //  5672,
            config('transaction.mq_user'),      // 'guest',
            config('transaction.mq_password')   // 'guest'
        );
        $channel = $this->connection->channel();
        $this->exchange = $channel->exchange_declare(
            config('transaction.receive.exchange.name'),        // 'topic_logs'
            config('transaction.receive.exchange.type'),        // 'topic'
            config('transaction.receive.exchange.passive'),     // false
            config('transaction.receive.exchange.durable'),     // false
            config('transaction.receive.exchange.auto_delete')  // false

        );
        $this->queue = $channel->queue_declare(
            config('transaction.receive.queue.queue'),      // ""
            config('transaction.receive.queue.passive'),    // false
            config('transaction.receive.queue.durable'),    // false
            config('transaction.receive.queue.exclusive'),  // true
            config('transaction.receive.queue.auto_delete') // false
        );
        $this->channel = $channel;
    }

    public function add($binding_key)
    {
        list($queue_name, ,) =$this->queue;
        $this->queue_name=$queue_name;
        $this->channel->queue_bind($queue_name, config('transaction.receive.exchange.name'), $binding_key);

    }

    public function run()
    {
        echo " [*] Waiting for transaction. To exit press CTRL+C\n";

        $callback = function ($msg) {
            echo ' [x] ', $msg->delivery_info['routing_key'], ':', $msg->body, "\n";
        };

        $this->channel->basic_consume(
            $this->queue_name,
            config('transaction.receive.consume.consumer_tag'), // ""
            config('transaction.receive.consume.no_local'),     // false
            config('transaction.receive.consume.no_ack'),       // true
            config('transaction.receive.consume.exclusive'),    // false
            config('transaction.receive.consume.nowait'),       // false
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