<?php

namespace ActiveMongo2\Tests\Document;

/** @Persist(collection="users") */
class UserDocument
{
    /** @Id */
    public $userid;

    /** @String @Required */
    public $username;

}
