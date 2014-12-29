<?php

class zfinalizeTest extends phpunit_framework_testcase
{
    protected function doTest(Array $cols1, $cols2)
    {
        foreach (['cols1', 'cols2'] as $zcol) {
            foreach ($$zcol as $id => $col) {
                $parts = explode(".", $col);
                ${$zcol}[$id] = end($parts);
            }
        }

        $this->AssertEquals(array_intersect($cols1, $cols2), array());
        $this->AssertEquals(array_intersect($cols2, $cols1), array());
    }

    public function testConnections()
    {
        $client = new MongoClient;
        $db1 = $client->selectDB('activemongo2_tests');
        $db2 = $client->selectDB('activemongo2_tests_foobar');
        $cols1 = array_map(function($a) { return (string)$a;}, $db1->listCollections());
        $cols2 = array_map(function($a) { return (string)$a;}, $db2->listCollections());


        $this->doTest($cols1, $cols2);
        $this->doTest($cols2, $cols1);
    }
}

