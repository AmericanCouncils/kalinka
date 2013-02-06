<?php

namespace Fixtures;

use Kalinka\BaseAccess;

class BaseAccessWaldo extends BaseAccess
{
    public function __construct($actions, $objtypes)
    {
        parent::__construct();
        parent::setupActions($actions);
        parent::setupObjectTypes($objtypes);
    }

    protected function check($action, $obj_class, $object, $property) {
        return true;
    }
}
