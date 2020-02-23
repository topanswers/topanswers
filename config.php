<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . "/helper.php";

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$config = [
    "devServer" => env('DEV_SERVER', '127.0.0.1')
];
