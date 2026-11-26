<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Console;
use App\Network\Auth;
use App\Network\ISP;
use App\Network\Root;
use App\Network\TLD;
use App\Packet;

$packet = new Packet();

// prepare servers
$servers = [
    'auth' => new Auth($packet),
    'tld' => new TLD($packet),
    'root' => new Root($packet),
    'isp' => new ISP($packet),
];
$sockets = [];

foreach ($servers as $name => $server) {
    $sockets[$name] = $server->prepare();
}

// wait for requests
while (true) {
    $read = $sockets;
    $write = $except = null;

    if (stream_select($read, $write, $except, null) === false) {
        throw new \RuntimeException('Failed to wait for packets');
    }

    foreach ($read as $role => $socket) {
        $unreadPacket = stream_socket_recvfrom($socket, 4096, 0, $peer);

        if ($unreadPacket === false) {
            continue;
        }

        Console::print(strtoupper($role), 'Got request, decoding sent packet');

        $servers[$role]->process($packet->read($unreadPacket));
    }
}
