<?php
/**
 * Collections of Entities
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
 *
 * @category    model
 * @package     Bft
 * @copyright   Copyright (c) 2010 Adam Hayward ALL RIGHTS RESERVED
 * @author      Adam Hayward <adam@happy.cat>
 * @version     $Id$
**/

require_once 'Zend/Db/Table/Abstract.php';
require_once 'Zend/Db/Expr.php';
require_once 'Uuid.php';
require_once 'Nullid.php';
require_once 'Entity.php';
require_once 'ConnectionManager.php';

class Bft_Collection
{
    
    protected $_tablename = 'collections';
    protected $_name;
    protected $_parent;
    protected $_state;

    protected $_bft;

    const null_collection_id = "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";

    public function __construct($name, $parent=null, Bft $bft)
    {
        $this->_name = $name;
        if (is_string($parent)) {
            $this->_parent = new Bft_UUID($parent);
        }
        elseif ($parent instanceof Bft_Entity || $parent instanceof Bft_UUID) {
            $this->_parent = $parent;
        }
        elseif ($parent === null) {
            $this->_parent = new Bft_NullId();
        }
        else{
            throw new Exception('Invalid parent specified for collection');
        }
        $this->_bft = $bft;
    }
    
    public function getParentId()
    {
        if ($this->_parent instanceof Bft_Entity) {
            return $this->_parent->id;
        }
        return $this->_parent;
    }

    public function getStateId()
    {
        return new Bft_UUID(md5($this->getParentId() . $this->_name));
    }

    public function getState()
    {
        if ($this->_state === null) {
            $id = $this->getStateId();
            $state = $this->_bft->get($id);
            if ($state === null) {
                $data = array(
                    'id' => $id,
                    'sub' => array()
                );
                $state = new Bft_Entity($data);
            }
            $this->_state = $state;
        }
        return $this->_state;
    }

    public function eraseState()
    {
        $this->_bft->delete($this->getStateId());
    }

    public function getSubCollections()
    {
        return $this->getState()->sub;
    }

    public function addSubCollections(array $names)
    {
        if (empty($names)) {
            return;
        }
        $state = $this->getState();
        $state->sub = array_unique(array_merge($state->sub, $names));
        $this->_bft->store($state);
    }
    
    public function addMany($entities)
    {
        if ($this->_new) {
            $this->save();
        }
        $values = array();
        $db = $this->_bft->getCollectionAdapter($this->_name);
        $sql = 'INSERT INTO entities_collections (collection_id, entity_id) values ';
        foreach ($entities as $entity) {
            if ($entity instanceof Bft_Entity) {
                $id = $entity->id->bin();
            }
            elseif ($entity instanceof Bft_UUID){
                $id = $entity->bin();
            }
            else {
                $uuid = new Bft_UUID($entity);
                $id = $uuid->bin();
            }
            $values[] = $db->quoteInto('(?,', $id) . $db->quoteInto('?)', $id);
        }
        $sql .= implode(',', $values);
        return $db->query($sql);
    }
    
    public function add($entity)
    {
        if ($entity instanceof Bft_Entity) {
            $entity = $entity->id;
        }
        elseif (is_string($entity)) {
            $entity = new Bft_UUID($entity);
        }
        $db = $this->_bft->getCollectionAdapter($this->_name);
        $sql = 'INSERT INTO entities_collections (collection_id, name, entity_id) values (:col_id, :name, :ent_id)';
        $colId = $this->_parent? $this->_parent->id->bin() : Bft_Collection::null_collection_id;
        $params = array(
            'col_id' => $colId,
            'name' => $this->_name,
            'ent_id' => $entity->bin()
        );
        try {
            return $db->query($sql, $params)->rowCount();
        }
        catch (Exception $e) {
            if ($this->hasEntity($entity)) {
                return null;
            }
            throw $e;
        }
    }

    public function delete($entity)
    {
        if ($entity instanceof Bft_Entity) {
            $entity = $entity->id;
        }
        elseif (is_string($entity)) {
            $entity = new Bft_UUID($entity);
        }
        $db = $this->_bft->getCollectionAdapter($this->_name);
        $sql = 'DELETE FROM entities_collections WHERE collection_id=:collection_id AND name=:name AND entity_id=:ent_id LIMIT 1';
        $params = array(
            'collection_id' => $this->getParentId()->bin(),
            'name' => $this->_name,
            'ent_id' => $entity->bin()
        );
        return $db->query($sql, $params)->rowCount();
    }

    public function hasEntity($entity)
    {
        if ($entity instanceof Bft_Entity) {
            $entity = $entity->id;
        }
        elseif (is_string($entity)) {
            $entity = Bft_UUID($entity);
        }
        $db = $this->_bft->getCollectionAdapter($this->_name);
        $sql = "SELECT COUNT(*) cnt FROM entities_collections WHERE collection_id = :collection_id AND name = :name";
        $params = array(
            'name' => $this->_name,
            'collection_id' => $this->getParentId()->bin()
        );
        $result = $db->fetchRow($sql, $params);
        return $result['cnt'] > 0;
    }

    public function getIds()
    {
        $db = $this->_bft->getCollectionAdapter($this->_name);
        $sql = 'SELECT entity_id AS id FROM entities_collections WHERE collection_id = :collection_id AND name = :name';
        $params = array(
            'name' => $this->_name,
            'collection_id' => $this->getParentId()->bin()
        );
        return $db->fetchAll($sql, $params);
    }
    
    public function getEntities()
    {
        $entities = array();
        foreach ($this->getIds() as $row) {
            $uuid = new Bft_UUID($row['id']);
            $row = $this->_bft->get($row['id']);
            if ($row) {
                $entities[] = $row;
            }
            else {
                $entities[] = new Bft_Entity(null, $uuid);
            }
        }
        return $entities;
    }

    public function getEntitiesArray()
    {
        $entities = array();
        foreach ($this->getIds() as $row) {
            $uuid = new Bft_UUID($row['id']);
            $row = $this->_bft->get($row['id']);
            if ($row) {
                $entities[] = $row->toArray();
            }
            else {
                $entities[] = array('id' => (string) $uuid);
            }
        }
        return $entities;
    }

}
