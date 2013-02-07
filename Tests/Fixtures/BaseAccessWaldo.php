<?php

namespace Fixtures;

use Kalinka\BaseAccess;

class BaseAccessWaldo extends BaseAccess
{
    public function __construct($actions, $objtypes)
    {
        if (!is_null($actions)) {
            parent::setupActions($actions);
        }
        if (!is_nulL($objtypes)) {
            parent::setupObjectTypes($objtypes);
        }
    }

    protected function check($action, $obj_class, $object, $property) {
        return true;
    }
}
