<?php

namespace Fixtures;

use AC\Kalinka\Context\BaseContext;

class UserSubjectContext extends BaseContext
{
    protected function isValidSubject()
    {
        return (
            gettype($this->subject) == "object" &&
            get_class($this->subject) == "Fixtures\User"
        );
    }

    protected function policyHasVowels()
    {
        return preg_match("/[aeiou]/", $this->subject->name) == 1;
    }
}
