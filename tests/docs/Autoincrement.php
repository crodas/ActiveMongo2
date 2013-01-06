<?php

namespace ActiveMongo2\Tests\Document;

/** 
 * @Persist(collection="autoincrement")
 * @Autoincrement
 */
class AutoincrementDocument
{
    /** @Id */
    public $someid;
}
