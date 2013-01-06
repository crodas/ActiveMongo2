<?php

namespace ActiveMongo2\Tests\Document;

/** 
 * @Persist(collection="users") 
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

    /** @Email */
    public $email;
}
