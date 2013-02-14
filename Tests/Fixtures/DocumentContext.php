<?php

namespace Fixtures;

class DocumentContext extends UserSubjectContext
{
    protected function isValidObject()
    {
        return (
            gettype($this->object) == "object" &&
            get_class($this->object) == "Fixtures\Document"
        );
    }

    protected function policyUnclassified()
    {
        return !$this->object->isClassified();
    }

    protected function policyOwned()
    {
        return ($this->object->getOwner() == $this->subject->name);
    }
}
