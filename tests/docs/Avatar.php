<?php

namespace ActiveMongo2\Tests\Document;

/**
 *  @Persist
 *  @GridFs
 *  @Connection("foobar")
 */
class Files
{
    /** @Id */
    public $id;

    /** @Required */
    public $namexxx;

    /** @Stream */
    public $file;

    /** @String (@xss) */
    public $type;
}
