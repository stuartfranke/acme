<?php
if (!isset($_SESSION)) {
    session_start();
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

use Acme\Tracker\Objects\Helper;

$helper = new Helper();