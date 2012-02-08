<?php
/**
 * Bootstrap script
 * 
 * initiates the configuration, logging, routes and database
 *
 * @category    library
 * @package     Bft
 * @subpackage  Api
 * @copyright   Copyright (c) 2010 Adam Hayward. ALL RIGHTS RESERVED
 * @author      Adam Hayward <adam@happy.cat>
 * @version     $Id$
**/

require_once 'Zend/Application/Bootstrap/Bootstrap.php';
require_once 'Bft.php';

class Bft_Api_Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    protected function _initConfig()
    {
        $config = new Zend_Config_Ini(dirname(__file__) . '/config.ini');
        Zend_Registry::set ('conf', $config);
    }
    
    protected function _initRoutes()
    {
        // API Route
        $front = Zend_Controller_Front::getInstance();
        $front->setControllerDirectory(dirname(__file__));
        $api = new Zend_Controller_Router_Route('*',
        array('controller' => 'api',
              'action' => 'dispatch'));
        $front->getRouter()->addRoute('api', $api);
    }
    
    /*
    * Database Initialization
    *
    * @return void
    */
    protected function _initBft()
    {
        $config = Zend_Registry::get('conf')->{BFT_API_ENV}->bft;
        Zend_Registry::set('bft', new Bft($config));
    }

}
