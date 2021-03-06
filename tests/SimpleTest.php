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
        $this->assertTrue($tmp->runEvent);

        $find = $conn->getCollection('user')
            ->find($user->userid.'');

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

    public function testPagination()
    {
        $conn = getConnection();
        $userCol = $conn->getCollection('user');
        $cursor = $userCol->find();
        $pages = $cursor->paginate(1, 20);
        $this->assertEquals(['current' => 1, 'pages' => [1]], $pages);
        for ($i=0; $i < 1000; $i++) {
            $user = new UserDocument;
            $user->username = "crodas-" . rand(0, 0xfffff);
            $conn->save($user);
        }
        $userCol = $conn->getCollection('user');
        $cursor = $userCol->find();
        $pages = $cursor->paginate(1, 20);
        $this->assertEquals($pages['current'], 1);
        $this->assertEquals(min($pages['pages']), 1);
        $this->assertEquals(max($pages['pages']), 50);

        $pages = $cursor->paginate(15, 20);
        $this->assertEquals($pages['current'], 15);
        $this->assertEquals(min($pages['pages']), 13);
        $this->assertEquals(max($pages['pages']), 50);

        $info = $cursor->info();
        $this->assertEquals(280, $info['skip']);
        $this->assertEquals(20, $info['limit']);

        $_REQUEST['page'] = 20;
        $pages = $cursor->paginate('page', 20);
        $this->assertEquals($pages['current'], 20);
        $this->assertEquals(min($pages['pages']), 18);
        $this->assertEquals(max($pages['pages']), 50);

        $info = $cursor->info();
        $this->assertEquals(380, $info['skip']);
        $this->assertEquals(20, $info['limit']);
    }


    /** 
     * @dependsOn testReferenceOne 
     * @dependsOn testSimpleFind 
     */
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

        $user = new UserDocument;
        $user->username = "crodas-" . rand(0, 0xfffff);
        $conn->save($user);
        $this->assertTrue($userCol->count() > 0);
        $conn->dropDatabase();
        $this->assertTrue($userCol->count() != 0);
        $conn->dropDatabase('foobar');
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
            $post = new PostDocument;
            $post->author_ref = $user;
            $post->author = $user;
            $post->collaborators[] = $user;
            $post->title  = "foobar post";
            $post->array  = [1];
            $post->readers[] = $user;
            $post->author_id = $user->userid;
            $GLOBALS['traited'] = false;
            $conn->save($post);
            $this->assertTrue($GLOBALS['traited']);
            $this->assertTrue($doc instanceof UserDocument);
            $this->assertTrue($post instanceof PostDocument);
        }

        $this->assertTrue(is_array($col->find()->toArray()));
        $this->assertTrue(count($col->find()->toArray()) > 0);
        $this->assertTrue($col->findOne() instanceof UserDocument);
        $this->assertEquals($col->findOne(array('foo' => 'bar')), NULL);

        $res = $col->aggregate([['$project' => ['username' => 1, 'addresses' => 1]], ['$unwind' => '$addresses']]);
        $this->assertEquals(count($res), 2);
        $this->assertEquals($res[0]->userid, $res[1]->userid);

        $res = getConnection()->getCollection('post')
            ->aggregate(['$project' => ['titulo' => 1]]);
        $this->assertEquals(count($res), 1);
        $this->assertEquals($res[0], array('_id' => 1, 'titulo' => 'foobar post'));
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

    public function testDoubleReference()
    {
        $x = new Foo;
        $x->x = 1;
        $x->bar = new Foo;
        $x->save();

        $this->assertTrue(getconnection()->getCollection('Foo')->is($x->bar));
    }

    /**
     *  @expectedException RuntimeException
     */
    public function testConnectionException()
    {
        getConnection()->getConnection(uniqid(true));
    }

    /**
     *  @expectedException RuntimeException
     */
    public function testGetDatabaseException()
    {
        getConnection()->getDatabase(uniqid(true));
    }

    public function testReferenceSave()
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
        $post->array  = [1];
        $post->readers[] = $user;
        $post->author_id = $user->userid;
        $conn->save($post);

        $conn->save($post->author_ref);

        $post->author_ref->username = "david";
        $conn->save($post->author_ref);

        $user = $conn->getCollection('user')->findOne(['_id' => $user->userid]);
        $this->assertEquals($user->username, "david");

        /* Wrap __call/__get() {{{ */
        $this->assertEquals($post->author_ref->_id, $user->userid);
        $this->assertEquals($post->author_ref->username(), $user->username);
        $this->assertEquals($post->author_ref->something(), $user->userid);
        $this->assertEquals($post->author_ref->userid(), $user->userid);
        /* }}} */

        $conn->dropDatabase();

    }

    public function testReferenceOne()
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
        $post->something = new Datetime;
        $conn->save($post);

        $post->array  = [2,3,4,5,6];
        $conn->save($post);
        $this->assertNotEquals(NULL, $post->created);
        $this->assertTrue($post->something instanceof MongoDate);

        $savedPost = $conn->getCollection('post')->findOne();
        $this->assertEquals($savedPost->author->userid, $user->userid);

        // test that no request has been made
        $this->AssertTrue(is_array($savedPost->author->getReference()));
        $this->assertEquals($savedPost->author->username, $user->username);

        // test that no request has been made
        $this->AssertTrue(is_array($savedPost->author->getReference()));
        $this->assertEquals("1-foobar-post", $savedPost->uri);

        $user->username = "foobar";
        $conn->save($user);

        sleep(1);
        $savedPost = $conn->getCollection('post')->findOne();
        $this->assertEquals($savedPost->author->username, $user->username);
        $this->assertEquals($savedPost->collaborators[0]->username, $user->username);

        $post->array[] = 9;
        $post->array[] = 10;
        $post->something = "1987/08/25";
        $conn->save($post);
        $savedPost = $conn->getCollection('post')->findOne();
        $this->assertEquals($savedPost->array, [6,9,10]);
        $this->assertEquals("1987-08-25", date("Y-m-d", $post->something->sec));

        $post->array[] = 19;
        $conn->save($post);
        $savedPost = $conn->getCollection('post')->findOne();
        $this->assertEquals($savedPost->array, [9,10,19]);

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

        $addr1 = new AddressDocument;
        $addr1->city = "Luque";

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

        $user->uaddresses[] = $addr;
        $user->uaddresses[] = $addr;
        $user->uaddresses[] = $addr1;
        $user->uaddresses[] = $addr1;

        $conn->save($user);
        $user->visits += 10;
        $conn->save($user);


        $user2 = $conn->getCollection('user')->findOne(array('_id' => $user->userid));
        $user2->visits += 10;
        $user2->address->city = "Luque";
        $user2->addresses[2]->city = "CDE";
        $this->assertEquals(2, count($user2->uaddresses));
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

        $user->some_post = $post;
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

        // dont fail is _id not define
        $conn->save($user);

        $zpost = $conn->getCollection('post')->findOne(['_id' => $post->id]);
        $this->assertEquals($zpost->tags, array_values($post->tags));
    }

    /** 
     * @dependsOn testArray1 
     * @expectedException ActiveMongo2\Exception\NotFound
     */
    public function testGetNotFound()
    {
        $conn = getConnection();
        $doc = $conn->getCollection('post')
            ->get(['_id' => 0xffffff]);
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

    public function testGetById_WithMongoIdString()
    {
        $conn = getConnection();
        $x = new BinaryDoc; 
        $x->content = "hi there";
        $conn->save($x);
        $this->assertTrue($x->id instanceof MongoId);
        $y = $conn->_binary->getById((string)$x->id);
        $this->assertEquals($x, $y);
        $conn->delete($x);
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

    public function testReflectionByClassOrCollection()
    {
        $conn = getConnection();
        $this->assertEquals(
            $conn->getReflection('post'),    
            $conn->getReflection('ActiveMongo2\Tests\Document\BaseDocument')
        );

        $post = new ActiveMongo2\Tests\Document\PostDocument;

        $this->assertEquals(
            $conn->getReflection($post),
            $conn->getReflection(get_class($post))
        );
    }

    public function testGetCollections()
    {
        $cols = getConnection()->getCollections();
        $this->assertTrue(count($cols) > 5);
        foreach ($cols as $col) {
            $this->assertTrue($col instanceof \ActiveMongo2\Collection);
            $this->assertTrue($col->getReflection() instanceof \ActiveMongo2\Reflection\Collection);
        }
    }

    /**
     *  @expectedException UnexpectedValueException
     */
    public function testValidator()
    {
        $cols = getConnection()->getCollections();

        $conn = getConnection();
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
        $post->xxxyyy = 31;
        $conn->save($post);
    }

    public function testReflections()
    {
        $conn = getConnection();
        $reflection = $conn->getReflection('ActiveMongo2\Tests\Document\UserDocument');
        $this->assertTrue($reflection instanceof \ActiveMongo2\Reflection\Collection);
        $this->assertEquals(1, count($reflection->properties('@Id')));
        $this->assertEquals(2, count($reflection->properties('@Embed')));

        $tmp = new UserDocument;
        $tmp->userid = "foobar_" . uniqid(true);

        $this->assertEquals($reflection->property('_id')->get($tmp), $tmp->userid);
    }


    public function testNoId()
    {
        $conn = getConnection();
        $reflection = $conn->getReflection('NoId');

        $foo = new NoID;
        $foo->name = "foobar";
        $foo->x = clone $foo;
        $conn->save($foo);

        $id1 = $reflection->property('_id')->get($foo);
        $id2 = $reflection->property('@Id')->get($foo);
        $this->assertEquals($id1, $id2);

        $reflection->property('name')->set($foo, 'x');
        $reflection->property('name')->set($foo, 'x');

        $id1 = $reflection->property('name')->get($foo);
        $id2 = $reflection->property('name')->get($foo);
        $this->assertEquals($id1, $id2);
        $this->assertEquals($id1, 'x');

        $id1 = $reflection->property('x')->get($foo);
        $id2 = $reflection->property('x')->get($foo, true);
        $this->assertNotEquals($id1, $id2);
        $this->assertTrue($id1 instanceof NoID);
        $this->assertTrue(is_array($id2));
    }

    public function testAutocomplete()
    {
        $conn = getConnection();
        $this->assertEquals($conn->post->count(['$autocomplete' => 'cesar']), 0);
        $this->assertEquals($conn->post->count(['$autocomplete' => 'FOO']), 1);
    }

    public function testByArray()
    {
        $conn = getConnection();
        // test create
        $user = new UserDocument;
        $data = array(
            "username" => "crodas-" . rand(0, 0xfffff),
            "pass" =>  "foobar",
        );
        $conn->getCollection($user)->populateFromArray($user, $data);
        $this->assertEquals($user->username, $data['username']);
        $this->assertEquals($user->pass, $data['pass']);
        $conn->save($user);

        // test update
        $data = array(
            "username" => "crodas-" . rand(0, 0xfffff),
        );
        $conn->getCollection($user)->populateFromArray($user, $data);
        $this->assertEquals($user->username, $data['username']);
        $conn->save($user);

        $data = array(
            "title" => "foobar " . uniqid(true),
            "author" => array("_id" => (string)$user->userid),
            "author_ref" => array("_id" => (string)$user->userid),
            "author_id" =>  $user->userid,
        );

        $post = new PostDocument;
        $conn->getCollection($post)->populateFromArray($post, $data);
        $this->assertEquals($post->title, $data['title']);
        $conn->save($post);

        $this->assertTrue(!empty($post->author));
        $this->assertTrue(!empty($post->author_ref));

        $this->assertEquals($post->author->username, $user->username);
        $this->assertEquals($post->author->username, $post->author_ref->username);
    }

    /** 
     * @expectedException ActiveMongo2\Plugin\LockingException 
     */
    public function testLockingPlugin()
    {
        $conn = getConnection();

        $post = new Locked;
        $post->title = "xxx";
        $post->tags  = ["y"];
        $conn->save($post);
        
        /* Update from another instance */
        $npost = getConnection()->getCollection('locked')->findOne(['_id' => $post->id]);
        $npost->title = "yyy";

        $this->assertEquals($npost->__ol_version, $post->__ol_version);

        $conn->save($npost);

        $this->assertNotEquals($npost->__ol_version, $post->__ol_version);
        define('_EXPECTED_VERSION', $npost->__ol_version);

        /* expect the error! */
        $post->title  = "foobar post - yyy";
        $conn->save($post);
    }

    /** 
     * @dependsOn testLockingPlugin 
     */
    public function testCheckStateLockingPlugin()
    {
        $x = getConnection()->getCollection('locked')->findOne(['title' => 'yyy']);
        $this->assertEquals($x->__ol_version, _EXPECTED_VERSION);
    }

    /** 
     * @dependsOn testLockingPlugin 
     * @dependsOn testCheckStateLockingPlugin
     */
    public function testLockingNoConflicts()
    {
        $conn = getConnection();
        $x = $conn->getCollection('locked')->findOne(['title' => 'yyy']);
        $y = $conn->getCollection('locked')->findOne(['title' => 'yyy']);

        $x->title = "ttt";
        $y->tags  = ['yyy'];

        $conn->save($x);
        $conn->save($y);

        $new = $conn->getCollection('locked')->findOne(['title' => 'ttt']);
        $this->assertEquals($x->title, $new->title);
        $this->assertNotEquals($y->title, $new->title);
        $this->assertEquals($y->tags, $new->tags);
        $this->assertEquals($y->__ol_version, $new->__ol_version);
    }

    public function testFindAnModifyNotFound()
    {
        $conn = getConnection();
        $this->assertNull($conn->binarydoc->findAndModify(['_id' => uniqid(true)], ['$set' => ['foo' => 1]]));
    }
    
    public function testBinary()
    {
        $conn = getConnection();
        $doc  = new BinaryDoc;
        $doc->content = file_get_contents(__FILE__);
        $doc->base64  = base64_encode("cesar");
        $conn->save($doc);
        $doc2 = $conn->_binary->findOne();
        $this->assertEquals($doc->content, $doc2->content);
        $this->assertEquals($doc->base64, base64_encode("cesar"));
        $object = $conn->_binary->rawCollection()->findOne();
        $this->assertTrue($object['content'] instanceof MongoBinData);
        $this->assertTrue($object['base64'] instanceof MongoBinData);
    }

    public function testReferenceUpdateBug()
    {
        $conn = getConnection();
        $doc = new BinaryDoc;
        $doc->content = file_get_contents(__FILE__);
        $doc->base64  = base64_encode("cesar");
        $conn->save($doc);


        $ref = new ReferenceHub;
        $ref->ref = $doc;

        $y = $GLOBALS['x_updates'];
        $conn->save($ref);

        $this->assertEquals($y+1, $GLOBALS['x_updates']);

        $ref->ref = BinaryDoc::findOne(['_id' => $doc->id]);
        $conn->save($ref);
        $this->assertEquals($y+1, $GLOBALS['x_updates']);

        $doc = new BinaryDoc;
        $doc->content = file_get_contents(__FILE__);
        $doc->base64  = base64_encode("cesar");
        $conn->save($doc);

        $ref->ref = BinaryDoc::findOne(['_id' => $doc->id]);
        $conn->save($ref);
        $this->assertEquals($y+2, $GLOBALS['x_updates']);
    }

}
