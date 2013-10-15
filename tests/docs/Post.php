<?php

namespace ActiveMongo2\Tests\Document;

/** 
 * @Persist(collection="post")
 * @Sluggable("title", "uri")
 * @Universal(set_id=true, auto_increment=true)
 */
class PostDocument
{
    /** @Id */
    public $id;

    /** @Universal */
    public $global_id;

    /** @Reference("user", [username, email]) @Required */
    public $author;

    /** @Int */
    public $author_id;

    /** @AutoincrementBy(author_id) */
    public $post_by_user_id;

    /** @ReferenceMany("user", [username, email]) */
    public $collaborators = array();

    /** @Required @String */
    public $title;

    /** @String @Unique */
    public $uri;

    /** @ReferenceMany("user") */
    public $readers;

    /** @EmbedMany("user") */
    public $readers_1;

    /** @Integer */
    public $xxxyyy;

    /** @Array */
    public $tags;

    /**
     *  @preSave
     */
    public function __do_something()
    {
    }
}
