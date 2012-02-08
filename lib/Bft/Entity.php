<?php
/**
 * Bft Entity
 *
 * Copyright (c) 2008, Adam Hayward <adam at happy dot cat>
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
 */

require_once 'Zend/Db/Table/Row/Abstract.php';
require_once 'Uuid.php';

class Bft_Entity
{
    protected $_id;
    protected $_data;
    protected $_bft;
    protected $_new = true;

    public function __construct($data=null, $id=null)
    {
        if ($data === null) {
            $this->_data = array();
        }
        elseif (is_string($data)) { // Assume it's a JSON string
            $this->_data = $this->unserialize($data);
            if ($this->_data === null) {
                throw new Exception('Cannot create entity: invalid JSON string');
            }
            if ($id instanceof Bft_UUID) {
                $this->_id = $id;
            }
        }
        elseif (is_array($data)) {
            $this->_data = $data;
        }
        else {
            throw new Exception('Entity must be instantiated with a JSON string, an array or data or null.');
        }
        if (array_key_exists('id', $this->_data)) {
            if ($this->_data['id'] instanceof Bft_UUID) {
                $this->_id = $this->_data['id'];
                $this->_data['id'] = $this->_data['id']->__toString();
            }
            else {
                $this->_id = new Bft_UUID($this->_data['id']);
            }
            $this->_new = false;
        }
        else {
            $this->_id = new Bft_UUID();
            $this->_data = array_merge(array('id' => $this->_id->__toString()), $this->_data);
        }
    }

    public function equals(Bft_Entity $entity)
    {
        return $this->id->bin() === $entity->id->bin();
    }

    public function isEmpty()
    {
        // Only ID set
        return count($this->_data) === 1;
    }

    public function merge(Bft_Entity $entity)
    {
        if (!$this->equals($entity)) {
            throw new Exception('Unable to merge different entities');
        }
        $this->_data = array_merge($this->_data, $entity->toArray());
        return $this;
    }
    
    public function serialize()
    {
        return json_encode($this->_data);
    }
    
    public function unserialize(&$data)
    {
        return json_decode($data, true);
    }

    public function isNew()
    {
        return $this->_new;
    }

    public function setStored()
    {
        $this->_new = false;
    }

    public function __set($key, $val)
    {
        if ($key === 'id') {
            throw new Exception('Unable to change immutable entity id');
        }
        if ($val instanceof Bft_UUID) {
            $this->_data[$key] = $val->__toString();
        }
        else {
            $this->_data[$key] = $val;
        }
    }
    
    public function __get($key)
    {
        if ($key == 'id') {
            return $this->_id;
        }
        if (isset($this->_data[$key])) {
            return $this->_data[$key];
        }
        return null;
    }
    
    public function set($key, $val=null)
    {
        if (is_array($key) && $val===null) {
            foreach ($key as $k => $v) {
                $this->__set($k, $v);
            }
        }
        else {
            $this->__set($key, $val);
        }
        return $this;
    }
    
    public function get($key)
    {
        return $this->__get($key);
    }

    public function toArray()
    {
        return $this->_data;
    }

    public function store(Bft $bft)
    {
        return $bft->store($this);
    }

    public function delete(Bft $bft)
    {
        $bft->delete($this->id);
        $this->_new = true;
    }

}
