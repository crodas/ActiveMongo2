<?php
use ActiveMongo2\Tests\Document\PostDocument;
use ActiveMongo2\Tests\Document\UserDocument;

class DeferredTest extends \phpunit_framework_testcase
{
    public function testDeferredReferences()
    {
        $conn = getConnection(true);
        $user = new UserDocument;
        $user->username = "crodas:" . uniqid();
        $conn->save($user);

        $post = new PostDocument;
        $post->author = $user;
        $post->author_ref = $user;
        $post->author_refs[] = $user;
        $post->author_refs[] = $user;
        $post->author_id = $user->userid;
        $post->title = "some weird title";

        $conn->save($post);


        $user->visits = 1024;
        $conn->save($user);

        $this->post = $post->id;
        $this->user = $user->userid;
        
        $this->assertNotEquals($this->getUser()->visits, $this->getPost()->author_ref->visits);
        $this->assertNotEquals($this->getUser()->visits, $this->getPost()->author_refs[0]->visits);

        $done = getConnection()->worker(false);
        $this->assertEquals(3, $done);

        $this->assertEquals($this->getUser()->visits, $this->getPost()->author_ref->visits);
        $this->assertEquals($this->getUser()->visits, $this->getPost()->author_refs[0]->visits);

        $conn->delete($this->getUser());
        $conn->delete($post);
    }

    public function getPost()
    {
        return getConnection()->getCollection('post')->findOne(['_id' => $this->post]);
    }

    public function getUser()
    {
        return getConnection()->getCollection('user')->getById($this->user);
    }

}
