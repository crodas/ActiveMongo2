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

    /** @Date */
    public $uploadDate;

    /** @Int */
    public $length;

    /** @String (@xss) */
    public $type;
}
