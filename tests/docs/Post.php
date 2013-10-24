<?php

namespace ActiveMongo2\Tests\Document;

/** 
 * @Persist(collection="post")
 */
class PostDocument extends BaseDocument
{
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
