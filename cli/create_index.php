<?php

namespace ActiveMongo2\Cli;

/**
 *  @Cli("db:create_index", "Create indexes in activemongo2")
 *  @Arg("docdir", REQUIRED, "Directory where activemongo2 classes are defined")
 *  @Arg("db", REQUIRED, "Database name")
 */
function create_index($input, $output)
{
    $args = $input->getArguments();

    if (!is_dir($args['docdir'])) {
        throw new \RuntimeException("{$args['docdir']} is not not a directory");
    }

    $conf = new \ActiveMongo2\Configuration(tempnam(sys_get_temp_dir(), "activemongo2"));
    $conf
        ->addModelPath($args['docdir'])
        ->development();

    $mongo = new \MongoClient;
    $zconn = new \ActiveMongo2\Connection($conf, $mongo, $args['db']);

    $zconn->ensureIndex();

    
    $output->writeLn("creating were created");
}
