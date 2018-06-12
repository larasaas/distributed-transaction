<?php
/**=======================================
 * @author ikodota <ikodota@gmail.com>
 * @copyright (c) 2016, ikodota
 * @datetime 2016-12-01 15:54
 *=======================================*/
//1。利用发布/订阅 模式，实现分布式事务、（另外：集中配置、单登录、日志收集也可以实现）
//2。RPC 远程调用服务，记录调用具体明细，用于回滚。


return [
    'transaction'=>[    //分布式事务
        'mq_host' => 'localhost',
        'mq_port' => 5672,
        'mq_user' => 'guest',
        'mq_password' => 'guest',
        'receive'=> [
            'exchange' => [
                'name'=>'topic_message',
                'type'=>'topic',    //topic,direct,fanout
                'passive' => false ,
                'durable' => false ,
                'auto_delete' => false,
            ],
            'queue' => [
                'queue' => '',
                'passive' => false ,
                'durable' => false ,
                'exclusive'=>true,
                'auto_delete' => false,
            ],
            'consume' => [
                'consumer_tag'=>'',
                'no_local'=>false,
                'no_ack'=>true,
                'exclusive'=>false,
                'nowait'=>false
            ]
        ]
    ],
    'rpc' => [
        'mq_host' => 'localhost',
        'mq_port' => 5672,
        'mq_user' => 'guest',
        'mq_password' => 'guest',
        'server'=> [
            'queue' => [
                'queue' => 'rpc_queue',
                'passive' => false ,    //被动
                'durable' => false ,    //可持久化
                'exclusive'=> false,    //专用的; 高级的; 排外的; 单独的
                'auto_delete' => false, //自动删除
            ],
            'consume' => [
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
            ]
        ],
        'client'=>[
            'queue' => [
                'queue' => 'rpc_queue',
                'passive' => false ,
                'durable' => false ,
                'exclusive'=> true,
                'auto_delete' => false,
            ],
            'consume' => [
                'consumer_tag'=>'',
                'no_local'=>false,
                'no_ack'=>false,
                'exclusive'=>false,
                'nowait'=>false
            ],
        ]

    ],
//    'configCenter'=> [     //集中配置
//        'mq_host' => 'localhost',
//        'mq_port' => 5672,
//        'mq_user' => 'guest',
//        'mq_password' => 'guest',
//        'receive'=> [
//            'exchange' => [
//                'name'=>'topic_config',
//                'type'=>'topic',    //topic,direct,fanout
//                'passive' => false ,
//                'durable' => false ,
//                'auto_delete' => false,
//            ],
//            'queue' => [
//                'queue' => '',
//                'passive' => false ,
//                'durable' => false ,
//                'exclusive'=>true,
//                'auto_delete' => false,
//            ],
//            'consume' => [
//                'consumer_tag'=>'',
//                'no_local'=>false,
//                'no_ack'=>true,
//                'exclusive'=>false,
//                'nowait'=>false
//            ]
//        ]
//    ],
//    'kick' => [     //踢人服务，单登录，踢掉之前登录用户。(应该采用web socket，例如MQTT协议)
//        'mq_host' => 'localhost',
//        'mq_port' => 5672,
//        'mq_user' => 'guest',
//        'mq_password' => 'guest',
//        'receive'=> [
//            'exchange' => [
//                'name'=>'topic_user',
//                'type'=>'topic',    //topic,direct,fanout
//                'passive' => false ,
//                'durable' => false ,
//                'auto_delete' => false,
//            ],
//            'queue' => [
//                'queue' => '',
//                'passive' => false ,
//                'durable' => false ,
//                'exclusive'=>true,
//                'auto_delete' => false,
//            ],
//            'consume' => [
//                'consumer_tag'=>'',
//                'no_local'=>false,
//                'no_ack'=>true,
//                'exclusive'=>false,
//                'nowait'=>false
//            ]
//        ]
//    ],
//    'logsCollect'=>[        //日志收集器
//        'mq_host' => 'localhost',
//        'mq_port' => 5672,
//        'mq_user' => 'guest',
//        'mq_password' => 'guest',
//        'receive'=> [
//            'exchange' => [
//                'name'=>'topic_logs',
//                'type'=>'topic',    //topic,direct,fanout
//                'passive' => false ,
//                'durable' => false ,
//                'auto_delete' => false,
//            ],
//            'queue' => [
//                'queue' => '',
//                'passive' => false ,
//                'durable' => false ,
//                'exclusive'=>true,
//                'auto_delete' => false,
//            ],
//            'consume' => [
//                'consumer_tag'=>'',
//                'no_local'=>false,
//                'no_ack'=>true,
//                'exclusive'=>false,
//                'nowait'=>false
//            ]
//        ]
//    ]

];
