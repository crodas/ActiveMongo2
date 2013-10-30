<?php

namespace ActiveMongo2\Tests\Document;

/** 
 * @Persist
 */
class PostDocument extends BaseDocument
{
    /** @Universal */
    public $global_id;

    /** @Reference("user", [username, email]) @Deferred */
    public $author_ref;

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

    /**
     *  @postDelete
     *  @preDelete
     */
    public function delete($object)
    {
        if ($this == $object && isset($this->tmp)) {
            $this->tmp++;
        }
    }
}
