<?php
namespace Larasaas\DistributedTransaction;

use Larasaas\DistributedTransaction\Models\TransApplied;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Helper\Protocol\Wait091;

//define('AMQP_PROTOCOL','0.8');
//define('AMQP_DEBUG', true);
class ConfirmReceive
{

    protected $connection;
    protected $channel;
    protected $queue;
    protected $exchange;
    protected $queue_name;
    public function __construct()
    {
        $this->connection = new AMQPStreamConnection(
            config('dts.confirm.mq_host','localhost'),      // 'localhost',
            config('dts.confirm.mq_port',5672),      //  5672,
            config('dts.confirm.mq_user','guest'),      // 'guest',
            config('dts.confirm.mq_password','guest')   // 'guest'
        );
        $this->channel = $this->connection->channel();

        $this->queue = $this->channel->queue_declare(
            config('dts.confirm.queue.queue',"confirm_queue"),      // ""
            config('dts.confirm.queue.passive',false),    // false
            config('dts.confirm.queue.durable',true),    // false
            config('dts.confirm.queue.exclusive',false),  // true
            config('dts.confirm.queue.auto_delete',false) // false
        );
        $this->exchange = $this->channel->exchange_declare(
            config('dts.confirm.exchange.name','confirm_exchange'),        // 'topic_message'
            config('dts.confirm.exchange.type','topic'),        // 'topic'
            config('dts.confirm.exchange.passive',false),     // false
            config('dts.confirm.exchange.durable',true),     // false
            config('dts.confirm.exchange.auto_delete',false)  // false

        );
//        $this->channel->queue_bind(config('transaction.receive.queue.queue',"trans"), config('transaction.receive.exchange.name','topic_message'),'myrouter');
//        $this->channel = $channel;

    }

    public function queue_bind($binding_key)
    {
        list($queue_name, ,) =$this->queue;
        $this->queue_name=$queue_name;
        $this->channel->queue_bind($this->queue_name, config('dts.confirm.exchange.name','confirm_queue'), $binding_key);
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
                $msg->delivery_info['channel']->basic_ack($delivery_tag,false);           //确认收到消息
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
            config('dts.confirm.consume.consumer_tag',""), // ""
            config('dts.confirm.consume.no_local',false),     // false
            config('dts.confirm.consume.no_ack',false),       // true       //这里采用ack应答确认模式，必须启用ack.
            config('dts.confirm.consume.exclusive',false),    // false
            config('dts.confirm.consume.nowait',false),       // false
            $process_message
        );

//        $waitHelper = new Wait091();

        while (count($this->channel->callbacks)) {
            //阻塞
            $this->channel->wait();
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


    /**
     * 添加消费记录
     * @param array $app_data
     * @return array
     */
    public function addApplied($app_data=[])
    {
        $find=TransApplied::where(['trans_id'=>$app_data['trans_id'],'consumer'=>$app_data['consumer'],'producer'=>$app_data['producer']])->first();
        //查找是否被成功执行过，确保消费的信息不过界。
        if(empty($find)){
            $message = TransApplied::create($app_data);
            if(! $message){
                return ['error'=>1,'message'=>'保存事务消息失败'];
            }
            return ['error'=>0,'message'=>'ok','data'=>$message];
        }else{
            //容错
            return ['error'=>0,'message'=>'ok','data'=>$find];
            //return ['error'=>1,'message'=>'保存事务消息失败(重复)'];
        }
    }

    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
    }
}