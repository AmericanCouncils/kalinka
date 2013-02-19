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

    public function checkPolicyList($policies)
    {
        if (is_string($policies)) {
            $policies = [$policies];
        } elseif (is_null($policies)) {
            return false;
        } elseif (count($policies) == 0) {
            // TODO Test this!
            return false;
        }

        // Outer policy list is an AND-list
        foreach ($policies as $policy) {
            if (is_array($policy)) {
                $result = false;
                // Inner policy lists are OR-lists
                foreach ($policy as $subpolicy) {
                    if ($this->checkPolicy($subpolicy)) {
                        $result = true;
                        break;
                    }
                }
            } else {
                $result = $this->checkPolicy($policy);
            }

            if (!$result) {
                return false;
            }
        }

        return true;
    }

    protected function policyAllow()
    {
        return true;
    }
}
