<?php
namespace larasaas\DistributedTransaction;

//use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

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
        $channel = $this->connection->channel();
        $this->exchange = $channel->exchange_declare(
            config('transaction.receive.exchange.name','topic_message'),        // 'topic_message'
            config('transaction.receive.exchange.type','topic'),        // 'topic'
            config('transaction.receive.exchange.passive',false),     // false
            config('transaction.receive.exchange.durable',false),     // false
            config('transaction.receive.exchange.auto_delete',false)  // false
        );
//        $this->queue = $channel->queue_declare("", false, false, true, false);
        $this->channel = $channel;

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
            return ['error'=>1,'message'=>'事务消息体错误'];
        }
        $trans_data['message']=json_encode($message_data);
        $message = \App\Models\Transaction::create($trans_data);
        if(! $message){
            return ['error'=>1,'message'=>'保存事务消息失败'];
        }

        return ['error'=>0,'data'=>$message];

    }

    /**
     * 本地事务提交成功后，向Message Queue请求发送事务消息
     * @param $message
     */
    public function send_message($message)
    {
        $routing_key=$message->consumer;   //services.setting.brandcreator;
        $data=$message->body;

        $msg = new AMQPMessage($data);
        $this->channel->basic_publish($msg, config('transaction.receive.exchange.name','topic_message'), $routing_key);

//        echo ' [x] Sent ', $routing_key, ':', $data, "\n";

        $message->status=1;
        $return = $message->save();
        if(! $return) {
//            Log::error('发送事务消息失败：');
//            Log::error($message->toArray());
            return ['error'=>1,'message'=>'发送事务消息失败'];
        }
        return ['error'=>0,'data'=>$message];
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
            return ['error'=>1,'message'=>'取消事务消息失败'];
        }
        return ['error'=>0,'data'=>$message];
    }


   public function __destruct()
   {
       $this->channel->close();
       $this->connection->close();
   }

}