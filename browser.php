<?php

// what happens when we try to access $website in a browser?
// we can find out by simulating couple of things

require_once __DIR__ . '/vendor/autoload.php';

use App\Browser;
use App\Device;
use App\Packet;

$packet = new Packet();
$website = 'best-food-ever.com';

// boot device
$device = new Device($packet);

// open browser
$browser = new Browser($device);

// browser wants to visit best-food-ever.com and get the best recipe for his it's human but it doesn't have the website ip address
// can it find it? yes, it can! but some strange magic happens along th e way, so let's dig deeper and undertand how it works
$browser->load($website);
