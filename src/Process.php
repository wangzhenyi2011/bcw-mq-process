<?php
namespace Bcw\Swoole;

use Bcw\Swoole\Jobs;

class Process
{
    private $reserveProcess;
    private $workers;
    private $workNum = 5;
    private $config  = [];

    const PROCESS_NAME_LOG = ': bcwphp process';

    public function start($config)
    {
        \Swoole\Process::daemon();
        $this->config = $config;
        for ($i = 0; $i < $this->workNum; $i++) {
            $this->reserveQueue($i);
        }
        $this->registSignal($this->workers);
        //\Swoole\Process::wait();
    }

    //独立进程消费队列
    public function reserveQueue($workNum)
    {
        $self = $this;
        $ppid = getmypid();
        //file_put_contents($this->config['logPath'] . '/master.pid.log', $ppid . "\n");
        $this->setProcessName("job master " . $ppid . $self::PROCESS_NAME_LOG);
        $reserveProcess = new \Swoole\Process(function () use ($self, $workNum) {
            //设置进程名字
            $this->setProcessName("job " . $workNum . $self::PROCESS_NAME_LOG);
            try {
                $job = new Jobs($self->config);
                $job->run();
            } catch (Exception $e) {
                echo $e->getMessage();
            }
            echo "reserve process " . $workNum . " is working ...\n";
        });
        $pid                 = $reserveProcess->start();
        $this->workers[$pid] = $reserveProcess;
        echo "reserve start...\n";
    }

    //监控子进程
    public function registSignal($workers)
    {
        \Swoole\Process::signal(SIGTERM, function ($signo) {
            $this->exitMaster("收到退出信号,退出主进程");
        });
        \Swoole\Process::signal(SIGCHLD, function ($signo) use (&$workers) {
            while (true) {
                $ret = \Swoole\Process::wait(false);
                if ($ret) {
                    $pid           = $ret['pid'];
                    $child_process = $workers[$pid];
                    //unset($workers[$pid]);
                    echo "Worker Exit, kill_signal={$ret['signal']} PID=" . $pid . PHP_EOL;
                    $new_pid           = $child_process->start();
                    $workers[$new_pid] = $child_process;
                    unset($workers[$pid]);
                } else {
                    break;
                }
            }
        });
    }


    public function AutoMessage($content, $mobile)
    {
        $cdkey = "8SDK-EMY-6699-RJURK";
        $password = "562528";
        $curl = curl_init();
        $content = urlencode("【百草味】".$content);
        $url = "http://hprpt2.eucp.b2m.cn:8080/sdkproxy/sendsms.action";
        $url.="?cdkey={$cdkey}&password={$password}&phone={$mobile}&message={$content}&addserial=bcw";
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($curl);
        curl_close($curl);
    }

    private function exitMaster()
    {
        @unlink($this->config['logPath'] . '/master.pid.log');
        $this->AutoMessage('主进程退出','18916627173');
        $this->log("Time: " . microtime(true) . "主进程退出" . "\n");
        exit();
    }

    private function setProcessName($name)
    {
        //mac os不支持进程重命名
        if (function_exists("swoole_set_process_name") && PHP_OS != 'Darwin') {
            swoole_set_process_name($name);
        }
    }

    private function log($txt)
    {
        file_put_contents($this->config['logPath'] . '/worker.log', $txt . "\n", FILE_APPEND);
    }
}
