<?php

namespace Bcw\Swoole;

use Bcw\Swoole\Queue;

class Rabbitmq extends Queue
{
    private $connection = null;
    private $channel    = null;
    private $exchange   = null;
    private $queue      = null;

    private $attributes = array();

    public function __construct(array $config)
    {
        try {
            $class = class_exists('AMQPConnection', false);
            if ($class) {
                $this->connection = new \AMQPConnection();
                $this->connection->setHost($config['host']);
                $this->connection->setLogin($config['login']);
                $this->connection->setPassword($config['password']);
                $this->connection->setVhost($config['vhost']);
                $this->connection->connect();
            } else {
                die('you need install pecl amqp extension');
            }
            $this->channel = new \AMQPChannel($this->connection);

            $this->exchange = new \AMQPExchange($this->channel);
            $this->queue    = new \AMQPQueue($this->channel);
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
        }
    }

    public function push($key, $value)
    {
        $this->exchange->setArguments(['x-dead-letter-exchange'=>'deadletter ','x-dead-letter-routing-key'=>'deadletter']);
        $this->queue->setName($key);
        $this->queue->setFlags(AMQP_DURABLE);
        $this->queue->declareQueue('deadletter');
        $result = $this->exchange->publish(serialize($value), $key,AMQP_NOPARAM,['message_id'=>$this->uuid(),'user_id'=>'','delivery_mode'=>'2','timestamp'=>time(),'expiration'=>30000]);
        return $result;
    }

    public function pop($key)
    {
        $this->queue->setName($key);
        $this->queue->setFlags(AMQP_DURABLE);
        $this->queue->declareQueue('deadletter');
        $message = $this->queue->get();
        $result  = null;
        if ($message) {
            $result = $message->getBody();
        }

      //  var_dump($this->queue->ack($message->getDeliveryTag()));die();

        return $result ? unserialize($result) : null;
    }

    public function uuid()
    {
        $len     = 20;
        $hashStr = substr(str_shuffle(str_repeat('abcdefghijklmnopqrstuvwxyz0123456789', $len)), 0, $len);

        $uuid = md5(uniqid($hashStr, true) . microtime(true) . mt_rand(0, 1000));
        return $uuid;
    }
}
