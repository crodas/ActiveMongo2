<?php
namespace ActiveMongo2\Service;

/**
 *  @Service(activemongo, {
 *      host: {default: 'localhost'},
 *      user: {default: NULL},
 *      pass: {default: NULL},
 *      replicaSet: {default: NULL},
 *      db: {required: true},
 *      opts: { default:{}, type: 'hash'},
 *      path: { require: true, type: array_dir},
 *      temp_dir: { default: '/tmp', type: dir },
 *      w: {default: 1},
 *      devel: {default: true}
 *  }, { shared: true })
 */
function activemongo2_service($config)
{
    if (!$config['replicaSet']){
        $conn = new \MongoClient($config['host'], $config['opts']);
    }
    else {    
        $conn = new \MongoClient( $config[ 'host' ], array(
            "replicaSet" => $config['replicaSet'] 
        ), $config[ 'opts' ] );
        $conn->setReadPreference( \MongoClient::RP_SECONDARY );
        \MongoCursor::$slaveOkay = true;
    }

    $db   = $conn->selectDB($config['db']);
    if ($config['user'] || $config['pass']) {
        $db->authenticate($config['user'], $config['pass']);
    }

    $conf = new \ActiveMongo2\Configuration(
        $config['temp_dir'] . "/activemongo2__" . $db . ".php"
    );

    foreach ((array)$config['path'] as $path) {
        $conf->addModelPath($path);
    }

    if ($config['devel']) {
        $conf->development();
    }
    $conf->setWriteConcern($config['w']);
    $mongo = new \ActiveMongo2\Connection($conf, $conn, $db);
    return $mongo;
}
