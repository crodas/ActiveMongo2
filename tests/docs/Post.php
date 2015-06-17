<?php

namespace ActiveMongo2\Tests\Document;

class Middle extends BaseDocument
{
    /**
     *  @preSave
     */
    public function foobar()
    {
    }
}

/** 
 *  @Persist
 *  @RefCache('title', 'tags', 'author')
 */
class PostDocument extends Middle
{
    use \ActiveMongo2\Query;

    /** @Universal @Index(desc) */
    public $global_id;

    /** @Reference("user") @Deferred */
    public $author_ref;

    /** @FileUpload(Path => ../tmp-files, Validate => text) */
    public $www_file;

    /** @Array @Limit(-3) */
    public $array = array();

    /** @Geo @Index */
    public $geo;

    /** @ReferenceMany("user") @Deferred */
    public $author_refs;

    /** @Reference("user") @Required */
    public $author;

    /** @Int */
    public $author_id;

    /** @Float */
    public $float;

    /** @Numeric */
    public $number;

    /** @bool */
    public $boolean;

    /** @AutoincrementBy(author_id) */
    public $post_by_user_id;

    /** @ReferenceMany("user", [username, email]) */
    public $collaborators = array();

    /** @ReferenceMany("user") */
    public $readers;

    /** @EmbedMany("user") */
    public $readers_1;

    /** @Integer @Between([-20, 20], "xxxyy must be between -20 and 20 {$value} given") */
    public $xxxyyy;

    /** @Array */
    public $tags;

    /** @Date */
    public $something;

    /** @Date */
    public $created;

    /** @Date */
    public $updated;

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
