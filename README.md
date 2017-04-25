## Bcw-Swool-process
* 多进程消费

## 多消费者,并行处理
* 这可能是最常遇到的一种场景了. 消息产生之后堆到队列里, 有多个消费者的 worker 来共同处理这些消息, 以并行的方式提高处理效率.
* 这种场景在 Exchange 的类似选择上, 不管是 fanout 或者是 direct 都可以实现. 稍有不同在于, fanout 类型的话, 你在一个 exchange 上就不要乱绑定队列. direct 类型的话, 则是需要每条消息自己处理好 routing_key .

## 描述
* 最先搞过PHP自带的pcntl，发现对于这块写了虽然能实现，但是效果和稳定性不太理想
* 利用swoole_process::daemon守护进程
* 利用swoole的异步信号监听，worker进程退出后会自动拉起
* 利用sswoole_process::wait,回收结束运行的子进程,防止成为僵尸进程
* 子进程过大主动杀死，防止业务代码内存泄漏
* rabbitmq多种的实现（多消费者, 并行处理;一条消息多种处理, 临时队列;发布订阅)
* 支持composer，可以跟任意框架集成


## 示例


```
composer install

//往队列添加job
php test/testJobs.php



## 性能

* 瓶颈: rabbitmq队列存储本身和job执行速度
