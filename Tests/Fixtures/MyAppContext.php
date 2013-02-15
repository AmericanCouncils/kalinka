<?php

namespace Fixtures;

use AC\Kalinka\Context\BaseContext;

class MyAppContext extends BaseContext
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
