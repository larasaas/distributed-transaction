<?php
namespace Larasaas\DistributedTransaction;

use Larasaas\DistributedTransaction\Models\TransApplied;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class BaseRpcServer
{
    protected $connection;
    protected $channel;
    protected $exchange;

    public function __construct()
    {
        $connection = new AMQPStreamConnection(
            config('dts.rpc.mq_host','localhost'),
            config('dts.rpc.mq_port','5672'),
            config('dts.rpc.mq_user','guest'),
            config('dts.rpc.mq_password','guest')
        );
        $channel = $connection->channel();

        $channel->queue_declare(
            config('dts.rpc.queue.queue','rpc_queue'),
            config('dts.rpc.queue.passive',false),
            config('dts.rpc.queue.durable',false),
            config('dts.rpc.queue.exclusive',false),
            config('dts.rpc.queue.auto_delete'),false);
        $this->channel = $channel;

        $this->exchange = $this->channel->exchange_declare(
            config('dts.rpc.exchange.name','rpc_exchange'),        // 'topic_message'
            config('dts.rpc.exchange.type','topic'),        // 'topic'
            config('dts.rpc.exchange.passive',true),     // false
            config('dts.rpc.exchange.durable',false),     // false
            config('dts.rpc.exchange.auto_delete',false)  // false

        );
    }

    public function queue_bind($binding_key)
    {
//        list($queue_name, ,) =$this->queue;
//        $this->queue_name=$queue_name;
        $this->channel->queue_bind(config('rpc.server.queue.queue','rpc_queue'), config('transaction.receive.exchange.name','rpc_exchange'), $binding_key);
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

        $this->channel->basic_qos(
            config('dts.rpc.qos.perfetch_size',null),
            config('dts.rpc.qos.perfetch_count',1),
            config('dts.rpc.qos.a_global',null)
        );
        $this->channel->basic_consume(
            config('dts.rpc.consume.queue','rpc_queue'),
            config('dts.rpc.consume.consumer_tag',''),
            config('dts.rpc.consume.no_local',false),
            config('dts.rpc.consume.no_ack',false),
            config('dts.rpc.consume.exclusive',false),
            config('dts.rpc.consume.nowait',false),
            $callback
        );

        while (count($this->channel->callbacks)) {
            $this->channel->wait();
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
//        $this->channel->close();
//        $this->connection->close();

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







