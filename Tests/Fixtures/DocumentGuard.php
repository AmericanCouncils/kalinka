<?php

namespace Fixtures;

class DocumentGuard extends MyAppGuard
{
    protected function policyUnclassified()
    {
        return !$this->object->isClassified();
    }

    protected function policyOwned()
    {
        return ($this->object->getOwner() == $this->subject->name);
    }
}
