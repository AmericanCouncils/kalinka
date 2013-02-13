<?php

namespace AC\Kalinka\Context;

class BaseContext
{
    const ABSTAIN = -1;

    protected $subject;
    protected $object;

    public function __construct($subject = null, $object = null) {
        $this->subject = $subject;
        $this->object = $object;

        $valid = $this->isValidSubject();
        if (gettype($valid) !== "boolean") {
            throw new \LogicException(
                "Invalid return value from isValidSubject in " .
                get_called_class()
            );
        }
        if (!$valid) {
            throw new \InvalidArgumentException(
                "Invalid context subject "  . var_export($this->subject, true) .
                " for " . get_called_class()
            );
        }

        $valid = $this->isValidObject();
        if (gettype($valid) !== "boolean") {
            throw new \LogicException(
                "Invalid return value from isValidObject in " .
                get_called_class()
            );
        }
        if (!$valid) {
            throw new \InvalidArgumentException(
                "Invalid context object "  . var_export($this->object, true) .
                " for " . get_called_class()
            );
        }
    }

    public function checkPolicy($name) {
        // TODO Catch invalid policy checks and throw a more useful
        // exception than "can't find any method named that".
        // TODO Catch and complain about NULLs coming back, they indicate
        // that somebody forgot a 'return'!
        return call_user_func([$this, "policy" . ucfirst($name)]);
    }

    protected function isValidSubject()
    {
        return is_null($this->subject);
    }

    protected function isValidObject()
    {
        return is_null($this->object);
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
