<?php

namespace Fixtures;

class DocumentContext extends RoleSubjectContext
{
    protected function isValidObject()
    {
        return (get_class($this->object) == "Fixtures\Document");
    }

    protected function policyUnclassified()
    {
        return !$this->object->isClassified();
    }
}
