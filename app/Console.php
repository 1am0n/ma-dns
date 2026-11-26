<?php

namespace App;

class Console {
    public static function print($prefix, $message) {
        echo "[\033[31m" . $prefix . "\033[0m] " . $message . PHP_EOL;
    }
}