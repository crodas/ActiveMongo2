<?php
use ActiveMongo2\Tests\Document\UserDocument;

class FluentTest extends \phpunit_framework_testcase
{
    public function testQuery()
    {
        $conn  = getconnection();
        $users = $conn->getcollection('user');
        $query = $users->query()
            ->field('username')->equals( 5 )
            ->field('something')->range(5, 99)
            ->field('foobar')->greaterthan(99);

        $expects = array(
            'username' => 5,
            'something' => array('$gte' => 5, '$lte' => 99),
            'foobar' => array('$gt' => 99)
        );

        $this->assertEquals($query->GetQuery(), $expects);
        $this->assertNull($query->getUpdate());
    }

    public function testUpdate()
    {
        $conn  = getconnection();
        $users = $conn->getcollection('user');
        $query = $users->query()
            ->field('username')->equals( 5 )
            ->field('something')->range(5, 99)
            ->field('foobar')->greaterthan(99)
            ->field('visits')->inc()
            ->field('xxx')->set(1)
            ->field('zzy')->push(1)
            ->field('zzz')->push(array(1,2,3))
            ->field('www')->addToSet(array(1,2,3))
            ->field('yyy')->unsetField();

        $expects = array(
            'username' => 5,
            'something' => array('$gte' => 5, '$lte' => 99),
            'foobar' => array('$gt' => 99)
        );

        $update = array(
            '$inc' => array('visits' => 1),
            '$set' => array('xxx' => 1),
            '$unset' => array('yyy' => 1),
            '$push'  => array('zzy' => 1, 'zzz' => array('$each' => array(1,2,3))),
            '$addToSet'  => array('www' => array('$each' => array(1,2,3))),
        );


        $this->assertEquals($query->GetQuery(), $expects);
        $this->assertEquals($query->getUpdate(), $update);
    }

    public function testOr()
    {
        $conn  = getconnection();
        $user = new UserDocument;
        $user->username = "5";
        $conn->save($user);

        $users = $conn->getcollection('user');
        $query = $users->query()
            ->field('username')->equals( "5" )
            ->addOr()
                ->field('username')->equals("99")
                ->End()
            ->addOr()
                ->field('visits')->equals(0)
                ->End()
            ->addAnd()
                ->field('username')->equals("5")
                ->end()
            ->addAnd()
                ->field('visits')->equals(0)
                ->end()
            ;

        $expected = array (
          'username' => "5",
          '$or' => 
          array (
            array (
              'username' => "99",
            ),
            array (
              'visits' => 0,
            ),
          ),
          '$and' => 
          array (
            array (
              'username' => "5",
            ),
            array (
              'visits' => 0,
            ),
          ),
        );

        $this->assertEquals($query->count(), 1);
        $this->assertEquals($query->getQuery(), $expected);
    }
}

