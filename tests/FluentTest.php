<?php

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
        );


        $this->assertEquals($query->GetQuery(), $expects);
        $this->assertEquals($query->getUpdate(), $update);
    }

    public function testOr()
    {
        $conn  = getconnection();
        $users = $conn->getcollection('user');
        $query = $users->query()
            ->field('username')->equals( 5 )
            ->addOr()
                ->field('xx')->equals(99)
                ->End()
            ->addOr()
                ->field('zxx')->equals(99)
                ->End()
            ->addAnd()
                ->field('yyy')->equals(38)
                ->end()
            ->addAnd()
                ->field('xxxx')->equals(210)
                ->end()
            ;

        $expected = array (
          'username' => 5,
          '$or' => 
          array (
            array (
              'xx' => 99,
            ),
            array (
              'zxx' => 99,
            ),
          ),
          '$and' => 
          array (
            array (
              'yyy' => 38,
            ),
            array (
              'xxxx' => 210,
            ),
          ),
        );

        $this->assertEquals($query->getQuery(), $expected);
    }
}

