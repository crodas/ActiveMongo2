<?php

require __DIR__ . "/../vendor/autoload.php";

$conf = new \ActiveMongo2\Configuration(__DIR__ . "/tmp/foo.php");
$conf
    ->addModelPath(__DIR__ . '/docs')
    ->development();

$mongo = new MongoClient;
$zconn = new \ActiveMongo2\Connection($conf, $mongo, 'activemongo2_tests');

$zconn->worker();
