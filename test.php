<?php
require_once __DIR__.'/vendor/autoload.php';
$client = new WebSocket\Client("ws://127.0.0.1:9501");
$client->text("Hello WebSocket.org!");
echo $client->receive();
$client->close();