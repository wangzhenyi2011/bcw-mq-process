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

$jobs = new Bcw\Swoole\Jobs($config);

if (!$jobs->queue) {
    die("queue object is null\n");
}

//jobs的topic需要在配置文件里面定义，并且一次性注册进去
$topics = $jobs->queue->getTopics();
var_dump($topics);
$separateTableConf = [
                ['tableName'=>'222','tableNum'=>10,'modField'=>'id'],
                ['tableName'=>'111','tableNum'=>10,'modField'=>'uid'],
];

foreach ($separateTableConf as $conf) {
    $topicName = 'PayOrder';
    $uuid      = $jobs->queue->uuid();
    $data      = [
        'uuid'   => $uuid, 'jobName' => $topicName, 'jobAction' => 'table',
        'params' => [
            'table' => $conf['tableName'], 'key' => $conf['modField'],
        ],
    ];
    $jobs->queue->push($topicName, $data);
    echo $uuid . " ok\n";

    $result = $jobs->queue->pop($topicName);
    var_dump($result);
}
