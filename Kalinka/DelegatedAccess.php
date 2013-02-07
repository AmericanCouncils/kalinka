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

    protected function getPrivileges($action, $objectType, $property)
    {
        $privs = [];
        foreach ($this->constituents as $c) {
            if (
                $c->isValidAction($action) &&
                $c->isValidObjectType($objectType) &&
                (is_null($property) || $c->isValidProperty($objectType, $property))
            ) {
                $privs[] = $c->getPrivileges($action, $objectType, $property);
            }
        }
        return $privs;
    }
}
