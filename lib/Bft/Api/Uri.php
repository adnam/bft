<?php

class Bft_Api_Uri
{
    const TYPE_ENTITY = 0;
    const TYPE_COLLECTION = 1;
    
    protected $parts = array();
    protected $collections = array();
    protected $id;
    protected $parent;
    protected $requestType = 0;
    
    public function __construct($path)
    {
        $path = trim($path, '/');
        if ($path !== '') {
            $this->parts = explode('/', $path);
        }
        $this->parse();
    }
    
    public function parse()
    {
        $parts = $this->parts;
        $collections = array();
        if (empty($parts)) {
            $this->requestType = Bft_Api_Uri::TYPE_ENTITY;
            return;
        }
        $last = array_pop($parts);
        if ($last[0] === '-') {
            $this->requestType = Bft_Api_Uri::TYPE_COLLECTION;
            array_unshift($collections, $last);
        }
        else {
            $this->requestType = Bft_Api_Uri::TYPE_ENTITY;
            $this->id = $last;
        }
        while ($part = array_pop($parts)) {
            if ($this->parent!==null) {
                break;
            }
            if ($part[0] === '-') {
                array_unshift($collections, $part);
            } else {
                $this->parent = $part;
            }
        }
        $last = null;
        foreach ($collections as $c) {
            $this->collections[] = $last . $c;
            $last .= $c;
        }
    }

    public function getParts()
    {
        return $this->parts;
    }
    
    public function isCollectionRequest()
    {
        return $this->requestType === Bft_Api_Uri::TYPE_COLLECTION;
    }

    public function isEntityRequest()
    {
        return $this->requestType === Bft_Api_Uri::TYPE_ENTITY;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getParentId()
    {
        return $this->parent;
    }
    
    public function getParentUrl()
    {
        return implode('/', array_slice($this->parts, 0, -1));
    }
    
    public function getCollectionName()
    {
        return end ($this->collections);
    }
    
    public function getCollections()
    {
        return $this->collections;
    }
    
    public function __toString()
    {
        return '/' . implode('/', $this->parts);
    }

}
