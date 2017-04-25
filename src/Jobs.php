<?php

namespace Bcw\Swoole;

use Bcw\Swoole\Logs;
use Bcw\Swoole\Rabbitmq;

class Jobs
{
    const MAX_POP     = 50; //单个topic每次最多取多少次
    const MAX_REQUEST = 10000; //每个子进程while循环里面最多循坏次数，防止内存泄漏

    public $logger = null;
    public $queue  = null;


    public function __construct($config)
    {
        $this->config = $config;
        $this->logger = new Logs($config['logPath']);
        $this->getQueue($config['queue']);
        $this->queue && $this->queue->addTopics($config['topics']);
    }

    public function run()
    {
        //循环次数计数
        $req = 0;
        while (true) {
            $topics = $this->queue->getTopics();

            if ($topics) {
                foreach ($topics as $key => $jobName) {
                    //  echo $jobName;continue;

                    if (!isset($jobName['Arguments'])) {
                        $jobName['Arguments'] = array();
                    }

                    for ($i = 0; $i < self::MAX_POP; $i++) {
                        $result = $this->queue->pop($key, $jobName['Arguments']);

                    //    var_dump($result);die();
                        $data = unserialize($result['Body']);
                        if (!empty($data) && isset($data['jobAction'])) {
                            // $this->logger->log(print_r([$jobName, $jobAction], true), 'info');
                            $jobAction = $data['jobAction'];
                            $exitCode = $this->loadFramework($key, $jobAction, $data);
                            //var_dump($exitCode);continue;
                            if ($exitCode == 1) {
                                $this->queue->getqueue()->ack($result['DeliveryTag']);
                            } elseif ($exitCode == 2) {
                                $Arguments = [
                                    'x-dead-letter-exchange'=>'ordertransfer',
                                    'x-dead-letter-routing-key'=>'ordertransfer',
                                    'x-message-ttl'=>180000
                                  ];
                                $this->queue->push('WaitOrder', $data, 'waitorder', $Arguments);
                                $this->queue->getqueue()->ack($result['DeliveryTag']);
                            //      $this->queue->getqueue()->nack($result['DeliveryTag']);
                            } elseif (strlen($exitCode) == 4) {
                                $this->queue->getqueue()->ack($result['DeliveryTag']);
                            }
                        } else {
                            //  $this->logger->log($jobName . " no work to do!", 'info');
                            break;
                        }
                    }
                }
            } else {
                $this->logger->log("All no work to do!", 'info');
            }
            $this->logger->flush();
            sleep(1);
            $req++;

            if ($req >= self::MAX_REQUEST) {
                echo "达到最大循环次数，让子进程退出，主进程会再次拉起子进程\n";
                break;
            }
        }
    }



    public function getQueue($config)
    {
        if (isset($config['type']) && $config['type'] == 'redis') {
            $this->queue = new Redis($config);
        } elseif (isset($config['type']) && $config['type'] == 'rabbitmq') {
            $this->queue = new Rabbitmq($config);
        } else {
            echo "you must add queue config\n";
        }

        return $this->queue;
    }

    private function loadFramework($jobName, $jobAction, $data)
    {
        if (isset($this->config['framework']) && $this->config['framework'] == 'yii2') {
            $exitCode = $this->loadYii2Console($jobName, $jobAction, $data);
        } else {
            $exitCode = $this->loadTest($jobName, $jobAction, $data);
        }

        return $exitCode;

    //    $this->logger->log("uuid: " . $data['uuid'] . " one job has been done, exitCode: " . $exitCode, 'trace', 'jobs');
    }


    private function loadTest($jobName, $jobAction, $data)
    {
        $exitCode = 0;
        $jobName  = "\Bcw\Job\\" . ucfirst($jobName);
        if (method_exists($jobName, $jobAction)) {
            try {
                $job      = new $jobName();
                $exitCode = $job->$jobAction($data);
            } catch (Exception $e) {
                $this->logger->log($e->getMessage(), 'error');
            }
        } else {
            $this->logger->log($jobAction . " action not find!", 'warning');
        }
    }


    private function loadYii2Console($jobName, $jobAction, $data)
    {
        try {
            require_once($this->config['rootPath'] . '/yiisoft/yii2/Yii.php');

            // var_dump($this->config['rootPath']);die();
            \Yii::setAlias('@console', $this->config['consolePath']);

            $app = new \yii\console\Application($this->config['config']);

            $route       = $this->toUnderScore($jobName) . '/' . $jobAction;

            $params      = [$data['params']['uid'],$data['params']['order_id']];
            $exitCode    = 0;

            $exitCode = $app->runAction($route, $params);
            $this->logger->log("exitCode: " . $exitCode.'route: '.$route.'params: '.print_r([$data['params']], true), 'trace', 'jobs');
          //  echo 'route'.$route.'exitCode'.$exitCode;
        } catch (Exception $e) {
            $this->logger->log($e->getMessage(), 'error');
        }
        unset($application);
        return $exitCode;
    }

    private function toUnderScore($str)
    {
        $str = preg_replace_callback('/([A-Z]{1})/', function ($matches) {
            return '-'.strtolower($matches[0]);
        }, $str);
        return   substr($str, 1);
    }
}
