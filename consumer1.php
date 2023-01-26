<?php

require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->queue_declare('newsletter_queue_testing', false, true, false, false);

echo " [*] Waiting for messages. To exit press CTRL+C\n";

$callback = function ($msg) {
    $data = json_decode($msg->body, $assocForm = true);
    //echo"<pre>"; print_r($data);
    
    //print(base64_decode($data['body']));
    //echo ' [x] Received ', $msg->body, "\n";
    //sleep(substr_count($msg->body, '.'));
   // echo " [x] Done\n";
    $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
    //exit;
};

$channel->basic_qos(null, 1, null); // not give more then one msg to a consumer at a time.
$channel->basic_consume('newsletter_queue_testing', '', false, false, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}
echo "\nHere is end";
$channel->close();
$connection->close();
