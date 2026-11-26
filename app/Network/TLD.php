<?php

namespace App\Network;

use App\Console;
use App\Request;
use http\Exception\RuntimeException;
use App\Packet;

class TLD {
    public function __construct(private Packet $packet) {}

    public function prepare() {
        $socket = stream_socket_server('udp://127.0.0.3:1053', $errorCode, $errorMessage, STREAM_SERVER_BIND);

        if ($socket === false) {
            throw new RuntimeException("TLD: $errorMessage", $errorCode);
        }

        Console::print('TLD', 'Listening on 127.0.0.3:1053');

        return $socket;
    }

    public function process($packet) {
        $flags = 0x8000 | ($packet['header']['flags'] & 0x0100);
        $header = pack('n6', $packet['header']['transactionId'], $flags, 1, 0, 1, 1);
        $question = $this->packet->encodeName($packet['domain']) . pack('n2', $packet['question']['type'], $packet['question']['class']);
        $nsData = $this->packet->encodeName('ns.example.com');
        $authority = $this->packet->encodeName('example.com') . pack('nnNn', 2, 1, 300, strlen($nsData)) . $nsData;
        $ipData = inet_pton('127.0.0.4'); // auth server ip
        $additional = $this->packet->encodeName('ns.example.com') . pack('nnNn', 1, 1, 300, strlen($ipData)) . $ipData;

        $response = $header . $question . $authority . $additional;

        Console::print('TLD', 'Sending response to ISP');

        // back to isp
        Request::udp('127.0.0.1:1053', $response);
    }
}