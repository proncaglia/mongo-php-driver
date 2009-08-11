<?php
require_once 'PHPUnit/Framework.php';

/**
 * Test class for Mongo.
 * Generated by PHPUnit on 2009-04-09 at 18:09:02.
 */
class MongoObjDBTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var    Mongo
     * @access protected
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    public function setUp() {
      ini_set('mongo.objects', 1);
      $this->object = $this->sharedFixture->selectDB("phpunit");
    }

    public function tearDown() {
      ini_set('mongo.objects', 0);
    }

    public function testDBDrop() {
        $r = $this->object->drop();
        $this->assertEquals(1, $r->ok, json_encode($r));
    }

    public function testRepair() {
      $r = $this->object->repair();
      $this->assertEquals(1, $r->ok, json_encode($r));
    }

    public function testCreateCollection() {
        $ns = $this->object->selectCollection('system.namespaces');
        $this->object->drop('z');
        $this->object->drop('zz');
        $this->object->drop('zzz');

        $this->object->createCollection('z');
        $obj = $ns->findOne((object)array('name' => 'phpunit.z'));
        $this->assertNotNull($obj);

        $c = $this->object->createCollection('zz', true, 100);
        $obj = $ns->findOne((object)array('name' => 'phpunit.zz'));
        $this->assertNotNull($obj);

        for($i=0;$i<10;$i++) {
            $c->insert((object)array('x' => $i));
        }
        $this->assertLessThan(10, $c->count());

        $c = $this->object->createCollection('zzz', true, 1000, 5);
        $obj = $ns->findOne((object)array('name' => 'phpunit.zzz'));
        $this->assertNotNull($obj);

        for($i=0;$i<10;$i++) {
            $c->insert((object)array('x' => $i));
        }
        $this->assertEquals(5, $c->count());
    }
    
    public function testListCollections() {
        $ns = $this->object->selectCollection('system.namespaces');

        for($i=0;$i<10;$i++) {
            $c = $this->object->selectCollection("x$i");
            $c->insert((object)array("foo" => "bar"));
        }

        $list = $this->object->listCollections();
        for($i=0;$i<10;$i++) {
            $this->assertTrue($list[$i] instanceof MongoCollection);
            $this->assertTrue(in_array("phpunit.x$i", $list));
        }
    }
    

    public function testCreateDBRef() {
        $arr = (object)array('_id' => new MongoId());
        $ref = $this->object->createDBRef('foo.bar', $arr);
        $this->assertNotNull($ref);
        $this->assertTrue(is_object($ref));

        $arr = (object)array('_id' => 1);
        $ref = $this->object->createDBRef('foo.bar', $arr);
        $this->assertNotNull($ref);
        $this->assertTrue(is_object($ref));

        $ref = $this->object->createDBRef('foo.bar', new MongoId());
        $this->assertNotNull($ref);
        $this->assertTrue(is_object($ref));

        $id = new MongoId();
        $ref = $this->object->createDBRef('foo.bar', (object)array('_id' => $id, 'y' => 3));
        $this->assertNotNull($ref);
        $this->assertEquals((string)$id, (string)$ref->{'$id'});
    }

    public function testGetDBRef() {
        $c = $this->object->selectCollection('foo');
        $c->drop();
        for($i=0;$i<50;$i++) {
            $c->insert((object)array('x' => rand()));
        }
        $obj = $c->findOne();

        $ref = $this->object->createDBRef('foo', $obj);
        $obj2 = $this->object->getDBRef($ref);

        $this->assertNotNull($obj2);
        $this->assertEquals($obj->x, $obj2->x);
    }

    public function testExecute() {
        $ret = $this->object->execute('4+3*6');
        $this->assertEquals($ret->retval, 22);

        $ret = $this->object->execute(new MongoCode('function() { return x+y; }', (object)array('x' => 'hi', 'y' => 'bye')));
        $this->assertEquals($ret->retval, 'hibye');

        $ret = $this->object->execute(new MongoCode('function(x) { return x+y; }', (object)array('y' => 'bye')), array('bye'));
        $this->assertEquals($ret->retval, 'byebye');
    }

    public function testDBCommand() {
        $x = $this->object->command((object)array());
        $this->assertEquals($x->errmsg, "no such cmd");
        $this->assertEquals($x->ok, 0);

        $this->object->command((object)array('profile' => 0));
        $x = $this->object->command((object)array('profile' => 1));
        $this->assertEquals($x->was, 0, json_encode($x));
        $this->assertEquals($x->ok, 1);
    }

    public function testCreateRef() {
        $ref = MongoDBRef::create("x", "y");
        $this->assertEquals('x', $ref->{'$ref'});
        $this->assertEquals('y', $ref->{'$id'});
    }

    public function testIsRef() {
        $this->assertFalse(MongoDBRef::isRef((object)array()));
        $this->assertFalse(MongoDBRef::isRef((object)array('$ns' => 'foo', '$id' => 'bar')));

        $ref = (object)array('$ref' => 'blog.posts', '$id' => new MongoId('cb37544b9dc71e4ac3116c00'));
        $this->assertTrue(MongoDBRef::isRef($ref));
    }
}
?>