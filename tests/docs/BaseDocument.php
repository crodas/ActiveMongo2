<?php

namespace ActiveMongo2\Tests\Document;

/** 
 *  @Persist(collection="post")
 *  @SingleCollection
 *  @Sluggable("title", "uri")
 *  @Universal(set_id=true, auto_increment=true)
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
