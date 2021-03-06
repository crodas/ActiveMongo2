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
        $doc = PostDocument::getById(0xffffff + ceil(mt_rand()*0xfffff));
    }

    /**
     *  @expectedException ActiveMongo2\Exception\NotFound
     */ 
    public function testGetoneNotFound()
    {
        $doc = PostDocument::getOne(['_id' => 0xffffff + ceil(mt_rand()*0xfffff)]);
    }

    public function testGetNoArgument()
    {
        $doc = PostDocument::get();
        $this->assertTrue($doc instanceof ActiveMongo2\Cursor\Cursor);
    }


    public function testGetOneNoArgument()
    {
        $doc = PostDocument::getOne();
        $this->assertTrue($doc instanceof PostDocument);
    }

    /**
     *  @expectedException ActiveMongo2\Exception\NotFound
     */ 
    public function testGetNotFound()
    {
        $doc = PostDocument::get(['_id' => 0xffffff + ceil(mt_rand()*0xfffff)]);
    }

    /**
     *  @expectedException ActiveMongo2\Exception\NotFound
     */ 
    public function testFindArrayException()
    {
        $docs = PostDocument::getById([2, 0xfffffff]);
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
        $doc = PostDocument::getById($ids[0]);
        $this->assertTrue($doc instanceof PostDocument);
        $doc->tags = ['something'];
        $doc->save();
        $docx = PostDocument::byId($ids[0]);
        $this->assertEquals($docx->tags, ['something']);
    }

    public function testFindNumeric()
    {
        $ids = $this->getIds();
        $this->AssertTrue(
            PostDocument::byId((String)$ids[0]) == 
            PostDocument::byId((Int)$ids[0])
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
            PostDocument::byId($ids[0])
        );
    }

    public function testFindBy()
    {
        $ids = $this->getIds();
        $this->AssertTrue(
            PostDocument::findOne(['_id' => $ids[0]]) ==
            PostDocument::byId($ids[0])
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
