<?php

require_once 'Zend/Config/Ini.php';

require_once dirname(__file__) . '/GenericTestCase.php';
require_once 'Uuid.php';
require_once 'Entity.php';
require_once 'Bft.php';

class BftTest extends GenericTestCase
{
    
    // Instance of BigFatTable
    var $bft;
    
    public function setUp()
    {
        $config = new Zend_Config_Ini(dirname(__file__) . '/database.test.ini');
        $this->bft = new Bft($config->test);
    }
    
    public function testInstantiateEntity()
    {
        // Creation with array
        $data = array('thing' => 'car', 'wheels' => 4);
        try {
            $entity = new Bft_Entity($data);
        }
        catch (Exception $e) {
            $this->fail("Should be able to create entity data array");
        }

        // Creation with JSON string
        $json = json_encode($data);
        try {
            $entity = new Bft_Entity($json);
        }
        catch (Exception $e) {
            $this->fail("Should be able to create entity with JSON");
        }
        
        // Creation with invalid JSON string 1
        $json = 'invalid json';
        try {
            $entity = new Bft_Entity($json);
            $this->fail("Should NOT be able to create entity with invalid JSON");
        }
        catch (Exception $e) {} // OK
        
        // Creation with invalid JSON string 2
        $json = '{ invalid }';
        try {
            $entity = new Bft_Entity($json);
            $this->fail("Should NOT be able to create entity with invalid JSON");
        }
        catch (Exception $e) {} // OK

        // Creation with valid id
        $id = new Bft_UUID();
        try {
            $entity = new Bft_Entity(array('id' => $id->__toString()));
        }
        catch (Exception $e) {
            $this->fail("Should be able to create entity with a valid UUID 'id' field");
        }
        $this->assertEquals($id->__toString(), $entity->id->__toString());

        // Creation with valid id object
        $id = new Bft_UUID();
        try {
            $entity = new Bft_Entity(array('id' => $id));
        }
        catch (Exception $e) {
            $this->fail("Should be able to create entity with a valid UUID 'id' field");
        }
        $this->assertEquals($id, $entity->id);

        // Creation with INVALID id
        $id = new Bft_UUID();
        try {
            $entity = new Bft_Entity(array('id' => 'invalid-id-string'));
            $this->fail("Should NOT be able to create entity with an invalid 'id' field");
        }
        catch (Exception $e) {} // OK
    }

    public function testCRUD()
    {

        // Create a new Entity object
        $data = array(
            'one' => 'hello',
            'two' => 'bla bla bla'
        );
        $entity = $this->bft->entity($data);
        $uuid = $entity->id->__toString();
        
        // Entity has not been stored -> does not "exist"
        $this->assertFalse($this->bft->exists($entity->id));

        // After creation, data contains 'id' with UUID
        $expected = array(
            'id' => $uuid,
            'one' => 'hello',
            'two' => 'bla bla bla',
        );
        $this->assertEquals($entity->toArray(), $expected);
        $this->bft->store($entity);
        
        // Should now exist in DB
        $this->assertTrue($this->bft->exists($entity->id));
        $this->assertTrue($this->bft->exists($uuid));

        // Same data after storing
        $this->assertEquals($entity->toArray(), $expected);
        
        // Test that setter works
        $entity->foo = "lalala";
        $entity->two = "hahaha";
        $expected = array(
            'id' => $uuid,
            'one' => 'hello',
            'two' => 'hahaha',
            'foo' => 'lalala'
        );
        
        // Test getter works
        $this->assertEquals($entity->one, 'hello');
        $this->assertEquals($entity->two, 'hahaha');
        $this->assertEquals($entity->foo, 'lalala');

        // Test that you can't change the id
        try {
            $this->id = new Bft_UUID();
            $this->fail("You shouldn't be able to change the ID of an entity");
        }
        catch (Exception $e) {} // OK

        // Test the toArray() method
        $this->assertEquals($entity->toArray(), $expected);
        $this->bft->store($entity);
        $this->assertEquals($entity->toArray(), $expected);
        $stored = $this->bft->get($entity->id);
        $this->assertEquals($stored->toArray(), $expected);

        // Test the equals() method
        $this->assertTrue($stored->equals($entity));
        $this->assertTrue($entity->equals($stored));

        // Test deletion
        $this->bft->delete($entity->id);
        $this->assertFalse($this->bft->exists($entity->id));
        $this->assertFalse($this->bft->exists($stored->id));
        $this->assertFalse($this->bft->exists($uuid)); // UUID passed as string
        
    }

    public function testEntityManipulation()
    {
        $entity = new Bft_Entity(array(
            'isbn' => '978-0596006624',
            'instock' => true,
        ));
        
        $entity->instock = false;
        $expected = array(
            'id' => $entity->id->__toString(),
            'isbn' => '978-0596006624',
            'instock' => false,
        );
        $this->assertEquals($entity->toArray(), $expected);

        $entity->set(array(
            'timesread' => 100,
            'recommended' => true
        ));
        $expected = array(
            'id' => $entity->id->__toString(),
            'isbn' => '978-0596006624',
            'instock' => false,
            'timesread' => 100,
            'recommended' => true
        );
        $this->assertEquals($entity->toArray(), $expected);
    }

}
