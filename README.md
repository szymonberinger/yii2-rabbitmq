# yii2-rabbitmq
Yii2 Rabbimmq

## Installation
add
```
"sansusan/yii2-rabbitmq": "*"
```
to the require section of your composer.json file.

## Used
add component in config file
```
'rabbit' => [
    'class' => 'sansusan\rabbitmq\Rabbit',
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
use sansusan\rabbitmq\Rabbit;

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