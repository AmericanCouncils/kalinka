<?php

namespace Kalinka;

class BaseAccess
{
    const ANY_PROPERTY = null;

    private $actions = ["create", "read", "update", "destroy"];

    protected function setupObjectTypes($objectTypes)
    {
    }

    protected function allow($action, $object, $property = null, $func = null)
    {
    } 

    protected function allowEverything()
    {
    }

    public function can($action, $object, $property = null)
    {
        return true;
    }
}
