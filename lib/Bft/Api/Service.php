<?php
/**
 * Bft Service API
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

require_once 'Bft.php';

class Bft_Api_Service
{

    protected $bft;
    
    public function __construct($bft=null)
    {
        if ($bft instanceof Bft) {
            $this->bft = $bft;
        }
        elseif ($bft === null) {
            $this->bft = Zend_Registry::get('bft');
        }
        if (!$this->bft) {
            throw new Exception('Unable to start BFT Api service - no Bft model found');
        }
    }
    
    public function getConfig()
    {
        return Zend_Registry::get('conf')->{BFT_API_ENV}->api->toArray();
    }

    public function get($id)
    {
        $entity = $this->bft->get($id);
        if ($entity === null) {
            throw new Ex_ResourceNotExists();
        }
        return $entity->toArray();
    }

    public function index($collectionName, $parentId=null)
    {
        $collection = $this->bft->collection($collectionName, $parentId);
        return $collection->getEntitiesArray();
    }
    
    // Returns id
    public function put($id, $data, $collections=array(), $parentId=null)
    {
        if (array_key_exists('id', $data) && $data['id'] != $id)
        {
            throw new Ex_BadData('Id in entity body does not match id in request URI');
        }
        $entity = $this->bft->entity($data);
        // Store the entity if it contains data
        if (!$entity->isEmpty()) {
            $this->bft->store($entity);
        }
        // Add the entity to each collection
        foreach ($collections as $ind => $name) {
            $collection = $this->bft->collection($name, $parentId);
            $added = $collection->add($entity);
            if ($added) {
                $subCollections = array_slice($collections, $ind+1);
                $collection->addSubCollections($subCollections);
            }
        }
        return $entity->id->str();
    }
    
    public function post($entity, $collections=null, $parentId=null)
    {
        // 1. Store the entity
        $entity = $this->bft->entity($entity);
        try {
            $this->bft->insert($entity);
        }
        catch (Exception $e) {
            if ($this->bft->exists($entity->id)) {
                throw new Ex_ResourceExists();
            }
            throw $e;
        }
        // 2. Add to collections
        foreach ($collections as $ind => $name) {
            $collection = $this->bft->collection($name, $parentId);
            $added = $collection->add($entity);
            if ($added) {
                $subCollections = array_slice($collections, $ind+1);
                $collection->addSubCollections($subCollections);
            }
        }
        return $entity->id->str();
    }
    
    public function delete($id, $collections=null, $parentId=null)
    {
        $id = new Bft_UUID($id);
        $numDeleted = 0;
        // 1. remove from top collection
        $last = $this->bft->collection(end($collections), $parentId);
        $numDeleted += $last->delete($id);
        
        // 2. remove from sub-collections
        $sub = $last->getSubCollections();
        foreach ($sub as $name) {
            $collection = $this->bft->collection($name, $parentId);
            $numDeleted += $collection->delete($id);
        }
        
        // 3. Remove the entity if no parent collections
        if (count($collections) < 2) {
            $numDeleted += $this->bft->delete($id);
        }
        return $numDeleted;
    }
}
