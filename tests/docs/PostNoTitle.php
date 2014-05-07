<?php

namespace ActiveMongo2\Tests\Document;

/** 
 * @Persist(collection="user_posts")
 * @Sluggable("title", "uri")
 * @Autoincrement
 */
class PostNoTitleDocument
{
    /** @Id */
    public $id;

    /** @String */
    public $title;

    /** @String @Unique @Index */
    public $uri;
}
