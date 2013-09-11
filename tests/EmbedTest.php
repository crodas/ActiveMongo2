<?php
use ActiveMongo2\Tests\Document\PostDocument;
use ActiveMongo2\Tests\Document\UserDocument;

class EmbedTest extends \phpunit_framework_testcase
{
    protected $post;
    protected $user;

    public function testReferenceMany()
    {
        $conn = getConnection();
        $user = new UserDocument;
        $user->username = "crodas:" . uniqid();
        $conn->save($user);

        $post = new PostDocument;
        $post->author = $user;
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
        $this->assertEquals($this->getUser()->visits, $post->readers[0]->visits);

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
