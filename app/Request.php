<?php

namespace App;

class Request {
    public static function udp($address, $data) {
        $socket = stream_socket_client('udp://' . $address, $errorCode, $errorMessage, 3);

        if ($socket === false) {
            throw new \RuntimeException($errorMessage, $errorCode);
        }

        fwrite($socket, $data);
        fclose($socket);
    }
}