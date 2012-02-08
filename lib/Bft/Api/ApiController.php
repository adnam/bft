<?php
/**
 * Controller for Bft Service RestFul API
 *
 * Copyright (c) 2010, Adam Hayward <adam at happy dot cat>
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without 
 * modification, are permitted provided that the following conditions 
 * are met:
 * 
 * - Redistributions of source code must retain the above copyright 
 *   notice, this list of conditions and the following disclaimer.
 * - Redistributions in binary form must reproduce the above copyright 
 *   notice, this list of conditions and the following disclaimer in 
 *   the documentation and/or other materials provided with the distribution.
 * - Neither the name of the author nor the names of its contributors
 *   may be used to endorse or promote products derived from this software 
 *   without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED
 * TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
 * LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @copyright   Copyright (c) 2010 Adam Hayward ALL RIGHTS RESERVED
 * @author      Adam Hayward <adam at happy dot cat>
 * @license     http://svn.happy.cat/public/LICENSE.txt     New BSD License
 *
 * @category    api
 * @package     Bft
 * @copyright   Copyright (c) 2010 Adam Hayward ALL RIGHTS RESERVED
 * @author      Adam Hayward <adam@happy.cat>
 * @version     $Id$
**/

require_once 'Errors.php';
require_once 'Api/Service.php';
require_once 'Api/Uri.php';

class ApiController extends Zend_Controller_Action
{

    protected $svc;
    protected $uri;

    public function init()
    {
        $this->bft = Zend_Registry::get('bft');
        $this->svc = new Bft_Api_Service($this->bft);
        $this->getHelper('viewRenderer')->setNoRender();
        $this->uri = new Bft_Api_Uri($this->getRequest()->getPathInfo());
    }

    protected function isHome()
    {
        return $this->uri->getParts()===array();
    }

    protected function isList()
    {
        return $this->uri->isCollectionRequest();
    }

    protected function isEntityRequest()
    {
        return !$this->uri->isCollectionRequest();
    }

    public function dispatchAction()
    {
        $method = $this->getRequest()->getMethod();
        switch ($method) {
            case 'PUT':
                $this->doPut();
                break;
            case 'GET':
                $this->doGet();
                break;
            case 'POST':
                $this->doPost();
                break;
            case 'DELETE':
                $this->doDelete();
                break;
            default:
                throw new Ex_BadMethod();
        }
    }

    public function doGet()
    {
        if ($this->isHome()) {
            return $this->respond($this->svc->getConfig());
        }
        elseif ($this->isList()) {
            $name = $this->uri->getCollectionName();
            $id = $this->uri->getParentId();
            return $this->respond($this->svc->index($name, $id));
        }
        else {
            $entity = $this->svc->get($this->uri->getId());
            $this->respond($entity);
        }
    }
    
    public function doPut()
    {
        $data = Zend_Json::decode($this->getRequest()->getRawBody());
        $id = $this->uri->getId();
        $collections = $this->uri->getCollections();
        $parent = $this->uri->getParentId();
        $this->svc->put($id, $data, $collections);
        $this->respond();
    }
    
    public function doPost()
    {
        if ($this->isEntityRequest()) {
            // Can only post to collections
            throw new Ex_BadMethod();
        }
        $data = Zend_Json::decode($this->getRequest()->getRawBody());
        $collections = $this->uri->getCollections();
        $parentId = $this->uri->getParentId();
        $id = $this->svc->post($data, $collections, $parentId);
        $this->created($id);
    }
    
    public function doDelete()
    {
        $id = $this->uri->getId();
        $collections = $this->uri->getCollections();
        $parent = $this->uri->getParentId();
        $numDeleted = $this->svc->delete($id, $collections, $parent);
        if ($numDeleted === 0) {
            // Nothing was deleted, resourse was not found
            throw new Ex_ResourceNotExists();
        }
        $this->respond();
    }
    
    /*
    * OK response (HTTP 200 OK)
    */
    protected function respond($data=null)
    {
        $response = $this->getResponse();
        $response->setHeader('Content-type', 'application/json', true);
        $response->clearBody();
        if ($data === null) {
            $response->setRawHeader('HTTP/1.1 204 OK');
            $response->setHttpResponseCode(204);
        }
        else {
            $response->setRawHeader('HTTP/1.1 200 OK');
            $response->setHttpResponseCode(200);
            $response->setBody(Zend_Json::encode($data));
        }
    }
  
    protected function created($id)
    {
        $base = 'http://' . $_SERVER['HTTP_HOST'] . $this->getRequest()->getBasePath();
        if ($this->isList()) {
            $location = $base . $this->uri . '/' . $id;
        }
        else {
            $location = $base . $this->uri->getParentUrl() . '/' . $id;
        }
        $this->getResponse()->setHeader('location',$location, true);
        $this->getResponse()->setRawHeader('HTTP/1.1 201 Created');
        $this->getResponse()->setHttpResponseCode(201);
        $this->getResponse()->clearBody();
    }
}

