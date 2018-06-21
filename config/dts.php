<?php
/**=======================================
 * @author ikodota <ikodota@gmail.com>
 * @copyright (c) 2016, ikodota
 * @datetime 2016-12-01 15:54
 *=======================================*/
//1。利用发布/订阅 模式，实现分布式事务、（另外：集中配置、单登录、日志收集也可以实现）
//2。RPC 远程调用服务，记录调用具体明细，用于回滚。


return [
    '2pc'=>[    //两阶段提交型
        //2pc涉及到2个阶段，3个操作：
        //阶段1：“准备提交”。事务协调者向所有参与者发起prepare，所有参与者回答yes/no。
        //阶段2：“正式提交”。如果所有参与者都回答yes，则向所有参与者发起commit；否则，向所有参与者发起rollback。
        //因此，要实现2pc，所有参与者，都得实现3个接口：prepare/commit/rollback。
        //阶段一：投票请求阶段,阶段二： 提交执行阶段
    ],
    '3pc'=>[    //三阶段提交型
        //3pc是2pc的改进版本，主要是给协调者和参与者都引入了超时机制，同时在第一阶段和第二阶段过程中加入了一个准备阶段，保障了数据的一致性。
        //阶段一：canCommit,阶段二：preCommit,阶段三：doCommit
        //总结：3pc对比2pc来说增强了数据一致性， 同时引入了双方超时机制，减小了阻塞。但是缺点依旧存在，主要是无法避免网络分区（网络分区：在网络不好的情况下，高延迟被区分成失败）问题，因为3pc采用了失败-停止的模式,所以一旦网络延迟高就会失败。
    ],
    'tcc'=>[    //try-confirm-cancel型(柔性事务)
        //支付宝提出了TCC。2PC通常都是在跨库的DB层面，而TCC本质就是一个应用层面的2PC。
        //同样，TCC中，每个参与者需要3个操作：Try/Confirm/Cancel，也是2个阶段。
        //阶段1：”资源预留/资源检查“，也就是事务协调者调用所有参与者的Try操作
        //阶段2：“一起提交”。如果所有的Try成功，一起执行Confirm。否则，所有的执行Cancel.
    ],
    'confirm'=>[    //异步确保型（最终一致性）
        'mq_host' => 'localhost',
        'mq_port' => 5672,
        'mq_user' => 'guest',
        'mq_password' => 'guest',
        'exchange' => [
            'name'=>'confirm_exchange',
            'type'=>'topic',    //topic,direct,fanout
            'passive' => false ,
            'durable' => true ,
            'auto_delete' => false,
        ],
        'queue' => [
            'queue' => 'confirm_queue',
            'passive' => false ,
            'durable' => true ,
            'exclusive'=>false,
            'auto_delete' => false,
        ],
        'consume' => [
            'consumer_tag'=>'',
            'no_local'=>false,
            'no_ack'=>false,
            'exclusive'=>false,
            'nowait'=>false
        ]
    ],
    'delay'=>[  //最大努力通知型(阶梯延迟通知)
        'mq_host' => 'localhost',
        'mq_port' => 5672,
        'mq_user' => 'guest',
        'mq_password' => 'guest',
        'exchange' => [
            'name'=>'delay_exchange',
            'type'=>'topic',
            'passive' => false ,
            'durable' => true ,
            'auto_delete' => false,
        ],
        'queue' => [
            'queue' => 'delay_queue',
            'passive' => false ,
            'durable' => true ,
            'exclusive'=>false,
            'auto_delete' => false,
        ],
        'consume' => [
            'consumer_tag'=>'',
            'no_local'=>false,
            'no_ack'=>false,
            'exclusive'=>false,
            'nowait'=>false
        ]
    ],
    'rpc' => [  //远程过程调用
        'mq_host' => 'localhost',
        'mq_port' => 5672,
        'mq_user' => 'guest',
        'mq_password' => 'guest',
        'exchange' => [
            'name'=>'rpc_exchange',
            'type'=>'topic',
            'passive' => false ,
            'durable' => true ,
            'auto_delete' => false,
        ],
        'queue' => [
            'queue' => 'rpc_queue',
            'passive' => false ,    //被动
            'durable' => true ,    //可持久化
            'exclusive'=> false,    //专用的; 高级的; 排外的; 单独的
            'auto_delete' => false, //自动删除
        ],
        'consume' => [
            'queue' => 'rpc_queue',
            'consumer_tag'=>'',     //消费者标记
            'no_local'=>false,
            'no_ack'=>false,
            'exclusive'=>false,
            'nowait'=>false
        ],
        'qos'=>[
            'perfetch_size'=>null,
            'perfetch_count'=>1,
            'a_global'=>null
        ],
        'publish'=>[
            'exchange'=>'',
            'routing_key'=>'rpc_queue'
        ]

    ]
];
