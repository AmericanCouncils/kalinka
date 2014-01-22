<?php

namespace Fixtures;

class DocumentGuard extends MyAppGuard
{
    protected function policyUnclassified($subject, $object)
    {
        return !$object->isClassified();
    }

    protected function policyOwned($subject, $object)
    {
        return ($object->getOwner() == $subject->name);
    }

    public function getActions()
    {
        return ["read", "write"];
    }
}
