<?php

$GLOBALS['x_updates'] = 0;

/** @Persist */
class ReferenceHub
{
    use ActiveMongo2\Query;

    /** @Id */
    public $id;

    /** @Reference */
    public $ref;

    /** @postSave */
    public function didSave()
    {
        ++$GLOBALS['x_updates'];
    }
}
