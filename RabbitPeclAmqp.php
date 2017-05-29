<?php

namespace sansusan\rabbitmq;

use AMQPChannel;
use AMQPConnection;
use AMQPEnvelope;
use AMQPExchange;
use AMQPQueue;

/**
 * Class Rabbit
 * @package sansusan\rabbitmq
 */
class RabbitPeclAmqp
{
    /**
     * @var string Server IP
     */
    public $host = '127.0.0.1';

    /**
     * @var int AMQP port
     */
    public $amqpPort = 5672;

    /**
     * @var string Login
     */
    public $login = 'login';

    /**
     * @var string Password
     */
    public $pass = 'pass';

    /**
     * @var string Virtual host
     */
    public $vhost = '/';

    /**
     * @var string Receive message queue name
     */
    public $receiveQueueName = 'receive';

    /**
     * @var int Receive message wait timeout
     */
    public $receiveWaitTimeout = 0;

    /**
     * @var bool Auto acknowledgment
     */
    public $autoACK = false;

    /**
     * @var string Send message queue name
     */
    public $sendQueueName = 'send';

    /**
     * @var int Allow you to limit the number of unacknowledged messages on a channel (or connection) when consuming
     */
    public $prefetchCount = 1;

    /**
     * @var int Heartbeat
     */
    public $heartbeat = 30;

    /**
     * @var int Read write timeout
     */
    public $read_write_timeout = 60;

    /**
     * @var int Connection timeout
     */
    public $connection_timeout = 3;

    /**
     * @var bool Use SSL connection
     */
    public $useSSL = false;

    /**
     * @var string Path certificate file .pem
     */
    public $caFilePath = '';


    private $connection = null;
    private $queue = null;
    private $exchange = null;


    /**
     * Init connection
     * @return AMQPConnection
     */
    private function initConnection()
    {
        if (empty($this->connection)) {

            ini_set('amqp.cacert', $this->caFilePath);
            ini_set('amqp.cert', $this->caFilePath);
            ini_set('amqp.heartbeat', $this->heartbeat);
//        ini_set('amqp.key', '');
            ini_set('amqp.verify', 'false');

            $connection = new AMQPConnection();
            $connection->setHost($this->host);
            $connection->setPort($this->amqpPort);
            $connection->setLogin($this->login);
            $connection->setPassword($this->pass);
            $connection->setVhost($this->vhost);
            $connection->setReadTimeout($this->read_write_timeout);
            $connection->setWriteTimeout($this->read_write_timeout);
            $this->connection = $connection;
        }
        return $this->connection;
    }


    /**
     * @return bool
     */
    private function connect()
    {
        $connection = $this->initConnection();
        if (!$connection->isConnected())
            return $connection->connect();
        else
            return true;
    }


    /**
     * Get queue
     * @return AMQPQueue
     */
    private function initQueue()
    {
        $connection = $this->initConnection();
        if (empty($this->queue)) {
            $channel = new AMQPChannel($connection);
            $channel->setPrefetchCount((int)$this->prefetchCount);
            $q = new AMQPQueue($channel);
            $q->setFlags(AMQP_DURABLE);
            $q->setName($this->receiveQueueName);
            $q->declareQueue();
            $this->queue = $q;
        }
        return $this->queue;
    }


    private function initExchange()
    {
        $connection = $this->initConnection();
        if (empty($this->exchange)) {
            $channel = new AMQPChannel($connection);
            $ex = new AMQPExchange($channel);
            $ex->setName($this->sendQueueName);
            $ex->setFlags(AMQP_DURABLE);
            $ex->setType(AMQP_EX_TYPE_FANOUT);
            $ex->declareExchange();
            $this->exchange = $ex;
        }
        return $this->exchange;
    }


    /**
     * Receive next message
     * @return AMQPEnvelope|boolean
     */
    public function receive()
    {
        $q = $this->initQueue();
        if (!$this->connect())
            return false;
        return $q->get($this->autoACK ? AMQP_AUTOACK : AMQP_NOPARAM);
    }


    /**
     * Send ACK message
     *
     * @param AMQPEnvelope $message
     * @return bool
     */
    public function sendACK(AMQPEnvelope $message)
    {
        $q = $this->initQueue();
        return $q->ack($message->getDeliveryTag());
    }


    /**
     * Send message
     *
     * @param $message
     * @param array $headers
     * @return bool
     */
    public function send($message, $headers = [])
    {
        $ex = $this->initExchange();
        return $ex->publish($message, $this->sendQueueName, AMQP_NOPARAM, [
            'delivery_mode' => 2,
            'headers' => $headers
        ]);
    }
}