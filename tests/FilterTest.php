<?php

use ActiveMongo2\Tests\Document\AutoincrementDocument;
use ActiveMongo2\Tests\Document\UserDocument;
use ActiveMongo2\Tests\Document\PostDocument;
use ActiveMongo2\Tests\Document\AddressDocument;

class FilterTest extends phpunit_framework_testcase
{
    public function testNumeric()
    {
        $conn = getConnection();
        $conn->getCollection('post')->drop();
        $user = new UserDocument;
        $user->username = "crodas";
        $conn->save($user);

        $post = new PostDocument;
        $post->author_ref = $user;
        $post->author = $user;
        $post->collaborators[] = $user;
        $post->title  = "foobar post";
        $post->array  = [1];
        $post->readers[] = $user;
        $post->author_id = $user->userid;
        $post->number = "1.25";
        $conn->save($post);

        $this->assertTrue(is_float($post->number));
        $this->assertEquals(1.25, $post->number);
    }

    public function testFloat()
    {
        $conn = getConnection();
        $conn->getCollection('post')->drop();
        $user = new UserDocument;
        $user->username = "crodas";
        $conn->save($user);

        $post = new PostDocument;
        $post->author_ref = $user;
        $post->author = $user;
        $post->collaborators[] = $user;
        $post->title  = "foobar post";
        $post->array  = [1];
        $post->readers[] = $user;
        $post->author_id = $user->userid;
        $post->float = 1;
        $conn->save($post);

        $this->assertTrue(is_float($post->float));
    }

    /**
     *  @expectedException RuntimeException
     */
    public function testInvalidArray()
    {
        $conn = getConnection();
        $conn->getCollection('post')->drop();
        $user = new UserDocument;
        $user->username = "crodas";
        $conn->save($user);

        $post = new PostDocument;
        $post->author_ref = $user;
        $post->author = $user;
        $post->collaborators[] = $user;
        $post->title  = "foobar post";
        $post->array  = 1;
        $post->readers[] = $user;
        $post->author_id = $user->userid;
        $conn->save($post);
    }

    /**
     *  @expectedException RuntimeException
     */
    public function testInvalidGeo1()
    {
        $conn = getConnection();
        $conn->getCollection('post')->drop();
        $user = new UserDocument;
        $user->username = "crodas";
        $conn->save($user);

        $post = new PostDocument;
        $post->author_ref = $user;
        $post->author = $user;
        $post->collaborators[] = $user;
        $post->title  = "foobar post";
        $post->geo  = 1;
        $post->readers[] = $user;
        $post->author_id = $user->userid;
        $conn->save($post);
    }

    /**
     *  @expectedException RuntimeException
     */
    public function testInvalidGeo3()
    {
        $conn = getConnection();
        $conn->getCollection('post')->drop();
        $user = new UserDocument;
        $user->username = "crodas";
        $conn->save($user);

        $post = new PostDocument;
        $post->author_ref = $user;
        $post->author = $user;
        $post->collaborators[] = $user;
        $post->title  = "foobar post";
        $post->geo  = array(1,"cesar");
        $post->readers[] = $user;
        $post->author_id = $user->userid;
        $conn->save($post);
    }

    /**
     *  @expectedException RuntimeException
     */
    public function testInvalidGeo2()
    {
        $conn = getConnection();
        $conn->getCollection('post')->drop();
        $user = new UserDocument;
        $user->username = "crodas";
        $conn->save($user);

        $post = new PostDocument;
        $post->author_ref = $user;
        $post->author = $user;
        $post->collaborators[] = $user;
        $post->title  = "foobar post";
        $post->geo  = array(1,2,3,5);
        $post->readers[] = $user;
        $post->author_id = $user->userid;
        $conn->save($post);
    }

    public function testGeo()
    {
        $conn = getConnection();
        $conn->getCollection('post')->drop();
        $user = new UserDocument;
        $user->username = "crodas";
        $conn->save($user);

        $post = new PostDocument;
        $post->author_ref = $user;
        $post->author = $user;
        $post->collaborators[] = $user;
        $post->title  = "foobar post";
        $post->geo  = array(1,2);
        $post->readers[] = $user;
        $post->author_id = $user->userid;
        $conn->save($post);
        $this->assertEquals(array(1.0, 2.0), $post->geo);
    }

    /**
     *  @expectedException RuntimeException
     */
    public function testBrokenReference()
    {
        $conn = getConnection();
        $user = new UserDocument;
        $user->username = "croda-s";
        $conn->save($user);

        $post = new PostDocument;
        $post->author_ref = $user;
        $post->author = $user;
        $post->collaborators[] = $user;
        $post->title  = "foobar post";
        $post->array  = [1];
        $post->readers[] = $user;
        $post->author_id = $user->userid;
        $conn->save($post);


        $conn->delete($user);

        $post = $conn->post->findOne(['_id' => $post->id]);
        $post->author_ref->getObject(); // read object

    }

    /**
     *  @expectedException RuntimeException
     */
    public function testInvalidReferenceMany()
    {
        $conn = getConnection();
        $user = new UserDocument;
        $user->username = "croda-s";
        $conn->save($user);

        $post = new PostDocument;
        $post->author_ref = null;
        $post->author = $user;
        $post->collaborators[] = $user;
        $post->title  = "foobar post";
        $post->array  = [1];
        $post->readers[] = null;
        $post->author_id = $user->userid;
        $conn->save($post);
        $conn->delete($user);
    }

    /**
     *  @expectedException RuntimeException
     */
    public function testInvalidReferenceMany_InvalidArray()
    {
        $conn = getConnection();
        $user = new UserDocument;
        $user->username = "croda-s";
        $conn->save($user);

        $post = new PostDocument;
        $post->author_ref = null;
        $post->author = $user;
        $post->collaborators[] = $user;
        $post->title  = "foobar post";
        $post->array  = [1];
        $post->author_refs = true;
        $post->author_id = $user->userid;
        $conn->save($post);
    }

    /**
     *  @expectedException RuntimeException
     */
    public function testInvalidReferenceMany_InvalidClass()
    {
        $conn = getConnection();
        $user = new UserDocument;
        $user->username = "croda-s";
        $conn->save($user);

        $post = new PostDocument;
        $post->author_ref = null;
        $post->author = $user;
        $post->collaborators[] = $user;
        $post->title  = "foobar post";
        $post->array  = [1];
        $post->author_refs = [new stdclass];
        $post->author_id = $user->userid;
        $conn->save($post);
    }


    /**
     *  @expectedException RuntimeException
     */
    public function testInvalidEmbedMany_InvalidClass()
    {
        $conn = getConnection();
        $user = new UserDocument;
        $user->username = "croda-s";
        $conn->save($user);

        $post = new PostDocument;
        $post->author_ref = null;
        $post->author = $user;
        $post->collaborators[] = $user;
        $post->title  = "foobar post";
        $post->array  = [1];
        $post->readers_1 = [new stdclass];
        $post->author_id = $user->userid;
        $conn->save($post);
        $conn->delete($user);
    }

    /**
     *  @expectedException RuntimeException
     */
    public function testInvalidEmbedMany_1()
    {
        $conn = getConnection();
        $user = new UserDocument;
        $user->username = "croda-s";
        $conn->save($user);

        $post = new PostDocument;
        $post->author_ref = null;
        $post->author = $user;
        $post->collaborators[] = $user;
        $post->title  = "foobar post";
        $post->array  = [1];
        $post->readers_1 = true;
        $post->author_id = $user->userid;
        $conn->save($post);
        $conn->delete($user);
    }

    /**
     *  @expectedException RuntimeException
     */
    public function testInvalidEmbedMany()
    {
        $conn = getConnection();
        $user = new UserDocument;
        $user->username = "croda-s";
        $conn->save($user);

        $post = new PostDocument;
        $post->author_ref = null;
        $post->author = $user;
        $post->collaborators[] = $user;
        $post->title  = "foobar post";
        $post->array  = [1];
        $post->readers_1[] = null;
        $post->author_id = $user->userid;
        $conn->save($post);
        $conn->delete($user);
    }

    /**
     *  @expectedException RuntimeException
     */
    public function testInvalidEmbedOne()
    {
        $conn = getConnection();
        $user = new UserDocument;
        $user->username = "croda-s";
        $conn->save($user);

        $post = new PostDocument;
        $post->author_ref = null;
        $post->author = null;
        $post->collaborators[] = $user;
        $post->title  = "foobar post";
        $post->array  = [1];
        $post->readers[] = $user;
        $post->author_id = $user->userid;
        $conn->save($post);


        $conn->delete($user);
    }



    /**
     *  @expectedException RuntimeException
     */
    public function testInvalidFloat()
    {
        $conn = getConnection();
        $conn->getCollection('post')->drop();
        $user = new UserDocument;
        $user->username = "crodas";
        $conn->save($user);

        $post = new PostDocument;
        $post->author_ref = $user;
        $post->author = $user;
        $post->collaborators[] = $user;
        $post->title  = "foobar post";
        $post->array  = [1];
        $post->readers[] = $user;
        $post->author_id = $user->userid;
        $post->float = "something else";
        $conn->save($post);
    }

    /**
     *  @expectedException RuntimeException
     */
    public function testInvalidNumeric()
    {
        $conn = getConnection();
        $conn->getCollection('post')->drop();
        $user = new UserDocument;
        $user->username = "crodas";
        $conn->save($user);

        $post = new PostDocument;
        $post->author_ref = $user;
        $post->author = $user;
        $post->collaborators[] = $user;
        $post->title  = "foobar post";
        $post->array  = [1];
        $post->readers[] = $user;
        $post->author_id = $user->userid;
        $post->number = "something else";
        $conn->save($post);
    }

    /**
     *  @expectedException RuntimeException
     */
    public function testInvalidInt()
    {
        $conn = getConnection();
        $conn->getCollection('post')->drop();
        $user = new UserDocument;
        $user->username = "crodas";
        $conn->save($user);

        $post = new PostDocument;
        $post->author_ref = $user;
        $post->author = $user;
        $post->collaborators[] = $user;
        $post->title  = "foobar post";
        $post->array  = [1];
        $post->readers[] = $user;
        $post->author_id = "something else";
        $conn->save($post);
    }

    /**
     *  @expectedException RuntimeException
     */
    public function testInvalidDate()
    {
        $conn = getConnection();
        $conn->getCollection('post')->drop();
        $user = new UserDocument;
        $user->username = "crodas";
        $conn->save($user);

        $post = new PostDocument;
        $post->author_ref = $user;
        $post->author = $user;
        $post->collaborators[] = $user;
        $post->title  = "foobar post";
        $post->array  = [1];
        $post->readers[] = $user;
        $post->author_id = $user->userid;
        $post->something = "invalid date";
        $conn->save($post);
    }

    public function testBoolean()
    {
        $conn = getConnection();
        $conn->getCollection('post')->drop();
        $user = new UserDocument;
        $user->username = "crodas";
        $conn->save($user);

        $post = new PostDocument;
        $post->author_ref = $user;
        $post->author = $user;
        $post->collaborators[] = $user;
        $post->title  = "foobar post";
        $post->array  = [1];
        $post->readers[] = $user;
        $post->author_id = $user->userid;
        $post->boolean = "invalid date";
        $conn->save($post);

        $this->assertTrue($post->boolean);
    }

    /**
     *  @expectedException RuntimeException
     */
    public function testInvalidString()
    {
        $conn = getConnection();
        $conn->getCollection('post')->drop();
        $user = new UserDocument;
        $user->username = ["crodas"];

        $conn->save($user);
    }

}
