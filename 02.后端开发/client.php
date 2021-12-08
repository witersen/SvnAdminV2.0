<?php
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) or die("error:" . socket_strerror(socket_last_error()));
$msg = "---yes yes---";
$len = strlen($msg);
$server = socket_connect($socket, '127.0.0.1', 7777);
socket_write($socket, $msg);
$msg = socket_read($socket, 8192);
print($msg);
socket_close($socket);
