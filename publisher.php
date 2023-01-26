<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/vendor/autoload.php';

define("RABBITMQ_HOST", "snake.rmq2.cloudamqp.com");
define("RABBITMQ_PORT", 5672);
define("RABBITMQ_USERNAME", "XXXX");
define("RABBITMQ_PASSWORD", "XXXX_z4q");
define("RABBITMQ_QUEUE_NAME", "task_queue");
define("CLOUDAMQP_URL", "amqps://XXXX/xeyxstza");

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

# Create the queue if it does not already exist.
$channel->queue_declare(
    $queue = RABBITMQ_QUEUE_NAME,
    $passive = false,
    $durable = true,
    $exclusive = false,
    $auto_delete = false,
    $nowait = false,
    $arguments = null,
    $ticket = null
);

$job_id = 0;
while (true) {
    $jobArray = array(
        'id' => $job_id++,
        'working' => 'atif',
        'task' => 'sleep',
        'sleep_period' => rand(0, 9)
    );

    $msg = new \PhpAmqpLib\Message\AMQPMessage(
        json_encode($jobArray, JSON_UNESCAPED_SLASHES),
        array('delivery_mode' => 2) # make message persistent
    );

    $channel->basic_publish($msg, '', RABBITMQ_QUEUE_NAME);
    if ($job_id == 3) break;
}
echo 'done...';
