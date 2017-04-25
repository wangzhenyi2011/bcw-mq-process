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
    'framework' => 'yii2',
    'rootPath'=> '/app/www/show.foodiecup.com',
    'config' => [
           'id' => 'console',
           'basePath' => dirname(__DIR__),
           'controllerNamespace' => 'console\controllers',
           'components' => [
            'db' => [
            ]
      ]
    ],
];

$process = new Bcw\Swoole\Process();
$process->start($config);
