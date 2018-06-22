<?php
namespace Larasaas\DistributedTransaction;

use Larasaas\DistributedTransaction\Models\Transaction;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class BaseRpcClient implements DTS
{
    protected $connection;
    protected $channel;
    protected $callback_queue;
    protected $response;
    protected $corr_id;
    protected $message;

    public function __construct()
    {
        $this->connection = new AMQPStreamConnection(
            config('dts.rpc.mq_host','localhost'),
            config('dts.rpc.mq_port',5672),
            config('dts.rpc.mq_user','guest'),
            config('dts.rpc.mq_password','guest')
        );
        $this->channel = $this->connection->channel();

        list($this->callback_queue, ,) = $this->channel->queue_declare(
            "",
            false,
            false,
            true,
            false
        );

//
        $this->channel->basic_consume(
            $this->callback_queue,
            config('dts.rpc.consume.consumer_tag',''),      // '', //消费者标记
            config('dts.rpc.consume.no_local',false),          // false
            config('dts.rpc.consume.no_ack',false),            // false
            config('dts.rpc.consume.exclusive',false),         // false
            config('dts.rpc.consume.nowait',false),            // false
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

//    public function call($n)
//    {
//        $this->response = null;
//        $this->corr_id = uniqid();
//
//        $msg = new AMQPMessage(
//            (string) $n,
//            array(
//                'correlation_id' => $this->corr_id,
//                'reply_to' => $this->callback_queue
//            )
//        );
//        $this->channel->basic_publish(
//            $msg,
//            config('rpc.client.public.exchange',''),
//            config('rpc.client.public.routing_key','rpc_queue')
//        );
//        while (!$this->response) {
//            $this->channel->wait();
//        }
//        return intval($this->response);
//
//    }
    public function call()
    {
        $this->send_message();
    }

    public function save_message($trans_data){
        $message_data = $trans_data['message'] ?? '';
        if(empty($message_data)){
            return ['error'=>1,'message'=>'事务消息体错误','data'=>$message_data];
        }
//        $trans_data['message']=$message_data;
        $message = Transaction::create($trans_data);
        $this->message = $message;
        if(! $message){
            return ['error'=>1,'message'=>'保存事务消息失败','data'=>$message_data];
        }

        return ['error'=>0,'message'=>'ok','data'=>$message];
    }


    private function getApiToken()
    {
        $request=request();
        if(isset($request->api_token) && !empty($request->api_token)){
            return $request->api_token;
        }
        $authorization=$request->header('authorization');
        if( $authorization && substr($authorization,0,7)==='Bearer '){
            return substr($authorization,7);
        }
        return false;
    }



    public function send_message($prop = [])
    {

        $message=$this->message;
        $this->response = null;
        $this->corr_id = $message->id;

        $producer = $message->producer; //生产者，需要验证
        $routing_key=$message->consumer;   //services.setting.brandcreator;消费者
        $api_token=$this->getApiToken();
        $msg_body=$message->message;
        $trans_id=$message->id;


        $msg = new AMQPMessage(json_encode([
            'api_token'=>$api_token,
            'producer'=>$producer,
            'msg_body'=>$msg_body,
            'trans_id'=>$trans_id
        ]),[
            'correlation_id' => $this->corr_id,
            'reply_to' => $this->callback_queue
        ]);
        $this->channel->basic_publish(
            $msg,
            config('dts.rpc.public.exchange','rpc_exchange'),
//            config('rpc.client.public.routing_key','rpc_queue')
            $routing_key
        );

        $message->status=1;
        $return = $message->save();


        if(! $return) {
//            Log::error('发送事务消息失败：');
//            Log::error($message->toArray());
            return ['error'=>1,'message'=>'发送事务消息失败','data'=>$message];
        }

        while (!$this->response) {
            $this->channel->wait();
        }

        print_r($this->response);


//        $routing_key ='myrouter';
//        $this->channel->basic_publish($msg, config('transaction.receive.exchange.name','topic_message'), $routing_key,false);
//
//        $this->channel->wait_for_pending_acks();  //不用等返回
//        $this->channel->wait_for_pending_acks_returns();    //如果路由错误，则会触发set_return_listener.



    }
}

//
//$fibonacci_rpc = new FibonacciRpcClient();
//$response = $fibonacci_rpc->call(30);
//echo ' [.] Got ', $response, "\n";
