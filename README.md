ActiveMongo2
============

ActiveMongo2 is a very simple, efficient and developer friendly PHP abstraction for MongoDB.

*This is a work in progress, but it is being used already in production at some sites.*

How does it work?
-----------------

`ActiveMongo2` is not backward compatible with [ActiveMongo](https://github.com/crodas/ActiveMongo). `ActiveMongo2` generates code to avoid doing any checks at run time. Therefore configuration is a  bit more complicated.

```php
// /tmp/mapper.php would be generated
$conf = new \ActiveMongo2\Configuration("/tmp/mapper.php");
$conf->addModelPath(__DIR__ . "/app/model");
$conf->development(); // remove this line at production

// create mongodb connection
$mongo = new \MongoClient;

// create the ActiveMongo2 connection
$conn  = new \ActiveMongo2\Connection($conf, $mongo, 'database');
```


This configuration would walk checking each `*.php` file inside `__DIR__ . "/app/model"`, it would be looking for `@Persist` annotation.

The `ActiveMongo2\Connection` provides several methods, the most useful is `->getCollection("collection_name")`.

TODO
====
1. Write docs
