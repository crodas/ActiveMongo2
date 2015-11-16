<?php

/**
 *  @Persist("_binary")
 */ 
class BinaryDoc
{
    /** @Id */
    public $id;

    /** @Binary */
    public $content;

    /** @BinBase64 */
    public $base64;

}
