<?php

namespace Fixtures;

use AC\Kalinka\Context\BaseContext;

class RoleSubjectContext extends BaseContext
{
    protected function isValidSubject()
    {
        return (gettype($this->subject) == "string");
    }

    protected function policyNonGuest()
    {
        return ($this->subject !== "guest");
    }
}
