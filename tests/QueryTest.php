<?php

use ActiveMongo2\Tests\Document\PostDocument;
use ActiveMongo2\Tests\Document\UserDocument;

class QueryTest extends phpunit_framework_testcase
{
    /**
     *  @expectedException ActiveMongo2\Exception\NotFound
     */ 
    public function testFindNotFound()
    {
        $doc = PostDocument::find(0xffffff + ceil(mt_rand()*0xfffff));
    }

    /**
     *  @expectedException ActiveMongo2\Exception\NotFound
     */ 
    public function testFindArrayException()
    {
        $docs = PostDocument::find([2, 0xfffffff]);
    }

    public function getIds()
    {
        return PostDocument::pluck('_id');
    }

    public function testFindArray()
    {
        $ids  = $this->getIds();
        $docs = PostDocument::find([$ids[0]]);
        $this->assertTrue($docs instanceof \ActiveMongo2\Cursor\Cursor);
    }

    public function testFindAndSave()
    {
        $ids = $this->getIds();
        $doc = PostDocument::find($ids[0]);
        $this->assertTrue($doc instanceof PostDocument);
        $doc->tags = ['something'];
        $doc->save();
        $docx = PostDocument::find($ids[0]);
        $this->assertEquals($docx->tags, ['something']);
    }

    public function testFindNumeric()
    {
        $ids = $this->getIds();
        $this->AssertTrue(
            PostDocument::find((String)$ids[0]) == 
            PostDocument::find((Int)$ids[0])
        );
    }

    public function testSum()
    {
        $this->assertEquals(0, PostDocument::sum('visits'));
    }

    public function testWhere()
    {
        $ids = $this->getIds();
        $this->AssertTrue(
            PostDocument::where(['_id' => $ids[0]])->first() ==
            PostDocument::find($ids[0])
        );
    }

    public function testFindBy()
    {
        $ids = $this->getIds();
        $this->AssertTrue(
            PostDocument::find_by(['_id' => $ids[0]]) ==
            PostDocument::find($ids[0])
        );
    }

    public function testPluck()
    {
        $arr = PostDocument::pluck('_id', 'tags');
        $this->AssertTrue(is_array($arr));
        foreach ($arr as $row) {
            $this->AssertEquals(2, count($row));
        }
    }

    public function testFindOrCreate()
    {
        $ids = $this->getIds();
        $post = PostDocument::find_or_create_by(array("tags" => ['xxx', 'yyy']));
        $this->assertEquals(null, $post->id);
        $this->assertEquals(['xxx', 'yyy'], $post->tags);

        $post = PostDocument::find_or_create_by(array("tags" => 'something'));
        $this->assertEquals($ids[0], $post->id);
        $this->assertEquals(['something'], $post->tags);
    }
}
