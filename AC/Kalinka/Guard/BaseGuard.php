<?php

namespace AC\Kalinka\Guard;

/**
 * Base class for Guard classes, which define security policies.
 *
 * Note that whether or not the policies actually apply in any given
 * case is determed by Authorizers; the Guard classes simply make
 * the policies available.
 */
class BaseGuard
{
    const ABSTAIN = -1;

    protected $subject;
    protected $object;

    public function __construct($subject = null, $object = null)
    {
        $this->subject = $subject;
        $this->object = $object;
    }

    public function checkPolicy($name)
    {
        // TODO Catch and complain about NULLs coming back, they indicate
        // that somebody forgot a 'return'!
        return call_user_func([$this, "policy" . ucfirst($name)]);
    }

    protected function policyAllow()
    {
        return true;
    }

    protected function policyDeny()
    {
        return false;
    }

    protected function policyAbstain()
    {
        return self::ABSTAIN;
    }
}
