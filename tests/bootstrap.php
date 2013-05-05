<?php

require __DIR__ . "/../vendor/autoload.php";

foreach (glob(__DIR__ . "/docs/*.php") as $php) {
    require $php;
}

function getConnection()
{
    $mongo = new MongoClient;
    $conn  = new \ActiveMongo2\Connection($mongo, 'activemongo2_tests');
    $conn->registerNamespace("ActiveMongo2\\Tests\\Document\\{{collection}}Document");

    return $conn;
}

getConnection()->dropDatabase();
