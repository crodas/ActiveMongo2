<?php

use ActiveMongo2\Tests\Document\PostNoTitleDocument;

class IndexTest extends \phpunit_framework_testcase
{
    public function testUniqueIndex()
    {
        $conn = getConnection();
        $conn->ensureIndex();

        $indexes = $conn->user_posts->rawCollection()->getIndexInfo();


        $this->assertEquals(0, $conn->user_posts->count());
        $this->assertTrue(is_array($indexes));
        $this->assertEquals(2, count($indexes));
        $this->assertEquals(true,  $indexes[1]['unique']);

        $post = new PostNoTitleDocument;
        $post->uri = "something that I say";
        $conn->save($post);
        $this->assertEquals($post->uri, 'something-that-i-say');

        $post = new PostNoTitleDocument;
        $post->uri = "something that I say";
        $conn->save($post);
        $this->assertNotEquals($post->uri, 'something-that-i-say');
    }

    public function testCompoundIndex()
    {
        $indexes = getConnection()->post->rawCollection()->getIndexInfo();
        foreach($indexes as $index) {
            if (array_diff($index['key'], ['title' => 1, 'uri' => 1])) {
                return $this->assertTrue(true);
            }
        }
        $this->AssertTrue(false);
    }
}
