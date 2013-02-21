<?php

namespace Fixtures;

use AC\Kalinka\Guard\BaseGuard;

class MyAppGuard extends BaseGuard
{
    public function __construct(User $subject, $object = null)
    {
        parent::__construct($subject, $object);
    }

    protected function policyUsernameHasVowels()
    {
        return preg_match("/[aeiou]/", $this->subject->name) == 1;
    }

    protected function policyAckNoReturnValue()
    {
        // Do nothing
    }

    protected function policyAckBadReturnValue()
    {
        return 3;
    }
}
