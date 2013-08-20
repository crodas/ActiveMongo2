<?php

use ActiveMongo2\Tests\Document\PostNoTitleDocument;

class IndexTest extends \phpunit_framework_testcase
{
    /** 
     * @expectedException MongoException
     */
    public function testUniqueIndex()
    {
        $conn = getConnection();
        $conn->ensureIndex();

        $post = new PostNoTitleDocument;
        $post->uri = "something that I say";
        $conn->save($post);

        $post = new PostNoTitleDocument;
        $post->uri = "something that I say";
        $conn->save($post);
    }
}
