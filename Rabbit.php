<?php
namespace sansusan\rabbitmq;

use PhpAmqpLib\Connection\AMQPSSLConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

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


    /**
     * Init connection
     * @return AMQPSSLConnection
     */
    private function initConnection()
    {
        $ssl_options = [];
        $options = [
            'heartbeat' => $this->heartbeat,
            'read_write_timeout' => $this->read_write_timeout,
            'connection_timeout' => $this->connection_timeout,
        ];

        if ($this->useSSL) {
            $ssl_options = [
                'cafile' => $this->caFilePath,
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ];
        }

        return new AMQPSSLConnection($this->host, $this->amqpPort, $this->login, $this->pass, $this->vhost, $ssl_options, $options);
    }


    /**
     * Receive message
     *
     * @param null $callback
     */
    public function receive($callback = null)
    {
        $connection = $this->initConnection();
        $channel = $connection->channel();


        try {
            $channel->queue_declare(
                $this->receiveQueueName,    #queue
                false,              #passive
                true,               #durable, make sure that RabbitMQ will never lose our queue if a crash occurs
                false,              #exclusive - queues may only be accessed by the current connection
                false               #auto delete - the queue is deleted when all consumers have finished using it
            );

            /**
             * don't dispatch a new message to a worker until it has processed and
             * acknowledged the previous one. Instead, it will dispatch it to the
             * next worker that is not still busy.
             */
            $channel->basic_qos(
                null,   #prefetch size - prefetch window size in octets, null meaning "no specific limit"
                (int)$this->prefetchCount,   #prefetch count - prefetch window in terms of whole messages
                null    #global - global=null to mean that the QoS settings should apply per-consumer, global=true to mean that the QoS settings should apply per-channel
            );

            $channel->basic_consume(
                $this->receiveQueueName,#queue
                '',                     #consumer tag - Identifier for the consumer, valid within the current channel. just string
                false,                  #no local - TRUE: the server will not send messages to the connection that published them
                $this->autoACK,         #no ack, false - acks turned on, true - off.  send a proper acknowledgment from the worker, once we're done with a task
                false,                  #exclusive - queues may only be accessed by the current connection
                false,                  #no wait - TRUE: the server will not respond to the method. The client should not wait for a reply method
                $callback               #callback
            );

            while (count($channel->callbacks)) {
                echo "Waiting for incoming messages", "\n";
                $channel->wait(null, false, (int)$this->receiveWaitTimeout);
            }
        } finally {
            $channel->close();
            $connection->close();
        }
    }


    /**
     * Send ACK message
     * @param AMQPMessage $message
     */
    public function sendACK(AMQPMessage $message)
    {
        /**
         * If a consumer dies without sending an acknowledgement the AMQP broker
         * will redeliver it to another consumer or, if none are available at the
         * time, the broker will wait until at least one consumer is registered
         * for the same queue before attempting redelivery
         */
        if (!$this->autoACK) {
            $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
        }
    }


    /**
     * Send message
     *
     * @param string $message
     * @param array $headers
     */
    public function send($message, $headers = [])
    {
        $connection = $this->initConnection();
        $channel = $connection->channel();
        try {
            $channel->queue_declare(
                $this->sendQueueName, #queue - Queue names may be up to 255 bytes of UTF-8 characters
                false,              #passive - can use this to check whether an exchange exists without modifying the server state
                true,               #durable, make sure that RabbitMQ will never lose our queue if a crash occurs - the queue will survive a broker restart
                false,              #exclusive - used by only one connection and the queue will be deleted when that connection closes
                false               #auto delete - queue is deleted when last consumer unsubscribes
            );

            $msg = new AMQPMessage(
                $message,
                [
                    'delivery_mode' => 2,       # make message persistent, so it is not lost if server crashes or quits
                ]
            );

            $app_headers = new AMQPTable($headers);
            $msg->set('application_headers', $app_headers);

            $channel->basic_publish(
                $msg,               #message
                $this->sendQueueName,  #exchange
                $this->sendQueueName  #routing key (queue)
            );

        } finally {
            $channel->close();
            $connection->close();
        }
    }
}