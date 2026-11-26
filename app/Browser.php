<?php

namespace App;

class Browser {
    public function __construct(private Device $device) {
        Console::print('BROWSER', 'Opened');
    }

    public function load(string $url) {
        $resolvedHostname = $this->device->resolveHostname($url);

        Console::print('BROWSER', 'Hostname resolved, loading ' . $resolvedHostname . ' (' . $url . ')');
    }
}