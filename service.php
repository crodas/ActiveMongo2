<?php

namespace ActiveMongo2\Service;

/**
 *  @Service(activemongo, {
 *      host: {default: 'localhost'},
 *      user: {default: NULL},
 *      pass: {default: NULL},
 *      db: {required: true},
 *      opts: { default:{}, type: 'hash'},
 *      path: { require: true},
 *      devel: {default: true}
 *  }, { shared: true })
 */
function activemongo2_service($config)
{
    $conn = new \MongoClient($config['host'], $config['opts']);
    $db   = $conn->selectDB($config['db']);
    if ($config['user'] || $config['pass']) {
        $db->authenticate($config['user'], $config['pass']);
    }

    $conf = new \ActiveMongo2\Configuration(
        "/tmp/activemongo2:" . $db . ".php"
    );

    foreach ((array)$config['path'] as $path) {
        $conf->addModelPath($path);
    }

    if ($config['devel']) {
        $conf->development();
    }
    $mongo = new \ActiveMongo2\Connection($conf, $conn, $db);
    return $mongo;
}

