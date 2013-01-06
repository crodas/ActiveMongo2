<?php

use ActiveMongo2\Tests\Document\AutoincrementDocument;
use ActiveMongo2\Tests\Document\UserDocument;
use ActiveMongo2\Tests\Document\PostDocument;
use ActiveMongo2\Tests\Document\PostNoTitleDocument;
use ActiveMongo2\Tests\Document\AddressDocument;
use ActiveMongo2\Tests\Document\WrongDocument;

class SluggableTest extends \phpunit_framework_testcase
{
    /** 
     * @expectedException RuntimeException
     */
    public function testInvalidConfiguration()
    {
        getConnection()->save(new WrongDocument);
    }


    public function testSameSlug()
    {
        $conn = getConnection();
        $user = new UserDocument;
        $user->username = "crodas";
        $conn->save($user);


        $post1 = new PostDocument;
        $post1->author = $user;
        $post1->title  = "something random";
        $post1->readers[] = $user;
        $conn->save($post1);

        $post2 = new PostDocument;
        $post2->author = $user;
        $post2->title  = "something random";
        $post2->readers[] = $user;
        $conn->save($post2);

        $this->assertTrue(strlen($post2->uri) > strlen($post1->uri));
        $this->assertEquals(strncmp($post1->uri, $post2->uri, strlen($post1->uri)), 0);
    }

    public function testNoSlug()
    {
        $d = new PostNoTitleDocument;
        getConnection()->save($d);
        $this->assertEquals($d->uri, 'n-a');
    }
}
