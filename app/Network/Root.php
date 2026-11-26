<?php

namespace App\Network;

use App\Console;
use App\Packet;
use App\Request;

class Root {
    public function __construct(private Packet $packet) {}

    public function prepare() {
        $socket = stream_socket_server('udp://127.0.0.2:1053', $errorCode, $errorMessage, STREAM_SERVER_BIND);

        if ($socket === false) {
            throw new \RuntimeException("Root: $errorMessage", $errorCode);
        }

        Console::print('ROOT', 'Listening on 127.0.0.2:1053');

        return $socket;
    }

    public function process($packet) {
        $flags = 0x8000 | ($packet['header']['flags'] & 0x0100);
        $header = pack('n6', $packet['header']['transactionId'], $flags, 1, 0, 1, 1);
        $question = $this->packet->encodeName($packet['domain']) . pack('n2', $packet['question']['type'], $packet['question']['class']);
        $nsData = $this->packet->encodeName('ns.com');
        $authority = $this->packet->encodeName('com') . pack('nnNn', 2, 1, 300, strlen($nsData)) . $nsData;
        $ipData = inet_pton('127.0.0.3'); // tld server ip
        $additional = $this->packet->encodeName('ns.com') . pack('nnNn', 1, 1, 300, strlen($ipData)) . $ipData;

        $response = $header . $question . $authority . $additional;

        Console::print('ROOT', 'Sending response to ISP');

        // back to isp
        Request::udp('127.0.0.1:1053', $response);
    }
}