<?php

$context = stream_context_create();

stream_context_set_option($context, 'socket', 'so_reuseport', 1);

for ($i = 0; $i < 2; $i++) {

    $pid = pcntl_fork();

    if ($pid == 0) {

        while (true) {

            $socket = stream_socket_server("tcp://0.0.0.0:8000", $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context);

            while ($conn = @stream_socket_accept($socket, 5)) {

                fwrite($conn, getmypid() . ':时间:' . date('Y-m-d H:i:s') . "\n");

                fclose($conn);

            }

            fclose($socket);

        }

    }

}

while (1) {

    $pid = pcntl_wait($status);

    var_dump($pid, $status);

    sleep(1);

}