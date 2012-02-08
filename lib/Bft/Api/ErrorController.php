<?php

require_once 'Zend/Controller/Action.php';
require_once 'Errors.php';

class ErrorController extends Zend_Controller_Action
{
    public function init()
    {
        parent::init();
        $this->getHelper('viewRenderer')->setNoRender();
    }

    public function errorAction()
    {
        $errors = $this->_getParam('error_handler');
        $type = get_class($errors->exception);
        $message = $errors->exception->getMessage();
        $code = $errors->exception->getCode();
        switch (true) {
            case ($type === 'Zend_Json_Exception'):
                $this->respond($message, '400');
                break;
            case ($errors->exception instanceof Ex_ClientError):
                $this->respond($message, $code);
                break;
            default:
                $this->respond($errors->exception, '500');
                break;
        }
    }
    
    public function respond($message, $code, $data=null)
    {
        $response = $this->getResponse();
        $response->setHeader('Content-type', 'application/json', true);
        $response->clearBody();
        if ($data === null) {
            $data = array('error' => $message);
        }
        $response->setHttpResponseCode($code);
        $response->setBody(Zend_Json::encode($data) . "\n");
    }

}
