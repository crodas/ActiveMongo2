<?php

require __DIR__ . "/../vendor/autoload.php";

@mkdir(__DIR__ . "/tmp");
foreach (glob(__DIR__ . "/tmp/*") as $delete) {
    unlink($delete);
}

function getConnection($cache = false)
{
    static $first;

    $conf = new \ActiveMongo2\Configuration(__DIR__ . "/tmp/foo.php");
    $conf
        ->addModelPath(__DIR__ . '/docs')
        ->development();

    if ($cache) {
        $conf->setCacheStorage(new \ActiveMongo2\Cache\Storage\Memory);
    }

    $mongo = new MongoClient;
    $zconn = new \ActiveMongo2\Connection($conf, $mongo, 'activemongo2_tests');

    if (!$first) $zconn->dropDatabase();
    $first = true;

    return $zconn;
}

