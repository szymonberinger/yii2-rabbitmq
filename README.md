# yii2-rabbitmq
Yii2 Rabbimmq

## Installation
add
```
"sansusan/yii2-rabbitmq": "*"
```
to the require section of your composer.json file.

## Used PhpAmqpLib
add component in config file
```
'rabbit' => [
    'class' => 'sansusan\rabbitmq\RabbitPhpAmqpLib',
    'host' => '127.0.0.1',
    'amqpPort' => 5666,
    'login' => 'user_login',
    'pass' => 'user_pass',
    'vhost' => '/',
    'receiveQueueName' => 'receive_queue',
    'receiveWaitTimeout' => '10',
    'sendQueueName' => 'send_queue',
    'prefetchCount' => 1,
    'heartbeat' => 30,
    'read_write_timeout' => 60,
    'connection_timeout' => 3,
    'useSSL' => true,
    'caFilePath' => '/var/www/html/rabbitmq/cacert.pem',
    'autoACK' => false,
 ],
```

add console command
```
use PhpAmqpLib\Message\AMQPMessage;
use sansusan\rabbitmq\RabbitPhpAmqpLib;

class TestController extends \yii\console\Controller
{
    private function receiveCallback(AMQPMessage $message)
    {
        $body = $message->body;
        $rabbit = \Yii::$app->rabbit;
        $rabbit->sendACK($message);
    }

    public function actionRead()
    {
        $rabbit = \Yii::$app->rabbit;
        $rabbit->receive([$this, 'receiveCallback']);
    }

    public function actionSend($message)
    {
        $rabbit = \Yii::$app->rabbit;

        // add custom heeaders
        $headers = [
            'file_name' => 'test.xml',
        ];
        $rabbit->send($message, $headers);
    }
}
```

## Used PECL amqp lib
add component in config file
```
'rabbit_pecl' => [
    'class' => 'sansusan\rabbitmq\RabbitPeclAmqp',
    'host' => '127.0.0.1',
    'amqpPort' => 5666,
    'login' => 'user_login',
    'pass' => 'user_pass',
    'vhost' => '/',
    'receiveQueueName' => 'receive_queue',
    'receiveWaitTimeout' => '10',
    'sendQueueName' => 'send_queue',
    'prefetchCount' => 1,
    'heartbeat' => 30,
    'read_write_timeout' => 60,
    'connection_timeout' => 3,
    'useSSL' => true,
    'caFilePath' => '/var/www/html/rabbitmq/cacert.pem',
    'autoACK' => false,
 ],
```

add console command
```
use AMQPChannel;
use AMQPConnection;
use AMQPExchange;
use AMQPQueue;

class TestController extends \yii\console\Controller
{
    public function actionReadpecl()
        {
            $rabbit = \Yii::$app->rabbit_pecl;
            $message = $rabbit->receive();
            if ($message) {
                echo $message->getBody();
            }
        }

        public function actionSendpecl()
        {
            $rabbit = \Yii::$app->rabbit_pecl;
            $message = 'Hello from PECL amqp';
            $headers = [
                'some_name' => 'some_value',
            ];
            if ($rabbit->send($message, $headers))
                echo "Success\n";
        }
}
```