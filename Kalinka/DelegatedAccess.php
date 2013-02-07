<?php

namespace Kalinka;

class DelegatedAccess extends BaseAccess
{
    private $constituents;

    // TODO Assert that constituents all implement BaseAccess
    public function __construct($constituents)
    {
        $this->constituents = $constituents;
    }

    protected function isValidAction($action)
    {
        foreach ($this->constituents as $c) {
            if ($c->isValidAction($action)) {
                return true;
            }
        }
        return false;
    }

    protected function isValidObjectType($objectType)
    {
        foreach ($this->constituents as $c) {
            if ($c->isValidObjectType($objectType)) {
                return true;
            }
        }
        return false;
    }

    protected function isValidProperty($objectType, $property)
    {
        foreach ($this->constituents as $c) {
            if ($c->isValidProperty($objectType, $property)) {
                return true;
            }
        }
        return false;
    }

    protected function check($action, $objectType, $object, $property)
    {
        foreach ($this->constituents as $c) {
            if (
                $c->isValidAction($action) &&
                $c->isValidObjectType($objectType) &&
                $c->isValidProperty($objectType, $property) &&
                $c->check($action, $objectType, $object, $property)
            ) {
                return true;
            }
        }
        return false;
    }
}
