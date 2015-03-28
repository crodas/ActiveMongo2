<?php

/**
 *  @Persist
 *  @Locking
 */
class Locked
{
    /** @Id */
    public $id;

    /** @String */
    public $title;

    /** @Array */
    public $tags;
}
