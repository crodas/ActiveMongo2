<?php

namespace ActiveMongo2\Service;

/**
 *  @Service(activemongo, {
 *      host: {default: 'localhost'},
 *      user: {default: NULL},
 *      pass: {default: NULL},
 *      db: {required: true},
 *      class: {type: 'string'},
 *      opts: { default:{}, type: 'hash'}
 *  }, { shared: true })
 */
function activemongo2_service($config)
{
    $conn = new \MongoClient($config['host'], $config['opts']);
    $db   = $conn->selectDB($config['db']);
    if ($config['user'] || $config['pass']) {
        $db->authenticate($config['user'], $config['pass']);
    }
    $mongo = new \ActiveMongo2\Connection($conn, $config['db']);
    $mongo->registerNamespace($config['class']);
    return $mongo;
}

