<?php

namespace Fixtures;

class DocumentContext extends MyAppContext
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
