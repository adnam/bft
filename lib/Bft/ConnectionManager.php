<?php
/**
 * Bft Connection manager 
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
**/

require_once 'ConnectionManagerInterface.php';
require_once 'Zend/Db.php';

class Bft_ConnectionManager
{

    protected $_config;
    protected $_adapters = array();
    
    public function __construct($config)
    {
        $this->_config = $config;
        if ($this->countAdapters() < 1) {
            throw new Exception('Bft config error - no database adapters defined');
        }
    }
    
    public function getEntityAdapter(Bft_UUID $uuid)
    {
        $ord = ord(substr($uuid->bin(), 0, 1));
        $ind = $ord % $this->countAdapters();
        return $this->getAdapter($ind);
    }
    
    public function getCollectionAdapter(&$name)
    {
        $ind = hexdec(substr(md5($name), 0, 1)) % $this->countAdapters();
        return $this->getAdapter($ind);
    }
    
    public function countAdapters()
    {
        return count($this->_config->hosts);
    }

    /*
     * Get the Nth configured database adapter
     */
    protected function getAdapter($id)
    {
        if (!isset($this->_adapters[$id])) {
            if (!$this->_config->hosts->{$id}) {
                return null;
            }
            static $paramNames = array(
                'hostname', 'username', 'password',
                'dbname', 'charset', 'adapter'
            );
            $params = array ();
            foreach ($paramNames as $name) {
                if (isset($this->_config->hosts->{$id}->{$name})) {
                    $params[$name] = $this->_config->hosts->{$id}->{$name};
                }
                elseif (isset($this->_config->default->{$name})) {
                    $params[$name] = $this->_config->default->{$name};
                }
                else {
                    throw new Exception("Parameter '$name' is not defined in BFT config file");
                }
            }
            $this->_adapters[$id] = Zend_Db::factory($params['adapter'], $params);
        }
        return $this->_adapters[$id];
    }
}
