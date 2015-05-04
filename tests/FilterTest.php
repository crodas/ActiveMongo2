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
