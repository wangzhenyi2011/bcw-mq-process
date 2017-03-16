<?php
date_default_timezone_set('Asia/Shanghai');

require __DIR__ . '/../../../../vendor/autoload.php';

$config = [
     'queue'   => [
         'type'     => 'rabbitmq',
         'host'     => 'localhost',
         'vhost'    => '/',
         'login'    => 'guest',
         'password' => 'guest',
         'port'     => '5672',
     ],
    'logPath' => __DIR__ . '/../log',
    'topics'  => ['separate'],

];

$jobs = new Bcw\Swoole\Jobs($config);

if (!$jobs->queue) {
    die("queue object is null\n");
}

//jobs的topic需要在配置文件里面定义，并且一次性注册进去
$topics = $jobs->queue->getTopics();
var_dump($topics);
$separateTableConf = [
                ['tableName'=>'basic_user','tableNum'=>10,'modField'=>'id'],
                ['tableName'=>'basic_user_auth','tableNum'=>10,'modField'=>'uid'],
                ['tableName'=>'basic_user_coupon','tableNum'=>10,'modField'=>'uid'],
                ['tableName'=>'basic_virtual_card','tableNum'=>10,'modField'=>'couid'],
                ['tableName'=>'basic_mark','tableNum'=>10,'modField'=>'uid'],
                ['tableName'=>'basic_activity_prize','tableNum'=>10,'modField'=>'uid'],
                ['tableName'=>'basic_cart','tableNum'=>10,'modField'=>'uid'],
];

foreach ($separateTableConf as $conf) {
    $topicName = 'separate';
    $uuid      = $jobs->queue->uuid();
    $data      = [
        'uuid'   => $uuid, 'jobName' => $topicName, 'jobAction' => 'table',
        'params' => [
            'table' => $conf['tableName'], 'key' => $conf['modField'],
        ],
    ];
    $jobs->queue->push($topicName, $data);
    echo $uuid . " ok\n";
}
