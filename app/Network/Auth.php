<?php

namespace App\Network;

use App\Console;
use App\Request;
use App\Packet;

class Auth {
    public function __construct(private Packet $packet) {}

    public function prepare() {
        $socket = stream_socket_server('udp://127.0.0.4:1053', $errorCode, $errorMessage, STREAM_SERVER_BIND);

        if ($socket === false) {
            throw new \RuntimeException("AUTH: $errorMessage", $errorCode);
        }

        Console::print('AUTH', 'Listening on 127.0.0.4:1053');

        return $socket;
    }

    public function process($packet) {
        $flags = 0x8000 | 0x0400 | ($packet['header']['flags'] & 0x0100);
        $header = pack('n6', $packet['header']['transactionId'], $flags, 1, 1, 0, 0);
        $question = $this->packet->encodeName($packet['domain']) . pack('n2', $packet['question']['type'], $packet['question']['class']);
        $ipData = inet_pton('192.0.2.10');
        $answer = $this->packet->encodeName($packet['domain']) . pack('nnNn', 1, 1, 300, strlen($ipData)) . $ipData;

        $response = $header . $question . $answer;

        Console::print('AUTH', 'Sending response to ISP');

        // back to isp
        Request::udp('127.0.0.1:1053', $response);
    }
}