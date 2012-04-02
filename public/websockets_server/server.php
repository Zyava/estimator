<?php
// prevent the server from timing out
set_time_limit(0);
error_reporting(-1);


// include the web sockets server script (the server is started at the far bottom of this file)
require 'class.PHPWebSocket.php';
require '../classes/Loader.php';
Loader::registerAutoload();

// start the server
$server = new PHPWebSocket();
$dispatcher = Dispatcher::getInstance();
$dispatcher->setServer($server);
$server->bind('message', array($dispatcher, 'wsOnMessage'));
$server->bind('open', array($dispatcher, 'wsOnOpen'));
$server->bind('close', array($dispatcher, 'wsOnClose'));

// for other computers to connect, you will probably need to change this to your LAN IP or external IP,
// alternatively use: gethostbyaddr(gethostbyname($_SERVER['SERVER_NAME']))
$server->wsStartServer('127.0.0.1', 9300);
