<?php
/**
 * Bft - a sharded 'big fat table' database for the Zend framework
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
 * @see         http://bret.appspot.com/entry/how-friendfeed-uses-mysql
 * @copyright   Copyright (c) 2010 Adam Hayward ALL RIGHTS RESERVED
 * @author      Adam Hayward <adam at happy dot cat>
 * @license     http://svn.happy.cat/public/LICENSE.txt     New BSD License
 */
 
require_once 'ConnectionManager.php';
require_once 'Uuid.php';
require_once 'Collection.php';

class Bft
{
    protected $_name = 'entities';
    protected $_cfg;
    protected $_cm;
    
    public function __construct($config, $cm=null)
    {
        $this->_cfg = $config;
        if ($cm !== null && !($cm instanceof Bft_ConnectionManagerInterface)) {
            throw new Exception('Invalid connection manager used to instantiate Bft');
        }
        $this->_cm = $cm;
    }
    
    public function getConnectionManager()
    {
        if ($this->_cm === null) {
            $this->_cm = new Bft_ConnectionManager($this->_cfg);
        }
        return $this->_cm;
    }
    
    public function getEntityAdapter(&$id)
    {
        return $this->getConnectionManager()
                    ->getEntityAdapter($id);
    }
    
    public function getCollectionAdapter(&$id)
    {
        return $this->getConnectionManager()
                    ->getCollectionAdapter($id);
    }
    
    public function getTableName()
    {
        return $this->_name;
    }

    public function entity($data)
    {
        if ($data instanceof Bft_Entity){
            return $data;
        }
        return new Bft_Entity($data);
    }
    
    public function exists($id)
    {
        if (!$id instanceof Bft_UUID) {
            $id = new Bft_UUID($id);
        }
        $db = $this->getEntityAdapter($id);
        $where = $db->quoteInto("id = ?", $id->bin());
        $sql = "SELECT COUNT(*) cnt FROM " . $this->_name . " WHERE $where";
        $result = $db->fetchRow($sql);
        return $result['cnt'] > 0;
    }
    
    public function insert(Bft_Entity $entity)
    {
        $db = $this->getEntityAdapter($entity->id);
        $result = $db->insert(
            $this->_name,
            array(
                'id' => $entity->id->bin(),
                'body' => $entity->serialize()
            )
        );
        $entity->setStored();
        return $result;
    }
    
    public function update(Bft_Entity $entity)
    {
        $db = $this->getEntityAdapter($entity->id);
        $result = $db->update(
            $this->_name,
            array(
                'body' => $entity->serialize()
            ),
            $db->quoteInto('id=?', $entity->id->bin())
        );
        $entity->setStored();
        return $result;
    }
    
    public function store($entity)
    {
        $entity = $this->entity($entity);
        if ($entity->isNew()) {
            try {
                $this->insert($entity);
            }
            catch (Exception $e) {
                if ($this->exists($entity->id)) {
                    $this->update($entity);
                }
                throw $e;
            }
        }
        else {
            $cnt = $this->update($entity);
            if ($cnt === 0 && !$this->exists($entity->id)) {
                $this->insert($entity);
            }
        }
        return $entity;
    }

    public function getByBinaryId($id)
    {
        $sql = 'SELECT updated, body FROM ' . $this->_name . ' WHERE id = ?';
        $db = $this->getEntityAdapter($id);
        return $db->fetchRow($sql, $id);
    }

    public function getRaw(Bft_UUID $id)
    {
        $sql = 'SELECT updated, body FROM ' . $this->_name . ' WHERE id = ?';
        $db = $this->getEntityAdapter($id);
        return $db->fetchRow($sql, $id->bin());
    }
    
    public function get($id)
    {
        if (!$id instanceof Bft_UUID) {
            $id = new Bft_UUID($id);
        }
        $raw = $this->getRaw($id);
        if (!$raw) {
            return null;
        }
        else {
            return new Bft_Entity($raw['body'], $id);
        }
    }
    
    public function delete($id)
    {
        if (!$id instanceof Bft_UUID) {
            $id = new Bft_UUID($id);
        }
        $db = $this->getEntityAdapter($id);
        $where = $db->quoteInto("id = ?", $id->bin());
        $sql = 'DELETE FROM ' . $this->_name . ' WHERE ' . $where;
        return $db->query($sql)->rowCount();
    }
    
    public function collection($name, $parent=null)
    {
        $args = func_get_args();
        if (count($args) == 2) {
            return new Bft_Collection($args[0], $args[1], $this);
        }
        else {
            return new Bft_Collection($args[0]['name'], $args[0], $this);
        }
    }
    
}
