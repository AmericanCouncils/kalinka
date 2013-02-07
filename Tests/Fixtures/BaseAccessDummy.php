<?php

namespace Fixtures;

use Kalinka\BaseAccess;

class BaseAccessDummy extends BaseAccess
{
    protected function isValidAction($action) {
        return preg_match("/^ok/i", $action);
    }

    protected function isValidObjectType($objectType) {
        return preg_match("/^ok/i", $objectType);
    }

    protected function isValidProperty($objectType, $property) {
        return preg_match("/^ok/i", $property);
    }

    protected function getPrivileges($action, $objectType, $property) {
        return true;
    }
}
