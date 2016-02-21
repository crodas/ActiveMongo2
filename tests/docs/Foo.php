<?php

/** @Persist(foos) */
class Foo
{
    use ActiveMongo2\Query;

    /** @Id */
    public $id;

    /** @Int */
    public $x;

    /** @Reference(Foo) */
    public $foo;

    /** @Reference(Foo) */
    public $bar;
}
