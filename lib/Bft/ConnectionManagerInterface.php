<?php

interface ConnectionManagerInterface
{
    public function __construct();

    public function getEntityAdapter();

    public function getCollectionAdapter();
}
