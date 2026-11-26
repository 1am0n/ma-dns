<?php

namespace App;

class Device {
    public function __construct(private Packet $packet) {
        Console::print('DEVICE', 'Booted');
    }

    public function resolveHostname(string $domain) {
        // simulate dns query data
        $transactionId = random_int(0, 65535);

        $header = pack('n6', $transactionId, 0x0100, 1, 0, 0, 0);
        $query = $header . $this->packet->encodeName($domain) . pack('n2', 1, 1);

        // send the query to the recursive resolver (isp)
        // we somehow automatically know it's ip =]
        Request::udp('127.0.0.1:1053', $query);

        // the browser then needs to wait for the hostname to be RESOLVED!
        return $this->waitForResolvedHostname();
    }

    public function waitForResolvedHostname() {
        $socket = stream_socket_server('udp://127.0.0.5:1053', $errorCode, $errorMessage, STREAM_SERVER_BIND);

        if ($socket === false) {
            throw new \RuntimeException("DEVICE: $errorMessage", $errorCode);
        }

        Console::print('DEVICE', 'Listening on 127.0.0.5:1053 and waiting for hostname ip');

        while (true) {
            $packet = stream_socket_recvfrom($socket, 4096, 0, $peer);

            if ($packet === false) {
                continue;
            }

            // process result
            Console::print('DEVICE', 'Got request, decoding packet');

            $packet = $this->packet->read($packet);

            if ($packet['header']['answers'] == 1) {
                $resolvedHostname = $packet['answers'][0]['data'];

                break;
            }
        }

        return $resolvedHostname;
    }
}