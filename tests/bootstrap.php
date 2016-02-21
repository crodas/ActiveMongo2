<?php

use crodas\FileUtil\File;

require __DIR__ . "/../vendor/autoload.php";

@mkdir(__DIR__ . "/tmp");
foreach (glob(__DIR__ . "/tmp/*") as $delete) {
    echo "delete: $delete\n";
    unlink($delete);
}

File::overrideFilepathGenerator(function($prefix) {
    return __DIR__ . '/tmp/';
});

function getConnection($cache = false)
{
    static $first;

    $conf = new \ActiveMongo2\Configuration(__DIR__  . "/tmp/mapper.php");
    $conf
        ->addModelPath(__DIR__ . '/docs')
        ->development();

    if (!empty($_SERVER["NAMESPACE"])) {
        if (!$first) print "Using namespace {$_SERVER['NAMESPACE']}\n";
        $conf->SetNamespace($_SERVER["NAMESPACE"]);
    }
    if (!$first) {
        $mongo = new MongoClient;
        $mongo->selectDB('activemongo2_tests')->drop();
        $mongo->selectDB('activemongo2_tests_foobar')->drop();
        $config = new ActiveMongo2\Configuration;
        unlink($config->getLoader());
    }

    if (empty($_SERVER["NAMESPACE"])) {
        $zconn = new \ActiveMongo2\Client(new MongoClient, 'activemongo2_tests', __DIR__ . '/docs');
    } else {
        $zconn = new \ActiveMongo2\Connection($conf, new MongoClient, 'activemongo2_tests');
    }
    $zconn->AddConnection('foobar', new MongoClient, 'activemongo2_tests_foobar', 'zzzz');

    if ($cache) {
        $zconn->setCacheStorage(new \ActiveMongo2\Cache\Storage\Memory);
    }

    $first = true;

    return $zconn;
}

