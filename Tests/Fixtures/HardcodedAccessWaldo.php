<?php

namespace Fixtures;

use Kalinka\HardcodedAccess;

class HardcodedAccessWaldo extends HardcodedAccess
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
