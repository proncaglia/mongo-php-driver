<?php
require_once 'PHPUnit/Framework.php';

/**
 * Test class for Mongo.
 * Generated by PHPUnit on 2009-04-09 at 18:09:02.
 */
class MinMaxKeyTest extends PHPUnit_Framework_TestCase
{
    protected $object;

    public function setUp() {
      $this->object = $this->sharedFixture->selectCollection("phpunit", "minmax");
      $this->object->drop();
    }


    public function testMin() {
      $this->object->insert(array("x" => new MongoMinKey));
      $x = $this->object->findOne();
      $this->assertTrue($x['x'] instanceof MongoMinKey);
    }

    public function testMax() {
      $this->object->insert(array("x" => new MongoMaxKey));
      $x = $this->object->findOne();
      $this->assertTrue($x['x'] instanceof MongoMaxKey);
    }

    public function testMinMax() {
      $this->object->insert(array("x" => 3));
      $this->object->insert(array("x" => 2.9));
      $this->object->insert(array("x" => new MongoDate()));
      $this->object->insert(array("x" => true));
      $this->object->insert(array("x" => null));
      $this->object->insert(array("x" => new MongoMaxKey()));
      $this->object->insert(array("x" => new MongoMinKey()));

      $cursor = $this->object->find()->sort(array("x" => 1));

      $obj = $cursor->getNext();
      $this->assertTrue($obj['x'] instanceof MongoMinKey, json_encode($obj));

      $obj = $cursor->getNext();
      $this->assertEquals(null, $obj['x'], json_encode($obj));

      $obj = $cursor->getNext();
      $this->assertEquals(2.9, $obj['x'], json_encode($obj));

      $obj = $cursor->getNext();
      $this->assertEquals(3, $obj['x'], json_encode($obj));

      $obj = $cursor->getNext();
      $this->assertEquals(true, $obj['x'], json_encode($obj));

      $obj = $cursor->getNext();
      $this->assertTrue($obj['x'] instanceof MongoDate, json_encode($obj));

      $obj = $cursor->getNext();
      $this->assertTrue($obj['x'] instanceof MongoMaxKey, json_encode($obj));
    }


}

?>