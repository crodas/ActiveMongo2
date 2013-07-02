<?php

namespace ActiveMongo2\Tests\Document;

/** 
 * @Persist(collection="user_posts")
 * @Sluggable("title", "uri")
 * @Autoincrement
 * @Universal
 */
class PostDocument
{
    /** @Id */
    public $id;

    /** @Reference("user") @Required */
    public $author;

    /** @Required @String */
    public $title;

    /** @String @Unique */
    public $uri;

    /** @ReferenceMany("user") */
    public $readers;
}
