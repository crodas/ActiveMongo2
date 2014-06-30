<?php

namespace ActiveMongo2\Tests\Document;

/** 
 *  @Persist(collection="post")
 *  @SingleCollection
 *  @Sluggable(["_id", "titulo"], "uri")
 *  @Autoincrement
 *  @Timeable
 *  @Autocomplete
 *  @Index(title => 1, uri)
 */
abstract class BaseDocument
{
    /** @Id */
    public $id;

    /** @Required @String @Field("titulo") @Autocomplete */
    public $title;

    /** @String @Unique */
    public $uri;

}
