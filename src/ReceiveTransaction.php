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
        $this->connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
        $channel = $this->connection->channel();
        $this->exchange = $channel->exchange_declare('topic_logs', 'topic', false, false, false);
        $this->queue = $channel->queue_declare("", false, false, true, false);
        $this->channel = $channel;
    }

    public function add($binding_key)
    {
        list($queue_name, ,) =$this->queue;
        $this->queue_name=$queue_name;
        $this->channel->queue_bind($queue_name, 'topic_logs', $binding_key);

    }

    public function run()
    {
        echo " [*] Waiting for transaction. To exit press CTRL+C\n";

        $callback = function ($msg) {
            echo ' [x] ', $msg->delivery_info['routing_key'], ':', $msg->body, "\n";
        };

        $this->channel->basic_consume($this->queue_name, '', false, true, false, false, $callback);

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