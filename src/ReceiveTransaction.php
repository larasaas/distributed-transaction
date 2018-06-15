<?php
namespace larasaas\DistributedTransaction;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Helper\Protocol\Wait091;

//define('AMQP_PROTOCOL','0.8');
define('AMQP_DEBUG', true);
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
        $this->channel = $this->connection->channel();

        $this->queue = $this->channel->queue_declare(
            config('transaction.receive.queue.queue',"trans"),      // ""
            config('transaction.receive.queue.passive',false),    // false
            config('transaction.receive.queue.durable',true),    // false
            config('transaction.receive.queue.exclusive',false),  // true
            config('transaction.receive.queue.auto_delete',false) // false
        );
        $this->exchange = $this->channel->exchange_declare(
            config('transaction.receive.exchange.name','topic_message'),        // 'topic_message'
            config('transaction.receive.exchange.type','topic'),        // 'topic'
            config('transaction.receive.exchange.passive',false),     // false
            config('transaction.receive.exchange.durable',true),     // false
            config('transaction.receive.exchange.auto_delete',false)  // false

        );
//        $this->channel->queue_bind(config('transaction.receive.queue.queue',"trans"), config('transaction.receive.exchange.name','topic_message'),'myrouter');
//        $this->channel = $channel;

    }

    public function queue_bind($binding_key)
    {
        list($queue_name, ,) =$this->queue;
        $this->queue_name=$queue_name;
        $this->channel->queue_bind($this->queue_name, config('transaction.receive.exchange.name','topic_message'), $binding_key);

    }


    public function run()
    {
        echo " [*] Waiting for transaction. To exit press CTRL+C\n";

//        $this->channel->basic_qos(null, 10000, null);
        $process_message = function ($msg) {

            $consumer=$msg->delivery_info['routing_key'];
            $delivery_tag = $msg->delivery_info['delivery_tag'];
            echo ' ['.getmypid().'] ', $consumer,":",$msg->body, "\n";

//            $msg->delivery_info['channel']->basic_nack($msg->delivery_info['delivery_tag'],false,false);    //删除消息
            $msg->body=trim($msg->body,'"');
//            echo ' ['.getmypid().'] ', $msg->delivery_info['routing_key'],":",$msg->body, "\n";
////            print_r($msg->delivery_info['delivery_tag']);die();
//
            if($msg->body == 'good'){
                $msg->delivery_info['channel']->basic_ack($delivery_tag,true);           //确认收到消息
            }else{
//                sleep(10);
//                $msg->delivery_info['channel']->basic_nack($delivery_tag,false,true);     //重新放入队列
                $msg->delivery_info['channel']->basic_nack($msg->delivery_info['delivery_tag'],false,false);    //删除消息
//                $msg->delivery_info['channel']->basic_reject($msg->delivery_info['delivery_tag'],false);
            }
//
//
//            if ($msg->body === 'quit') {
//                $msg->delivery_info['channel']->basic_cancel($msg->delivery_info['consumer_tag']);
//            }


        };

//        $this->channel->basic_qos(null, 10000, null);       //限制消费的次数的多少
        $this->channel->basic_consume(
            $this->queue_name,
            config('transaction.receive.consume.consumer_tag',""), // ""
            config('transaction.receive.consume.no_local',false),     // false
            config('transaction.receive.consume.no_ack',false),       // true       //这里采用ack应答确认模式，必须启用ack.
            config('transaction.receive.consume.exclusive',false),    // false
            config('transaction.receive.consume.nowait',false),       // false
            $process_message
        );

//        $waitHelper = new Wait091();

        while (count($this->channel->callbacks)) {
            //这里是主进程，需根据命令参数，开启对应的topic（每个topic代表一个最小单位的服务，主topic用*号）。
            //根据callback回调函数来处理子方法。

            //如果消费成功，则发送ack确认消息
            //如果消费失败，则发送nack重新放入队列或删除消息

            //阻塞
            $this->channel->wait();
//            $this->channel->wait(null, false, 0);
//            $this->channel->wait(array($waitHelper->get_wait('basic.ack')));

            //非阻塞
//            $read = array($this->connection->getSocket()); // add here other sockets that you need to attend
//            $write = null;
//            $except = null;
//            if (false === ($changeStreamsCount = stream_select($read, $write, $except, 60))) {
//                /* Error handling */
//            } elseif ($changeStreamsCount > 0 || $this->channel->hasPendingMethods()) {
//                $this->channel->wait();
//            }
        }
    }


    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
    }
}