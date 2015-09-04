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
    public function testUploadException()
    {
        $conn = getConnection(true);
        $file = new Files;
        $file->id = "/foobar";
        $file->namexxx = "foobar";
        $conn->file($file)->storeUpload("name");
    }

    /*
    public function testUpload()
    {
        $tmp = '/tmp/' . uniqid(true);
        copy(__FILE__, $tmp);
        $_FILES['name'] = array(
            'name' => uniqid(true),
            'tmp_name' => $tmp,
            'type' => 'text/plain',
            'size' => filesize($tmp),
            'error' => UPLOAD_ERR_OK,
        );
        $conn = getConnection(true);
        $file = new Files;
        $file->id = "/foobar";
        $file->namexxx = "foobar";
        $conn->file($file)->storeUpload("name");
    }
    */

    public function testBytes()
    {
        $conn = getConnection(true);
        $file = new Files;
        $file->id = "/foobar_raw";
        $file->namexxx = "foobar";
        $conn->file($file)->storeBytes(__FILE__);
    }

    public function testBytesUpdate()
    {
        $conn = getConnection(true);
        $file = new Files;
        $file->id = "/foobar_raw_xx";
        $file->namexxx = "foobar";
        $conn->file($file)->storeFile(__FILE__);

        $this->assertEquals(
            file_get_contents(__FILE__),
            fread($file->file, 10*1024)
        );

        $conn->file($file)->storeFile($f = __DIR__ . '/bootstrap.php');

        $this->assertEquals(
            file_get_contents($f),
            fread($file->file, 10*1024)
        );
    }

    public function testBytesDelete()
    {
        $conn = getConnection(true);
        $file = new Files;
        $file->id = "/foobar_raw_yy";
        $file->namexxx = "foobar";
        $conn->file($file)->storeFile(__FILE__);

        $this->assertEquals(
            file_get_contents(__FILE__),
            fread($file->file, 10*1024)
        );

        $raw = $conn->getDatabase();
        $tbl = (!empty($_SERVER['NAMESPACE']) ? $_SERVER['NAMESPACE'] . "." : "") . "fs.chunks";
        $this->assertEquals(1, $raw->selectCollection($tbl)->count(array('files_id' => '/foobar_raw_yy')));

        $conn->delete($file);

        $this->assertEquals(0, $raw->selectCollection($tbl)->count(array('files_id' => '/foobar_raw_yy')));
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
    public function testFileNoFile()
    {
        $conn = getConnection(true);
        $file = new Files;
        $file->id = "/foobarxxx";
        $file->namexxx = "foobar";
        $conn->file($file)->storeFile('/tmp/' . uniqid(true));
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

