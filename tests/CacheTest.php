<?php
use ActiveMongo2\Tests\Document\PostDocument;
use ActiveMongo2\Tests\Document\UserDocument;

class CacheReferenceTest extends \phpunit_framework_testcase
{
    public function testReferenceMany()
    {
        $conn = getConnection(true);
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

        // read from db (we never save duplicate references)
        $this->assertEquals(count($this->getPost()->readers), 1);

        // update things in the reference, it should update
        // the object which is referenced too
        $post->readers[1]->visits = 1024;
        $conn->save($post);
        $this->assertEquals($this->getUser()->visits, 1024);
        $conn->delete($this->getUser());
        $conn->delete($post);
    }

    public function getPost()
    {
        return getConnection(true)->getCollection('post')->findOne(['_id' => $this->post]);
    }

    public function getUser()
    {
        return getConnection(true)->getCollection('user')->getById($this->user);
    }

}
