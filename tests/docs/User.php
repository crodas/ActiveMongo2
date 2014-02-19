<?php

namespace ActiveMongo2\Tests\Document;

/** 
 * @Persist(collection="user") 
 * @Unupdatable("email")
 * @Universal(auto_increment=true,set_id=true)
 * @RefCache(username, email, visits)
 */
class UserDocument
{
    /** @Id */
    public $userid;

    /** @Reference(post) */
    public $some_post;

    /** @String @Required */
    public $username;

    /** @Inc */
    public $visits = 0;


    /** @Embed(class="Address") */
    public $address;

    /** @Password("$username") */
    public $pass;

    /** @EmbedMany(class="Address") */
    public $addresses;

    // guess the type at run time
    /** @Embed */
    public $something;

    // Guess the type at run time
    /** @EmbedMany */
    public $something_else;

    /** @Email */
    public $email;

    public $runEvent = false;

    /** @preCreate */
    public function preUpdateEvent()
    {
        $this->runEvent = true; 
    }

    /** @onHydratation */
    public static function doTest()
    {
    }
}
