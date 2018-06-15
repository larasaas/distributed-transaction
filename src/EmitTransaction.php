<?php
namespace larasaas\DistributedTransaction;

//use Illuminate\Support\Facades\Log;
use larasaas\DistributedTransaction\Models\Transaction;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Helper\Protocol\Wait091;

//define('AMQP_PROTOCOL','0.8');
define('AMQP_DEBUG', true);
class EmitTransaction
{

    protected $connection;
    protected $channel;
    protected $queue;
    protected $exchange;
    public function __construct()
    {
        $this->connection = new AMQPStreamConnection(
            config('transaction.mq_host','localhost'),      // 'localhost',
            config('transaction.mq_port',5672),      //  5672,
            config('transaction.mq_user','guest'),      // 'guest',
            config('transaction.mq_password','guest')   // 'guest'
        );

        $this->channel = $this->connection->channel();

//        $this->queue = $this->channel->queue_declare("trans", false, true, false, false);
        $this->channel->set_ack_handler(
            function (AMQPMessage $message) {
//                echo $message->delivery_info['delivery_tag'];
                echo "(".$message->delivery_info['delivery_tag'].")Message acked with content " . $message->body . PHP_EOL;
            }
        );

        $this->channel->set_nack_handler(
            function (AMQPMessage $message) {
//                echo $message->delivery_info['delivery_tag'];
                echo "(".$message->delivery_info['delivery_tag'].")Message nacked with content " . $message->body . PHP_EOL;
            }
        );

        //当exchange与queue未绑定时候，则会触发。
        $this->channel->set_return_listener(
            function ($replyCode, $replyText, $exchange, $routingKey, AMQPMessage $message) {
//                echo "Message returned with content " . $message->body . PHP_EOL;
                echo "return: ",
                $replyCode, "\n",
                $replyText, "\n",
                $exchange, "\n",
                $routingKey, "\n",
                $message->body, "\n";
            }
        );

//        $this->channel->tx_select();
        $this->channel->confirm_select(false);     //消息确认模式

        $this->exchange = $this->channel->exchange_declare(
            config('transaction.receive.exchange.name','topic_message'),        // 'topic_message'
            config('transaction.receive.exchange.type','topic'),        // 'topic'
            config('transaction.receive.exchange.passive',false),     // false
            config('transaction.receive.exchange.durable',true),     // false
            config('transaction.receive.exchange.auto_delete',false)  // false
        );

    }

    /**
     * 提交前，需要保存事务消息
     * @param array $message_data
     * @return mixed
     */
    public function save_message($trans_data=[])
    {
        $message_data = $trans_data['message'] ?? '';
        if(empty($message_data)){
            return ['error'=>1,'message'=>'事务消息体错误','data'=>$message_data];
        }
        $trans_data['message']=json_encode($message_data);
        $message = Transaction::create($trans_data);
        if(! $message){
            return ['error'=>1,'message'=>'保存事务消息失败','data'=>$message_data];
        }

        return ['error'=>0,'message'=>'ok','data'=>$message];

    }

    /**
     * 本地事务提交成功后，向Message Queue请求发送事务消息
     * @param $message
     */
    public function send_message($message,$props=array(
        'content_type' => 'text/plain',
        'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
    )
    )
    {
        $producer = $message->producer;
        $routing_key=$message->consumer;   //services.setting.brandcreator;消费者
//        $routing_key = config('transaction.receive.exchange.name','topic_message');
        $msgbody=$message->message;
//        echo '版本:'.AMQPChannel::getProtocolVersion();

//        $msg = new AMQPMessage($msgbody,array('content_type' => 'text/plain'));
        $msg = new AMQPMessage($msgbody,$props);
//        $routing_key ='myrouter';
        $this->channel->basic_publish($msg, config('transaction.receive.exchange.name','topic_message'), $routing_key,false);

        $this->channel->wait_for_pending_acks();  //不用等返回
//        $this->channel->wait_for_pending_acks_returns();    //如果路由错误，则会触发set_return_listener.


//        $i=1;
//        while ($i <= 11) {
//            $msg = new AMQPMessage($i++, array('content_type' => 'text/plain'));
//            $this->channel->basic_publish($msg, config('transaction.receive.exchange.name','topic_message'),$routing_key, false);
//        }
//        $this->channel->wait_for_pending_acks_returns();

//        echo ' [x] Sent ', $routing_key, ':', $data, "\n";

        $message->status=1;
        $return = $message->save();
        if(! $return) {
//            Log::error('发送事务消息失败：');
//            Log::error($message->toArray());
            return ['error'=>1,'message'=>'发送事务消息失败','data'=>$message];
        }
        return ['error'=>0,'message'=>'ok','data'=>$message];
    }

    public function get_mq_message()
    {

    }

    /**
     * 本地事务提交失败后，向Message Queue请求取消事务消息
     * @param $message
     *
     */
    public function cancel_message($message)
    {
        $message->status=2;
        $return = $message->save();
        if(! $return) {
//            Log::error('取消事务消息失败：');
//            Log::error($message->toArray());
            return ['error'=>1,'message'=>'取消事务消息失败','data'=>$message];
        }
        return ['error'=>0,'message'=>'ok','data'=>$message];
    }


    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
    }

}