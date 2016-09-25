<?php

/**
 *  @Persist("_binary")
 */ 
class BinaryDoc
{
    use ActiveMongo2\Query;

    /** @Id */
    public $id;

    /** @Binary */
    public $content;

    /** @BinBase64 */
    public $base64;

}
