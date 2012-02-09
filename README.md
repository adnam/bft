BFT
===

A schemaless data store based on MySQL with collections and sharding.

**Warning:** This project is actually a bit useless. It is based on the 
blog-post [How FriendFeed uses MySQL to store schema-less data](http://backchannel.org/blog/friendfeed-schemaless-mysql)
by [Bret Taylor](http://backchannel.org/) of [FriendFeed](http://friendfeed.com/).
Still, if this interests you, have a look at Redis or Cassandra too :-)

Author:     Adam Hayward <adam at happy dot cat>
Date:       October 2010
License:    New BSD, see below
See:        http://happy.cat/blog/draft/Bft-schemaless-datastore.html
See:        http://bret.appspot.com/entry/how-friendfeed-uses-mysql

Installation
------------

1. Setup your database shards using the script in the 'sql' directory
2. Create a 'config.ini' - see config.example.ini
3. Set the include path of your application to be able to include Bft

Examples
--------

1. Storing and retrieving entities and collections
    
        $config = new Zend_Config_Ini('/path/to/config.ini');
        $bft = new Bft($config);

        // Create a new entity
        $contact = $bft->store(array('name' => Joe, 'surname' => 'Bloggs'));

        // Print the UUID of the contact
        print $contact->id;

        // Create a collection, and add the entity
        $collection = $bft->collection('contacts', null);
        $collection->add($contact);

        // List the entities in a collection:
        $collection->getEntities();

        // List the IDs of entities in a collection
        $collection->getIds();

        // Check if a collection contains a particular entity
        $collection->hasEntity($contact);

Rest-ish API
------------

Bft comes complete with an API that you can use to store JSON objects in heirarchies of collections.

Installation:

1. Create a new host, and point the DocumentRoot at Api/public
2. Create config file Api/config.ini (see Api/config.example.ini)
3. That's it.

Usage:
------

   URLs contain collection names, starting with a dash ('-') or entity
   IDs (UUIDs). Here are some examples using Daniel Stenberg's curl utility.

    1. Create an entity within the collection '-contacts'

        $ curl -D - -X "POST" "http://bft/-contacts" -d \
            '{"name": "Joe", "Surname" : "Bloggs"}'
        HTTP/1.1 201 Created
        Location: http://bft/-contacts/698fade8-60f6-42bf-a5b5-577b7ed3da57
        Content-Length: 0
    
    2. Retrieve the contact

        $ curl -D - -X "GET" "http://bft.adam-laptop.lan:88/-contacts/698fade8-60f6-42bf-a5b5-577b7ed3da57"
        HTTP/1.1 200 OK
        Content-Length: 77
        Content-Type: application/json

        {"id":"698fade8-60f6-42bf-a5b5-577b7ed3da57","name":"Joe","Surname":"Bloggs"}

    3. Add a contact to the group "-contacts" AND the sub-group '-friends':

        $ curl -D - -X "POST" "http://bft/-contacts/-friends" -d \
            '{"name": "Robert", "Surname" : "Paulson"}'
        HTTP/1.1 201 Created
        Location: http://bft/-contacts/-friends/e12807f9-733b-4dc9-90d2-50c484950746
        Content-Length: 0

    4. List all contacts

        $ curl -D - -X "GET" "http://bft.adam-laptop.lan:88/-contacts"
        HTTP/1.1 200 OK
        Content-Length: 165
        Content-Type: application/json
        
        [{"id":"698fade8-60f6-42bf-a5b5-577b7ed3da57","name":"Joe","Surname":"Bloggs"},
        {"id":"e12807f9-733b-4dc9-90d2-50c484950746","name":"Robert","Surname":"Paulson"}]

    5. List only friends

        $ curl -D - -X "GET" "http://bft.adam-laptop.lan:88/-contacts/-friends"
        HTTP/1.1 200 OK
        Content-Length: 79
        Content-Type: application/json
        
        [{"id":"e12807f9-733b-4dc9-90d2-50c484950746","name":"Robert","Surname":"Paulson"}]

    6. Update friend details

        $ curl -D - -X "PUT" "http://bft/-contacts/-friends/e12807f9-733b-4dc9-90d2-50c484950746"\
            -d '{"id":"e12807f9-733b-4dc9-90d2-50c484950746","name":"Robert","Surname":"Paulson","email":"rob@fightclub.org"}
        HTTP/1.1 204 Ok
        Location: 
        Content-Length: 0

    7. Delete Robert from the friends list

        $ curl -D - -X "DELETE" "http://bft.adam-laptop.lan:88/-contacts/e12807f9-733b-4dc9-90d2-50c484950746"
        HTTP/1.1 204 OK
        Content-Length: 0
        Content-Type: application/json
    
    8. Note that Robert is still stored in the contacts list. To delete completely,

       You must delete him from the root list (contacts)
        $ curl -D - -X "GET" "http://bft.adam-laptop.lan:88/-contacts"
        HTTP/1.1 200 OK
        Content-Length: 165
        Content-Type: application/json
        
        [{"id":"698fade8-60f6-42bf-a5b5-577b7ed3da57","name":"Joe","Surname":"Bloggs"},
        {"id":"e12807f9-733b-4dc9-90d2-50c484950746","name":"Robert","Surname":"Paulson"}]
       
License
-------

    Copyright (c) 2010, Adam Hayward <adam at happy dot cat>
    All rights reserved.
    Redistribution and use in source and binary forms, with or without 
    modification, are permitted provided that the following conditions 
    are met:

    - Redistributions of source code must retain the above copyright 
      notice, this list of conditions and the following disclaimer.
    - Redistributions in binary form must reproduce the above copyright 
      notice, this list of conditions and the following disclaimer in 
      the documentation and/or other materials provided with the distribution.
    - Neither the name of the author nor the names of its contributors
      may be used to endorse or promote products derived from this software 
      without specific prior written permission.
    *
    THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
    "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED 
    TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
    PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR 
    CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, 
    EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, 
    PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR 
    PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF 
    LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING 
    NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS 
    SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

