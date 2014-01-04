<?php

namespace ActiveMongo2\Tests\Document;

/** 
 *  @Persist(collection="post")
 *  @SingleCollection
 *  @Sluggable("titulo", "uri")
 *  @Autoincrement
 */
abstract class BaseDocument
{
    /** @Id */
    public $id;

    /** @Required @String @Field("titulo") */
    public $title;

    /** @String @Unique */
    public $uri;

}
