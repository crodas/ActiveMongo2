<?php

use ActiveMongo2\Configuration;

class ConfigurationTest extends phpunit_framework_testcase
{
    public function testWriteConcern()
    {
        $x = new Configuration;
        $x->setWriteConcern(3);
        $this->assertEquals(3, $x->getWriteConcern());
    }

    public function testSetNamespace()
    {
        $x = new Configuration;
        $this->AssertEquals($x, $x->setnamespace('foobar'));
        $this->AssertEquals('foobar', $x->getNamespace());
    }

    public function testfailOnMissingReference()
    {
        $x = new Configuration;
        $this->AssertEquals($x, $x->failOnMissingReference(false));
        $this->AssertEquals(false, $x->failOnMissingReference());
        $this->AssertEquals($x, $x->failOnMissingReference(true));
        $this->AssertEquals(true, $x->failOnMissingReference());
    }
}
