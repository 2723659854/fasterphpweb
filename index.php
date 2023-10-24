<?php
header("Content-type:text/html;charset=utf-8");
$hosts = array("www.baidu.com:80", "www.sz517.com:80", "tcp://127.0.0.0:80");
$www = array('www.test1.com', 'www.test2.com', 'www.test3.com');
$timeout = 1; $status = array(); $sockets = array();$wid = array();
foreach ($hosts as $id => $host) {
    $s = stream_socket_client(
        $host, $errno, $errstr, $timeout, STREAM_CLIENT_ASYNC_CONNECT);
    if ($s) {
        $sockets[$id] = $s;
        $status[$id] = "in progress";
    } else {
        $status[$id] = "failed, $errno $errstr";
    }
}
while (count($sockets)) {
    $read = $write = $sockets;
    $n = stream_select($read, $write, $except , $timeout);
    if ($n > 0) {
        foreach ($read as $r) {
            $id = array_search($r, $sockets);
            $data = fread($r, 1024);
            if (strlen($data) == 0) {
                if ($status[$id] == "in progress") { $status[$id] = "failed to connect"; }
                fclose($r);  unset($sockets[$id]);
            } else {  $status[$id] .= $data;  }
        }
        foreach ($write as $w) {
            $id = array_search($w, $sockets);
            if(in_array($id, $wid)){ continue;    }else{  $wid[] = $id; }
            fwrite($w, "GET / HTTP/1.0\r\nHost: " . $www[$id] .  "\r\n\r\n");$status[$id] = "waiting for response";
        }
    } else {
        foreach ($sockets as $id => $s) { $status[$id] = "timed out " . $status[$id]; }
        break;
    }
}
foreach ($hosts as $id => $host) { echo "Host: $host\n"; echo "Status: " . $status[$id] . "\n\n"; }