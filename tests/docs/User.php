<?php

namespace ActiveMongo2\Tests\Document;

/** 
 * @Persist(collection="users") 
 * @Referenceable
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
}
