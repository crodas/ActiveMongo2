<?php

namespace ActiveMongo2\Tests\Document;

/** 
 * @Persist(collection="user_posts")
 * @Autoincrement
 */
class PostDocument
{
    /** @Id */
    public $id;

    /** @Reference("user") @Required */
    public $author;
}
