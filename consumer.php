<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/vendor/autoload.php';

define("RABBITMQ_HOST", "snake.rmq2.cloudamqp.com");
define("RABBITMQ_PORT", 5672);
define("RABBITMQ_USERNAME", "xeyxstza");
define("RABBITMQ_PASSWORD", "FtiV_dXZIjH7K9wZw7Y9XDq0Doig_z4q");
define("RABBITMQ_QUEUE_NAME", "task_queue");
define("CLOUDAMQP_URL", "amqps://xeyxstza:FtiV_dXZIjH7K9wZw7Y9XDq0Doig_z4q@snake.rmq2.cloudamqp.com/xeyxstza");

$url_str = CLOUDAMQP_URL
    or exit("CLOUDAMQP_URL not set");
$url = parse_url($url_str);

$vhost = ($url['path'] == '/' || !isset($url['path'])) ? '/' : substr($url['path'], 1);
if ($url['scheme'] === "amqps") {
    $port = isset($port) ? $port : 5671;
    $ssl_opts = array(
        'capath' => '/etc/ssl/certs'
    );
    $connection = new \PhpAmqpLib\Connection\AMQPSSLConnection($url['host'], $port, $url['user'], $url['pass'], $vhost, $ssl_opts);
} else {
    $port = isset($port) ? $port : 5672;
    $connection = new \PhpAmqpLib\Connection\AMQPStreamConnection($url['host'], $port, $url['user'], $url['pass'], $vhost);
}
$channel = $connection->channel();

$channel->queue_declare(RABBITMQ_QUEUE_NAME, false, true, false, false);

echo " [*] Waiting for messages. To exit press CTRL+C\n";

$callback = function ($msg) {
    global $channel;
    $data = json_decode($msg->body, $assocForm = true);
    echo "<pre>";
    print_r($data);

    //print(base64_decode($data['body']));
    //echo ' [x] Received ', $msg->body, "\n";
    //sleep(substr_count($msg->body, '.'));
    // echo " [x] Done\n";
    $channel->queue_declare(
        $queue = 'requeue_data',
        $passive = false,
        $durable = true,
        $exclusive = false,
        $auto_delete = false,
        $nowait = false,
        $arguments = null,
        $ticket = null
    );

    $msg2 = new \PhpAmqpLib\Message\AMQPMessage(
        json_encode($data, JSON_UNESCAPED_SLASHES),
        array('delivery_mode' => 2) # make message persistent
    );

    $channel->basic_publish($msg2, '', 'requeue_data');

    $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
};

$channel->basic_qos(null, 1, null); // not give more then one msg to a consumer at a time.
$channel->basic_consume(RABBITMQ_QUEUE_NAME, '', false, false, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}
echo "\nHere is end";
$channel->close();
$connection->close();
