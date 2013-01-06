<?php

use ActiveMongo2\Tests\Document\AutoincrementDocument;
use ActiveMongo2\Tests\Document\UserDocument;
use ActiveMongo2\Tests\Document\PostDocument;

class SimpleTest extends \phpunit_framework_testcase
{
    public function testGetCollection()
    {
        $conn = getConnection();
        $this->assertTrue($conn instanceof \ActiveMongo2\Connection);
        $this->assertTrue($conn->getCollection('user') instanceof \ActiveMongo2\Collection);
    }

    /** 
     * @expectedException RuntimeException
     */
    public function testNotFoundCollection()
    {
        $conn = getConnection();
        $conn->getCollection('foobardasda');
    }

    public function testCreateUpdateDelete()
    {
        $conn = getConnection();
        $user = new UserDocument;
        $user->username = "crodas-" . rand(0, 0xfffff);
        $conn->save($user);
        $this->assertTrue($user->userid instanceof \MongoId);

        $find = $conn->getCollection('user')
            ->find(array('_id' => $user->userid));

        $this->assertEquals(1, $find->count());

        foreach ($find as $u) {
            $this->assertTrue($u instanceof $user);
            $this->assertEquals($u->username, $user->username);
        }

        $conn->delete($u);
        
        $find = $conn->getCollection('user')
            ->find(array('_id' => $user->userid));

        $this->assertEquals(0, $find->count());
    }

    /** @dependsOn testSimpleFind */
    public function testDrop()
    {
        $conn = getConnection();
        $user = new UserDocument;
        $user->username = "crodas-" . rand(0, 0xfffff);
        $conn->save($user);

        $userCol = $conn->getCollection('user');
        $this->assertTrue($userCol->count() > 0);
        $userCol->drop();
        $this->assertTrue($userCol->count() == 0);
    }

    /** @dependsOn testCreateUpdateDelete */
    public function testSimpleFind()
    {
        $conn = getConnection();
        $user = new UserDocument;
        $user->username = "crodas-" . rand(0, 0xfffff);
        $conn->save($user);

        $col = getConnection()->getCollection('user');
        foreach ($col->find() as $doc) {
            $this->assertTrue($doc instanceof UserDocument);
        }
        $this->assertTrue(is_array($col->find()->toArray()));
        $this->assertTrue(count($col->find()->toArray()) > 0);
        $this->assertTrue($col->findOne() instanceof UserDocument);
        $this->assertEquals($col->findOne(array('foo' => 'bar')), NULL);
    }

    public function testPluginAutoincrement()
    {
        $conn = getConnection();
        for($i=0; $i < 50; $i++) {
            $doc = new AutoincrementDocument;
            $conn->save($doc);
            $this->assertEquals($doc->someid, $i+1);
        }
    }

    public function testReferenceOne()
    {
        $conn = getConnection();
        $user = new UserDocument;
        $user->username = "crodas";

        $conn->save($user);


        $post = new PostDocument;
        $post->author = $user;
        $conn->save($post);

        $savedPost = $conn->getCollection('post')->findOne();
        $this->assertEquals($savedPost->author->userid, $user->userid);
        $this->assertEquals($savedPost->author->username, $user->username);

    }
}
