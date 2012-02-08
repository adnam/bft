<?php
/**
 * Exception class definitions
 * 
 * Heirarchy of exceptions loosely based on HTTPS status codes
 *
 * @category    library
 * @package     Bft
 * @subpackage  Api
 * @copyright   Copyright (c) 2010 Adam Hayward. ALL RIGHTS RESERVED
 * @author      Adam Hayward <adam@happy.cat>
 * @version     $Id$
**/
class Ex_ClientError extends Exception {
    protected $code = 400;
    protected $message = 'Bad Request';
}
class Ex_ResourceNotExists extends Ex_ClientError {
    protected $code = 404;
    protected $message = 'Resource does not exists';
}
class Ex_ResourceExists extends Ex_ClientError {
    protected $code = 409;
    protected $message = 'Resource already exists';
}
class Ex_BadData extends Ex_ClientError {
    protected $code = 400;
    protected $message = 'Bad input from client';
}
class Ex_InvalidId extends Ex_BadData {
    protected $code = 400;
    protected $message = 'Bad input from user';
}
class Ex_Forbidden extends Ex_BadData {
    protected $code = 403;
    protected $message = 'Forbidden';
}
class Ex_BadMethod extends Ex_ClientError {
    protected $code = 405;
    protected $message = 'Method not allowed';
}
