<?php

namespace ActiveMongo2\Tests\Document;

/** 
 *  @Persist(collection="post")
 *  @SingleCollection
 *  @Sluggable("titulo", "uri")
 *  @Autoincrement
 *  @Timeable
 *  @Autocomplete
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
