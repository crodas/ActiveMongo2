<?php
use ActiveMongo2\Tests\Document\Files;
use ActiveMongo2\Tests\Document\PostDocument;
use ActiveMongo2\Tests\Document\UserDocument;

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

    /** @expectedException RuntimeException */
    public function testFileNoUpdateReference()
    {
        $conn = getConnection(true);
        $file = new Files;
        $file->id = "/foobarxxx";
        $file->namexxx = "foobar";
        $conn->file($file)->storeFile(__FILE__);


        $user = new UserDocument;
        $user->username = "crodas-avatar";
        $user->object = $file;
        $conn->save($user);

        $conn->file($user->object)->storeFile(__FILE__);
    }


    /** @expectedException RuntimeException */
    public function testFileNoUpdate()
    {
        $conn = getConnection(true);
        $file = new Files;
        $file->id = "/foobarx";
        $file->namexxx = "foobar";
        $conn->file($file)->storeFile(__FILE__);

        $conn->file($file)->storeFile(__FILE__);
    }


    /** @expectedException RuntimeException */
    public function testFileStoreFailed()
    {
        $conn = getConnection(true);
        $user = new UserDocument;
        $user->username = "crodas:" . uniqid();
        $conn->save($user);

        $post = new PostDocument;
        $post->title = "foobar";
        $post->author_id = $user->userid;
        $post->author = $user;
        $post->www_file = 'foobar';

        $conn->save($post);
    }

    /** @expectedException RuntimeException */
    public function testFileStoreInvalidType()
    {
        $conn = getConnection(true);
        $user = new UserDocument;
        $user->username = "crodas:" . uniqid();
        $conn->save($user);

        $post = new PostDocument;
        $post->title = "foobar";
        $post->author_id = $user->userid;
        $post->author = $user;
        $post->www_file = 'foobar';

        file_put_contents($tmp = __DIR__ . "/foobar.png", "foo-bar");

        $_FILES['foobar'] = array(
            'tmp_name' => $tmp,
            'name'  => basename($tmp),
            'type'  => 'image',
        );

        $conn->save($post);
    }



    public function testFileStore()
    {
        $conn = getConnection(true);
        $user = new UserDocument;
        $user->username = "crodas:" . uniqid();
        $conn->save($user);

        $post = new PostDocument;
        $post->title = "foobar";
        $post->author_id = $user->userid;
        $post->author = $user;
        $post->www_file = 'foobar';

        file_put_contents($tmp = __DIR__ . "/foobar.png", "foo-bar");

        $_FILES['foobar'] = array(
            'tmp_name' => $tmp,
            'name'  => basename($tmp),
            'type'  => 'plain/text',
        );

        $conn->save($post);
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

