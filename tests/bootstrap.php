<?php

require __DIR__ . "/../vendor/autoload.php";

foreach (glob(__DIR__ . "/docs/*.php") as $php) {
    require $php;
}

function getConnection()
{
    $conf = new \ActiveMongo2\Configuration("/tmp/foo.php");
    $conf
        ->addModelPath(__DIR__ . '/docs')
        ->development();

    $mongo = new MongoClient;
    $conn  = new \ActiveMongo2\Connection($conf, $mongo, 'activemongo2_tests');
    $conn->registerNamespace("ActiveMongo2\\Tests\\Document\\{{collection}}Document");

    return $conn;
}

getConnection()->dropDatabase();
