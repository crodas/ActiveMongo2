<?php
use ActiveMongo2\Tests\Document\PostDocument;
use ActiveMongo2\Tests\Document\UserDocument;

class EmbedReferenceTest extends \phpunit_framework_testcase
{
    protected $post;
    protected $user;

    public function testEmbedMany()
    {
        $conn = getConnection();
        $user = new UserDocument;
        $user->username = "crodas:" . uniqid();
        $user->pass     = "foobar";
        $conn->save($user);

        $this->assertNotEquals("foobar", $user->pass);
        $old_pass = $user->pass;
        $conn->save($user);
        $this->assertEquals($old_pass, $user->pass);

        $user->pass = "xxx";
        $conn->save($user);
        $this->assertNotEquals($old_pass, $user->pass);

        $post = new PostDocument;
        $post->author = $user;
        $post->author_id = $user->userid;
        $post->title = "some weird title";
        $conn->save($post);


        // check if $post->author is a reference
        $this->assertTrue($post->author instanceof \ActiveMongo2\Reference);
        // load the username
        $this->assertEquals($post->author->username, $user->username);
        // check that username didn't load the user from db
        $this->assertTrue(is_array($post->author->getReference()));

        $this->post = $post->id;
        $this->user = $user->userid;

        // test default behavior
        $this->assertEquals($post->readers_1, NULL);

        $post->readers_1[] = $user;
        $post->readers_1[] = $user;
        $conn->save($post);

        $zpost = $this->getPost();
        $zuser = $zpost->readers_1;
        $zuser[1]->visits = 99;
        $zuser[0]->visits = 499;
        $conn->save($zpost);

        // test AutoincrementBy
        $this->assertEquals(1, $zpost->post_by_user_id);

        // silly thing
        $zpost->author->runEvent = true;
        $zpost->post_by_user_id  = null;
        $conn->save($zpost);

        $this->assertEquals(2, $zpost->post_by_user_id);

        $zuser = $this->getPost()->readers_1;
        $this->assertNotEquals($zuser[0]->visits, $zuser[1]->visits);

                 
        $this->assertEquals(count($this->getPost()->readers_1), 2);
        unset($post->readers_1[0]);
        $conn->save($post);

        $this->assertEquals(count($this->getPost()->readers_1), 1);
        $this->assertEquals(array_keys($this->getPost()->readers_1), array(0));

        $conn->delete($post);
        $conn->delete($user);
    }

    public function testReferenceMany()
    {
        $conn = getConnection();
        $user = new UserDocument;
        $user->username = "crodas:" . uniqid();
        $conn->save($user);

        $post = new PostDocument;
        $post->author = $user;
        $post->author_id = $user->userid;
        $post->title = "some weird title";

        $conn->save($post);

        $this->post = $post->id;
        $this->user = $user->userid;

        // test default behavior
        $this->assertEquals($post->readers, NULL);
                 
        // add reference
        $post->readers[] = $user;
        $conn->save($post);
        $this->assertEquals(count($this->getPost()->readers), 1);

        
        // add another reference
        $post->readers[] = $user;
        $conn->save($post);

        // read from db
        $this->assertEquals(count($this->getPost()->readers), count($post->readers));
        $this->assertEquals(count($this->getPost()->readers), 2);

        // update things in the reference, it should update
        // the object which is referenced too
        $post->readers[1]->visits = 1024;
        $conn->save($post);
        $this->assertEquals($this->getUser()->visits, 1024);
        $this->assertEquals($this->getUser()->visits, $this->getPost()->readers[0]->visits);

        // remove one
        unset($post->readers[0]);
        $conn->save($post);
        $this->assertEquals(count($this->getPost()->readers), count($post->readers));
        $this->assertEquals(count($this->getPost()->readers), 1);
        $this->assertTrue(!empty($this->getPost()->readers[0]));
        $this->assertEquals(array_keys($this->getPost()->readers), array(0));

        //delete things
        $conn->delete($post);
        $conn->delete($user);
    }

    public function getPost()
    {
        return getConnection()->getCollection('post')->findOne(['_id' => $this->post]);
    }

    public function getUser()
    {
        return getConnection()->getCollection('user')->findOne(['_id' => $this->user]);
    }
}
