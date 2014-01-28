<?php

use ActiveMongo2\Tests\Document\AutoincrementDocument;
use ActiveMongo2\Tests\Document\UserDocument;
use ActiveMongo2\Tests\Document\PostDocument;
use ActiveMongo2\Tests\Document\AddressDocument;

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
        $conn->getCollection('user')->drop();
        $user = new UserDocument;
        $user->username = "crodas-" . rand(0, 0xfffff);
        $user->pass     = "foobar";
        $this->assertFalse($user->runEvent);
        $tmp = $user;
        $conn->save($user);
        $this->assertNotEquals($tmp, $user);
        $this->assertTrue($tmp->runEvent);

        $vars = get_object_vars($tmp);
        $this->assertEquals(end($vars), $user);

        $find = $conn->getCollection('user')
            ->find(array('_id' => $user->userid));

        $this->assertEquals(1, $find->count());


        /* */
        $count = $conn->user->count();
        $conn->save($tmp); 
        $this->assertEquals($count, $conn->user->count());

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

    /**
     *  @expectedException \RuntimeException
     */
    public function testInvalidEmbedValidator()
    {
        $conn = getConnection();
        $user = new UserDocument;
        $user->username = "crodas-" . rand(0, 0xfffff);
        $user->email = "fooobar";
        $conn->save($user);
    }

    /** @dependsOn testCreateUpdateDelete */
    public function testSimpleFind()
    {
        $conn = getConnection();
        $user = new UserDocument;
        $user->username = "crodas-" . rand(0, 0xfffff);
        $user->addresses[] = new AddressDocument;
        $user->addresses[] = new AddressDocument;
        $conn->save($user);

        $col = getConnection()->getCollection('user');
        foreach ($col->find() as $doc) {
            $this->assertTrue($doc instanceof UserDocument);
        }
        $this->assertTrue(is_array($col->find()->toArray()));
        $this->assertTrue(count($col->find()->toArray()) > 0);
        $this->assertTrue($col->findOne() instanceof UserDocument);
        $this->assertEquals($col->findOne(array('foo' => 'bar')), NULL);

        $res = $col->aggregate(['$project' => ['username' => 1, 'addresses' => 1]], ['$unwind' => '$addresses']);
        $this->assertEquals(count($res), 2);
        $this->assertEquals($res[0]->userid, $res[1]->userid);
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
        $post->author_ref = $user;
        $post->author = $user;
        $post->collaborators[] = $user;
        $post->title  = "foobar post";
        $post->readers[] = $user;
        $post->author_id = $user->userid;
        $conn->save($post);

        $savedPost = $conn->getCollection('post')->findOne();
        $this->assertEquals($savedPost->author->userid, $user->userid);

        // test that no request has been made
        $this->AssertTrue(is_array($savedPost->author->getReference()));
        $this->assertEquals($savedPost->author->username, $user->username);

        // test that no request has been made
        $this->AssertTrue(is_array($savedPost->author->getReference()));
        $this->assertEquals($savedPost->uri, "foobar-post");

        $user->username = "foobar";
        $conn->save($user);

        sleep(1);
        $savedPost = $conn->getCollection('post')->findOne();
        $this->assertEquals($savedPost->author->username, $user->username);
        $this->assertEquals($savedPost->collaborators[0]->username, $user->username);

        $post->tmp = 0;
        $conn->delete($post);
        $this->assertEquals(2, $post->tmp);
        $savedPost = $conn->getCollection('post')->findOne();
        $this->assertEquals(null, $savedPost);


    }

    /** 
     * @expectedException RuntimeException
     */
    public function testValidateEmail()
    {
        $conn = getConnection();
        $user = new UserDocument;
        $user->username = "foobar";
        $user->email = "dasd";
        $conn->save($user);
    }

    /** 
     * @expectedException RuntimeException
     */
    public function testUnupdatable()
    {
        $conn = getConnection();
        $user = new UserDocument;
        $user->username = "foobar";
        $user->email = "saddor@gmail.com";
        $conn->save($user);

        $user->email = "foobar@gmail.com";
        $conn->save($user);
    }

    public function testComplexUpdate()
    {
        $addr = new AddressDocument;
        $addr->city = "Asuncion";

        $conn = getConnection();
        $user = new UserDocument;
        $user->username = "foobar";
        $user->address  = $addr;
        $user->something = $addr;
        $user->something_else[] = $addr;
        $user->something_else[] = $addr;
        $user->addresses[] = $addr;
        $user->addresses[] = $addr;
        $user->addresses[] = $addr;

        $conn->save($user);
        $user->visits += 10;
        $conn->save($user);


        $user2 = $conn->getCollection('user')->findOne(array('_id' => $user->userid));
        $user2->visits += 10;
        $user2->address->city = "Luque";
        $user2->addresses[2]->city = "CDE";
        $conn->save($user);

        $user->visits  += 10;
        $user2->visits += 10;
        $user2->username = "barfoo";

        $conn->save($user);
        $conn->save($user2);

        
        $fromDB  = $conn->getCollection('user')->findOne(array('_id' => $user->userid));
        $this->assertTrue($fromDB->something instanceof AddressDocument);
        $this->assertTrue($fromDB->address instanceof AddressDocument);
        foreach ($fromDB->something_else  as $doc) {
            $this->assertTrue($doc instanceof AddressDocument);
        }
        $this->assertEquals($fromDB->address->city, "Luque");
        $this->assertEquals($fromDB->addresses[2]->city, "CDE");
        $this->assertEquals($fromDB->visits, 40);
        $this->assertEquals($fromDB->username, "barfoo");
    }

    public function testInc()
    {
        $conn = getConnection();
        $user = new UserDocument;
        $user->username = "croda2ssds";
        $conn->save($user);

        $user2 = $conn->getCollection('user')->findOne(['_id' => $user->userid]);
        $user2->visits = 10;
        $conn->save($user2);

        $user->visits = 10;
        $conn->save($user);

        $user3 = $conn->getCollection('user')->findOne(['_id' => $user->userid]);
        $this->assertEquals(20, $user3->visits);

    }

    public function testArray1()
    {
        $conn = getConnection();
        $user = new UserDocument;
        $user->username = "croda2s";
        $conn->save($user);


        $post = new PostDocument;
        $post->author = $user;
        $post->title  = "foobar";

        $post->tags = array(['x' => 'foobar'], ['x' => 'xx'], ['x' => 'xxyy']);
        $post->author_id = $user->userid;
        $conn->save($post);
        $zpost = $conn->getCollection('post')->findOne(['_id' => $post->id]);
        $this->assertEquals($zpost->tags, $post->tags);

        $post->tags[0] = ['x' => 'yyyxx'];
        $conn->save($post);
        $zpost = $conn->getCollection('post')->findOne(['_id' => $post->id]);
        $this->assertEquals($zpost->tags, $post->tags);

        $zpost = $conn->getCollection('post')->findOne(['_id' => $post->id]);
        $this->assertEquals($zpost->tags, $post->tags);

        unset($post->tags[0]);
        $conn->save($post);

        $zpost = $conn->getCollection('post')->findOne(['_id' => $post->id]);
        $this->assertEquals($zpost->tags, array_values($post->tags));
        unset($post->tags[1]);
        $conn->save($post);

        $zpost = $conn->getCollection('post')->findOne(['_id' => $post->id]);
        $this->assertEquals($zpost->tags, array_values($post->tags));
    }

    /** 
     * @dependsOn testArray1 
     * @expectedException RuntimeException
     * @expectedExceptionMessage Cannot
     */
    public function testGetByIdNotFound()
    {
        $conn = getConnection();
        $doc = $conn->getCollection('post')
            ->getById(0xffffff);
    }

    /** @dependsOn testArray1 */
    public function testGetById()
    {
        $conn = getConnection();
        $post = $conn->getCollection('post')
            ->findOne();

        $doc = $conn->getCollection('post')
            ->getById($post->id);
        $doc->foobar = "sss";

        $doc1 = $conn->getCollection('post')
            ->getById($post->id);

        $this->assertTrue(empty($doc1->foobar));
        $this->assertNotEquals($doc, $doc1);
    }

    public function testCollectionIterator()
    {
        $conn = getConnection();
        $one = $conn->post->find();

        $i = 0;
        foreach ($conn->post as $p) {
            $this->assertTrue($p instanceof ActiveMongo2\Tests\Document\PostDocument);
            $i++;
        }
        $this->assertEquals($one->count(), $i);
    }
}
