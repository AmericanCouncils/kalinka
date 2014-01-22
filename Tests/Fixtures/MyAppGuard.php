<?php

namespace Fixtures;

use AC\Kalinka\Guard\BaseGuard;

abstract class MyAppGuard extends BaseGuard
{
    protected function policyUsernameHasVowels($subject)
    {
        return preg_match("/[aeiou]/", $subject->name) == 1;
    }

    protected function policyUserIsOnFirst($subject)
    {
        return ($subject->name == "Who");
    }

    protected function policyUserIsOnSecond($subject)
    {
        return ($subject->name == "What");
    }
}
