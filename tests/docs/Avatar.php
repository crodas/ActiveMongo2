<?php

namespace ActiveMongo2\Tests\Document;

/**
 *  @Persist
 *  @GridFs
 */
class Files
{
    /** @Id */
    public $id;

    /** @Required */
    public $namexxx;

    /** @Stream */
    public $file;
}