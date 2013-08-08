<?php

namespace ActiveMongo2\Tests\Document;

/** 
 * @Persist(collection="user") 
 * @Referenceable
 * @Unupdatable("email")
 */
class UserDocument
{
    /** @Id */
    public $userid;

    /** @String @Required */
    public $username;

    /** @Inc */
    public $visits = 0;


    /** @Embed(class="Address") */
    public $address;

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
}
