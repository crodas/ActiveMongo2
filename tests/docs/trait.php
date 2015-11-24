<?php

namespace ActiveMongo2\Tests\Document;

trait foobartrait
{
    /** @postsave */
    public function dosomethignelse() {
        $GLOBALS['traited'] = true;
    }
}

