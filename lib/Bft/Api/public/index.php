<?php

// Define path to root directory
defined('BFT_DIR') || define('BFT_DIR', dirname(dirname(dirname(__FILE__))));

// Define path to application directory
defined('BFT_API_PATH') || define('BFT_API_PATH', BFT_DIR . DIRECTORY_SEPARATOR . 'Api');

// Define application environment
defined('BFT_API_ENV') || define('BFT_API_ENV', (getenv('BFT_API_ENV') ? getenv('BFT_API_ENV') : 'base'));

// Ensure library/ and Zend libraries are on include_path
set_include_path(implode(PATH_SEPARATOR, array(BFT_DIR, get_include_path())));

/** Zend_Application */
require_once 'Zend/Application.php';

// Create application, bootstrap, and run
$application = new Zend_Application(BFT_API_ENV, BFT_API_PATH . '/config.ini');
$application->bootstrap()->run();
