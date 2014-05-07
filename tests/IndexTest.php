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

        $info = $conn->command(array('buildinfo'=>true));


        $indexes = $conn->user_posts->rawCollection()->getIndexInfo();

        $this->assertEquals(0, $conn->user_posts->count());
        $this->assertTrue(is_array($indexes));
        $this->assertEquals(2, count($indexes), 'mongod ' . $info['version']);
        $this->assertEquals(true,  $indexes[1]['unique']);

        $post = new PostNoTitleDocument;
        $post->uri = "something that I say";
        $conn->save($post);

        $post = new PostNoTitleDocument;
        $post->uri = "something that I say";
        $conn->save($post);
    }
}
