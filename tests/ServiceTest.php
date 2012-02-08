<?php

require_once 'Zend/Config/Ini.php';

require_once dirname(__file__) . '/GenericTestCase.php';
require_once 'Uuid.php';
require_once 'Entity.php';
require_once 'Bft.php';
require_once 'Api/Service.php';

class Bft_Api_ServiceTest extends GenericTestCase
{
    // Instance of Bft
    var $bft;
    // INstance of Bft_Api_Service
    var $svc;
    
    var $baseCollection;

    public function setUp()
    {
        $config = new Zend_Config_Ini(dirname(__file__) . '/database.test.ini');
        $this->bft = new Bft($config->test);
        $this->svc = new Bft_Api_Service($this->bft);
        $this->baseCollection = 'basecollection_' . mt_rand();
    }
    
    public function tearDown()
    {
        $this->bft->collection($this->baseCollection, null)->eraseState();
    }
    
    public function testService1()
    {
        $widgets = $this->svc->index($this->baseCollection);
        // There should not be any entities inside the collection
        $this->assertEquals($widgets, array());
        $widget1 = array(
            'name' => 'My Widget'
        );
        $widget2 = array(
            'name' => 'Your Widget'
        );
        $collections = array($this->baseCollection);
        // Create first widget
        $id1 = $this->svc->post($widget1, $collections);
        // Should return a UUID
        $this->assertTrue(Bft_UUID::isUUID($id1));
        $newwidget1 = array(
            'id' => $id1,
            'name' => 'My Widget'
        );
        $widgets = $this->svc->index($this->baseCollection);
        // Entity should be returned in collection
        $this->assertEquals($widgets, array($newwidget1));
        
        $id2 = $this->svc->post($widget2, $collections);
        $newwidget2 = array(
            'id' => $id2,
            'name' => 'Your Widget'
        );
        $widgets = $this->svc->index($this->baseCollection);
        $this->assertEquals(count($widgets), 2);
        if ($widgets[0]['id'] == $id2) {
            $this->assertEquals($widgets[0], $newwidget2);
            $this->assertEquals($widgets[1], $newwidget1);
        }
        else {
            $this->assertEquals($widgets[0], $newwidget1);
            $this->assertEquals($widgets[1], $newwidget2);
        }
        
        $collections[] = $collections[0] . '-cool_widgets';
        $widget3 = array(
            'name' => 'The Third Widget'
        );
        $id3 = $this->svc->post($widget3, $collections);
        $newwidget3 = array(
            'id' => $id3,
            'name' => 'The Third Widget'
        );
        $baseWidgets = $this->svc->index($collections[0]);
        $coolWidgets = $this->svc->index($collections[1]);
        $this->assertEquals(count($baseWidgets), 3);
        $this->assertEquals(count($coolWidgets), 1);
        
        $this->assertEquals($this->svc->get($id1), $newwidget1);
        $this->assertEquals($this->svc->get($id2), $newwidget2);
        $this->assertEquals($this->svc->get($id3), $newwidget3);
        
        $widget3b = array(
            'id' => $id3,
            'name' => 'The Third Widget',
            'dispatched' => true
        );
        $result = $this->svc->put($id3, $widget3b, $collections);
        $coolWidgets = $this->svc->index($collections[1]);
        $this->assertEquals($coolWidgets[0], $widget3b);
        
        $result = $this->svc->delete($id3, $collections);
        $this->assertEquals($result, 1);
        
        $baseWidgets = $this->svc->index($collections[0]);
        $this->assertEquals(count($baseWidgets), 3);
        
        $result = $this->svc->delete($id3, array($collections[0]));
        $baseWidgets = $this->svc->index($collections[0]);
        $this->assertEquals(count($baseWidgets), 2);
        
        $result = $this->svc->delete($id2, array($collections[0]));
        $baseWidgets = $this->svc->index($collections[0]);
        $this->assertEquals(count($baseWidgets), 1);
        
        $result = $this->svc->delete($id1, array($collections[0]));
        $baseWidgets = $this->svc->index($collections[0]);
        $this->assertEquals(count($baseWidgets), 0);
    }

    public function _testService2()
    {
        // There should not be any entities inside the collection
        $widgets = $this->svc->index($this->baseCollection);
        $this->assertEquals($widgets, array());
        
        $widget1 = array('name' => 'My Widget');
        $widget2 = array('name' => 'Your Widget');
        
        // Create widgets in a parent- and sub-collection
        $id1 = $this->svc->post($widget1, array($this->baseCollection));
        $id2 = $this->svc->post($widget2, array($this->baseCollection, $this->baseCollection.'-subcollection'));
        $newwidget1 = array('id' => $id1, 'name' => 'My Widget');
        $newwidget2 = array('id' => $id2, 'name' => 'Your Widget');
        
        // Parent collection should contain both
        $allwidgets = $this->svc->index($this->baseCollection);
        $this->assertEquals(count($allwidgets), 2);
        
        // Sub collection should contain only one
        $subwidgets = $this->svc->index($this->baseCollection.'-subcollection');
        $this->assertEquals(count($subwidgets), 1);
        $this->assertEquals($subwidgets[0], $newwidget2);

        // Deleting a widget in the base collection, deletes it in sub collections
        $result = $this->svc->delete($id2, array($this->baseCollection));
        $allwidgets = $this->svc->index($this->baseCollection);
        $subwidgets = $this->svc->index($this->baseCollection.'-subcollection');
        $this->assertEquals(count($allwidgets), 1);
        $this->assertEquals(count($subwidgets), 0);
        $this->assertEquals($allwidgets[0], $newwidget1);
        
        $result = $this->svc->delete($id1, array($this->baseCollection));
        $allwidgets = $this->svc->index($this->baseCollection);
        $subwidgets = $this->svc->index($this->baseCollection.'-subcollection');
        $this->assertEquals(count($allwidgets), 0);
        $this->assertEquals(count($subwidgets), 0);
    }
}
