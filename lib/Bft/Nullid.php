<?php

// A NULL UUID
class Bft_NullId extends Bft_UUID
{
    const bin = "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";
    const hex = '00000000000000000000000000000000';
    const str = '00000000-0000-0000-0000-000000000000';
    
    protected static $_instance; // Singleton
    
    public function __construct()
    {
        if (Bft_NullId::$_instance === null)
        {
            Bft_NullId::$_instance = $this;
        }
        return Bft_NullId::$_instance;
    }
    
    public static function gen()
    {
        return Bft_NullId::$_instance;
    }

    /**
     */
    public function hex()
    {
        return Bft_NullId::hex;
    }

    /**
     */
    public function bin()
    {
        return Bft_NullId::bin;
    }

    /**
     */
    public function __toString()
    {
        return Bft_NullId::str;
    }

    public function str()
    {
        return Bft_NullId::str;
    }

}
