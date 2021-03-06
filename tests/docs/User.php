<?php

namespace ActiveMongo2\Tests\Document;

/** 
 * @Persist(collection="user") 
 * @Unupdatable("email")
 * @Universal(auto_increment=true,set_id=true)
 * @RefCache(username, email, visits)
 * @Connection("foobar")
 * @Elastica
 */
class UserDocument
{
    /** @Id */
    public $userid;

    /** @Reference(activemongo2\tests\document\files) */
    public $object;

    /** @Reference(post) */
    public $some_post;

    /** @String @Required @Searcheable */
    public $username;

    /** @Inc */
    public $visits = 0;


    /** @Embed(class="Address") */
    public $address;

    /** @Password("$username") @MaxLength([16], "{$value} is an invalid password") */
    public $pass;

    /** @EmbedMany(class="Address") */
    public $addresses;

    /** @EmbedMany(class="Address") @UniqueBy("city") */
    public $uaddresses;

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
    public function preUpdateEvent($obj)
    {
        $this->runEvent = true; 
    }

    public function something()
    {
        return $this->userid;
    }

    /** @onHydratation */
    public static function doTest()
    {
    }
}
