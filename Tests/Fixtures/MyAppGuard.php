<?php

namespace Fixtures;

use AC\Kalinka\Guard\BaseGuard;

class MyAppGuard extends BaseGuard
{
    public function __construct($subject, $object = null) {
        if (!($subject instanceof User)) {
            throw new InvalidArgumentException("Subject must be User");
        }
        parent::__construct($subject, $object);
    }

    protected function policyUsernameHasVowels()
    {
        return preg_match("/[aeiou]/", $this->subject->name) == 1;
    }
}
