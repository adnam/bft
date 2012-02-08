<?php

require_once 'Zend/Config/Ini.php';

require_once dirname(__file__) . '/GenericTestCase.php';
require_once 'Uuid.php';
require_once 'Entity.php';
require_once 'Bft.php';
require_once 'Collection.php';

class Bft_CollectionTest extends GenericTestCase
{
    
    // Bft Instance
    var $bft;

    public function setUp()
    {
        $config = new Zend_Config_Ini(dirname(__file__) . '/database.test.ini');
        $this->bft = new Bft($config->test);
    }
    
    public function testCollection()
    {
        $albumInfo = array(
            'name' => 'Holiday photos',
            'date' => '1979-01-02',
        );
        $album = $this->bft->store($albumInfo);
        $albumPhotos = $this->bft->collection('photos', $album);
        $photo1 = $this->bft->entity(array(
            'title' => 'Photo 1',
            'timestamp' => time()
        ));
        $photo2 = $this->bft->entity(array(
            'title' => 'Photo 2',
            'timestamp' => time()
        ));
        $albumPhotos->add($photo1);
        $albumPhotos->add($photo2);
        $all = $albumPhotos->getEntities();
        $this->assertEquals(count($all), 2);
    }

}

