<?php
date_default_timezone_set('Asia/Shanghai');
require __DIR__ . '/../../../../vendor/autoload.php';

$config = [
     'queue'   => [
         'type'     => 'rabbitmq',
         'host'     => 'localhost',
         'vhost'    => '/',
         'login'    => 'wangzhenyi',
         'password' => '123456',
         'port'     => '5672',
     ],
    'logPath' => __DIR__ . '/../log',
    'topics'  => ['deadletter'],

];

$jobs = new Bcw\Swoole\Rabbitmq($config['queue']);


$uid = '22';
$order_id = '3335';
$data = ['jobAction'=>'index','params'=>['uid'=>$uid,'order_id'=>$order_id]];

$Arguments = [
'x-dead-letter-exchange'=>'deadletter',
'x-dead-letter-routing-key'=>'deadletter',
'x-message-ttl'=>30000,
];
$jobs->push('PayOrder', $data, 'orderpay', $Arguments);
