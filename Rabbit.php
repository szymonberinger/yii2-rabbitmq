<?php
namespace sansusan\rabbitmq;

/**
 * Class Rabbit
 * @package sansusan\rabbitmq
 */
class Rabbit
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
     * @var string Send message queue name
     */
    public $sendQueueName = 'send';

    /**
     * @var int Allow you to limit the number of unacknowledged messages on a channel (or connection) when consuming
     */
    public $prefetchCount = 1;

    /**
     * @var bool Use SSL connection
     */
    public $useSSL = false;

    /**
     * @var string Path certificate file .pem
     */
    public $caFilePath = '';


    public function receive()
    {
        if ($this->useSSL) {
            $ssl_options = [
                'cafile' => $this->caFilePath,
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ];
        }
    }


    public function send($message)
    {

    }
}