<?php

namespace Kalinka;

class DelegatedAccess extends BaseAccess
{
    private $impls;

    public function __construct($impls)
    {
        $this->impls = $impls;
    }

    // Overrides BaseAccess::isValidAction
    protected function isValidAction($action)
    {
        return true;
    }

    // Overrides BaseAccess::isValidObjectType
    protected function isValidObjectType($objectType)
    {
        return true;
    }

    // Overrides BaseAccess::isValidProperty
    protected function isValidProperty($objectType, $property)
    {
        return true;
    }

    protected function check($action, $obj_class, $object, $property)
    {
        return $obj_class != "SheetMetal";
    }
}
