<?php
use ActiveMongo2\Tests\Document\UserDocument;

class FluentTest extends \phpunit_framework_testcase
{
    public function testQueryWithSize()
    {
        $conn  = getconnection();
        $users = $conn->getcollection('user');
        $query = $users->query()
            ->field('username')->size( '5' ) 
            ->field('username')->exists(1);

        $expects = array(
            'username' => array('$size' => 5, '$exists' => true),
        );

        $this->assertEquals($query->GetQuery(), $expects);
        $this->assertNull($query->getUpdate());
    }
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
            ->field('afoo')->addToSet(1)
            ->field('yy')->pull(1)
            ->field('xxxx')->pull([1,2])
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
            '$pull' => array('yy' => 1),
            '$pullAll' => array('xxxx' => array(1,2)),
            '$addToSet'  => array(
                'afoo' => 1,
                'www' => array('$each' => array(1,2,3))
            ),
        );


        $this->assertEquals($query->GetQuery(), $expects);
        $this->assertEquals($query->getUpdate(), $update);

        $result = $query->execute();
        $this->assertTrue(!$result['updatedExisting'] || $result['nModified'] == 0);
        $this->assertEquals(0, $result['n']);

        $conn->getcollection('user')->drop();
        for ($i=0; $i < 50; $i++) {
            $u = new UserDocument;
            $u->username = uniqid();
            $conn->save($u);
        }

        $result = $conn->getcollection('user')
            ->query()
            ->field('foobar')->set(5)
            ->execute();

        $this->assertTrue($result['updatedExisting']);
        $this->assertEquals($i, $result['n']);

        $result = $conn->getcollection('user')
            ->query()
            ->addOr()
                ->field('foobar')->eq(5)
            ->execute();
        $this->assertEquals($result->count(), 50);
    }

    public function testOr()
    {
        $conn  = getconnection();
        $user = new UserDocument;
        $user->username = "5";
        $conn->save($user);

        $users = $conn->getcollection('user');
        $query = $users->query()
            ->field('username')->not()->greaterThan( "54" )
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
          'username' => array('$not' => array('$gt' => "54")),
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

        $this->assertEquals($query->getQuery(), $expected);
        $this->assertEquals($query->count(), 1);
    }

    public function testMissingEndOnCount()
    {
        $conn  = getconnection();
        $users = $conn->getcollection('user');
        $query = $users->query()
            ->field('username')->not()->greaterThan( "54" )
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
            ;

        $expected = array (
          'username' => array('$not' => array('$gt' => "54")),
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

    public function testPull()
    {
        $conn  = getconnection();
        $users = $conn->getcollection('user');
        $query = $users->query()
            ->field('foobar')->pullExpr()->greaterThan(5);
        $update = array('$pull' => array (
            'foobar' => array (
                '$gt' => 5,
            ),
        ));

        $this->assertEquals($query->getUpdate(), $update);
        $query = $users->query()
            ->field('foobar')->pull(5);
        $update = array('$pull' => array (
            'foobar' => 5
        ));
        $this->assertEquals($query->getUpdate(), $update);

        $this->assertEquals($query->getUpdate(), $update);

        $query = $users->query()
            ->field('foobar')->pull(array(5, 6));
        $update = array('$pullAll' => array (
            'foobar' => array(5, 6)
        ));
        $this->assertEquals($query->getUpdate(), $update);
    }

    public function testFirst()
    {
        $conn  = getconnection();
        $users = $conn->getcollection('user');
        $user = $users->query()->first();
        $this->assertTrue($user instanceof UserDocument);
        $user = $users->query()->addAnd()->field('_id')->eq(1)->first();
        $this->assertNull($user);
        $user = $users->query()->where('_id', '==', 1)->first();
        $this->assertNull($user);
    }

    /**
     *  @dependsOn testFirst
     */
    public function testIterator()
    {
        $conn  = getconnection();
        $users = $conn->getcollection('user')->query();
        $i     = 0;
        foreach ($users as $user) {
            $this->assertTrue($user instanceof UserDocument);
            $i++;
        }
        $this->assertTrue($i > 0);
    }

    /**
     *  @expectedException RuntimeException
     */
    public function testFirstInvalidCall()
    {
        $conn  = getconnection();
        $users = $conn->getcollection('user');
        $user = $users->query()->field('foo')->set(99)->first();
    }

    /**
     *  @expectedException RuntimeException
     */
    public function testInvalidRemove()
    {
        $conn  = getconnection();
        $users = $conn->getcollection('user');
        $users->query()
            ->field('foo')->set(5)
            ->remove();
    }

    public function testGetIterator()
    {
        $conn  = getconnection();
        $users = $conn->getcollection('user');
        $q = $users->query()
            ->addOr()
                ->field('_id')->eq(5)
            ->addOr()
                ->field('xxxa')->eq(99)
            ->getIterator();
        $this->assertTrue($q instanceof ActiveMongo2\Cursor\Cursor);
    }

    /**
     *  @expectedException RuntimeException
     */
    public function testInvalidOp4()
    {
        $conn  = getconnection();
        $users = $conn->getcollection('user');
        $total = $users->query()
            ->where('foo', 'bar', 5);
    }

    /**
     *  @expectedException RuntimeException
     */
    public function testInvalidOp3()
    {
        $conn  = getconnection();
        $users = $conn->getcollection('user');
        $total = $users->query()
            ->AddOr()
                ->field('foo')->eq(5)
                ->end()
                ->end();
    }

    /**
     *  @expectedException RuntimeException
     */
    public function testInvalidOp2()
    {
        $conn  = getconnection();
        $users = $conn->getcollection('user');
        $total = $users->query()->set(5);
    }

    /**
     *  @expectedException RuntimeException
     */
    public function testInvalidOp1()
    {
        $conn  = getconnection();
        $users = $conn->getcollection('user');
        $total = $users->query()->eq(5);
    }

    /**
     *  @expectedException RuntimeException
     */
    public function testInvalidOp()
    {
        $conn  = getconnection();
        $users = $conn->getcollection('user');
        $total = $users->query()->foobar();
    }

    public function testRemove()
    {
        $conn  = getconnection();
        $users = $conn->getcollection('user');
        $total = $users->query()->count();
        $this->assertTrue($total > 0);

        $users->query()->remove();

        $total = $users->query()->count();
        $this->assertEquals($total, 0);
    }
}

