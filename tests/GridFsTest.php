<?php
use ActiveMongo2\Tests\Document\Files;

class GridFsTest extends \phpunit_framework_testcase
{
    /**
     *  @expectedException RuntimeException
     */
    public function testInvalidSave()
    {
        $conn = getConnection(true);
        $file = new Files;
        $file->namexxx = "foobar";
        $conn->save($file);
    }

    /**
     *  @expectedException RuntimeException
     */
    public function testUpload()
    {
        $conn = getConnection(true);
        $file = new Files;
        $file->id = "/foobar";
        $file->namexxx = "foobar";
        $conn->file($file)->storeUpload("name");
    }

    public function testBytes()
    {
        $conn = getConnection(true);
        $file = new Files;
        $file->id = "/foobar_raw";
        $file->namexxx = "foobar";
        $conn->file($file)->storeBytes(__FILE__);
    }

    public function testStoreFile()
    {
        $conn = getConnection(true);
        $file = new Files;
        $file->id = "/foobar";
        $file->namexxx = "foobar";
        $conn->file($file)->storeFile(__FILE__);
    }

    /** @dependsOn testStoreFile */
    public function testFind()
    {
        $conn = getConnection(true);
        $col  = $conn->getCollection('fs');
        $this->assertTrue($col->getById("/foobar") instanceof Files);
        foreach ($col->find() as $file) {
            $this->assertTrue($file instanceof Files);
            $this->assertEquals($file->namexxx, "foobar");
        }
        $this->assertTrue(is_resource($file->file));
    }
}

