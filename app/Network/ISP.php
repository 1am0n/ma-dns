<?php

namespace App\Network;

use App\Console;
use App\Packet;
use App\Request;

class ISP {
    private $rootServers = [];

    public function __construct(private Packet $packet) {
        $this->rootServers[] = '127.0.0.2:1053';
    }

    public function prepare() {
        $socket = stream_socket_server('udp://127.0.0.1:1053', $errorCode, $errorMessage, STREAM_SERVER_BIND);

        if ($socket === false) {
            throw new \RuntimeException("ISP: $errorMessage", $errorCode);
        }

        Console::print('ISP', 'Listening on 127.0.0.1:1053');

        return $socket;
    }

    public function process($packet) {
        if ($packet['header']['answers'] == 1) {
            // got domain ip so we can pass it to browser to load it
            $flags = 0x8000 | 0x0080 | ($packet['header']['flags'] & 0x0100);
            $header = pack('n6', $packet['header']['transactionId'], $flags, 1, 1, 0, 0);
            $question = $this->packet->encodeName($packet['domain']) . pack('n2', $packet['question']['type'], $packet['question']['class']);
            $ipData = inet_pton('192.0.2.10');
            $answer = $this->packet->encodeName($packet['domain']) . pack('nnNn', 1, 1, 300, strlen($ipData)) . $ipData;

            $response = $header . $question . $answer;

            // send result to device
            Request::udp('127.0.0.5:1053', $response);
        } else {
            $isResponse = ($packet['header']['flags'] & 0x8000) !== 0;

            if ($isResponse) {
                // Send request to "correct" server (for now simple to first one)
                Console::print('ISP', 'Going through servers and sending request');

                foreach ($packet['additional'] as $row) {
                    $header = $this->buildHeader();
                    $query = $header . $this->packet->encodeName($packet['domain']) . pack('n2', $packet['question']['type'], $packet['question']['class']);

                    Request::udp($row['data'] . ':1053', $query);

                    break;
                }
            } else {
                $header = $this->buildHeader();
                $query = $header . $this->packet->encodeName($packet['domain']) . pack('n2', $packet['question']['type'], $packet['question']['class']);

                foreach ($this->rootServers as $serverAddress) {
                    Console::print('ISP', 'Contacting with root server');

                    Request::udp($serverAddress, $query);
                }
            }
        }
    }

    private function buildHeader() {
        $upstreamId = random_int(0, 65535);

        return pack('n6', $upstreamId, 0x0000, 1, 0, 0, 0);
    }
}