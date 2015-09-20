<?php

/**
 *  @Persist("_binary")
 */ 
class BinaryDoc
{
    /** @Binary */
    public $content;

    /** @BinBase64 */
    public $base64;

}
