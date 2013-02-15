<?php

namespace AC\Kalinka\Guard;

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
