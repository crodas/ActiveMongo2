<?php

namespace ActiveMongo2\Tests\Document;

/** 
 *  @Persist(collection="post")
 *  @SingleCollection
 *  @Sluggable("title", "uri")
 *  @Autoincrement
 */
abstract class BaseDocument
{
    /** @Id */
    public $id;

    /** @Required @String */
    public $title;

    /** @String @Unique */
    public $uri;

}
