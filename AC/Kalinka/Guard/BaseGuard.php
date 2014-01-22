<?php

namespace AC\Kalinka\Guard;

/**
 * Base class for Guard classes, which define security policies.
 *
 * Note that whether or not the policies actually apply in any given
 * case is determed by Authorizers; the Guard classes simply make
 * the policies available.
 *
 * Your application should create a class which extends BaseGuard
 * and includes any policies that apply based on the subject alone
 * (e.g. checking if the user has an admin flag set). Then, any
 * resources in your application that have policies specifically
 * related to them (e.g. checking if a post is marked as editable)
 * or involving a relationship between them and the subject (e.g.
 * checking if a post is owned by the current user) need their own
 * Guard class that extend from your app's Guard class.
 *
 * Examples of how to write Guards are available in <a href="index.html">README.md</a>.
 *
 * Besides deriving from this class, it can sometimes be useful to map a
 * resource directly to BaseGuard, or to your application's base guard class.
 * This way, you can have "virtual" resources,
 * which are not associated with any `object`. For
 * example, suppose you want to control access to a global system reset
 * button. You could map a resource type named `system` to BaseGuard, with
 * a single action `reset`. You may then call `can("reset", "system")` on your
 * Authorizer to check access to the button, as the third argument of `can()`
 * defaults to `null`.
 */
class BaseGuard implements IGuard
{
    /**
     * Checks if the named policy permits access.
     *
     * This method looks for a method named policyFoo, where "foo"
     * is the value of `$name` (the first letter of the policy name is
     * automatically capitalized). That method must return a boolean
     * value; `null` is *not* automatically assumed to be false.
     *
     * @return Boolean
     */
    public function checkPolicy($name, $subject, $object = null)
    {
        $result = call_user_func([$this, "policy" . ucfirst($name)], $subject, $object);
        if (is_bool($result)) {
            return $result;
        } else {
            throw new \LogicException(
                "From policy $name: invalid return value " . var_export($result, true)
            );
        }
    }

    /**
     * Checks if a policy list collectively permits access.
     *
     * Every entry in the list must permit access for the list overall
     * to do so (i.e. the list is joined by logical ANDs).
     *
     * Individual entries in the list can be a string with a policy names,
     * or an array of policies. These sub-arrays will approve access as
     * a whole if any item in the sub-array does so (i.e. the sub-array
     * is joined by logical ORs).
     *
     * For example, if you pass `["a", ["b", "c"]]` to checkPolicyList,
     * it will return true only if `policyA` returns true *AND* at least
     * one of `policyB` or `policyC` return true.
     *
     * @return Boolean
     */
    public function checkPolicyList($policies, $subject, $object = null)
    {
        if (is_string($policies)) {
            $policies = [$policies];
        } elseif (is_null($policies)) {
            return false;
        } elseif (count($policies) == 0) {
            return false;
        }

        // Outer policy list is an AND-list
        foreach ($policies as $policy) {
            if (is_array($policy)) {
                $result = false;
                // Inner policy lists are OR-lists
                foreach ($policy as $subpolicy) {
                    if ($this->checkPolicy($subpolicy, $subject, $object)) {
                        $result = true;
                        break;
                    }
                }
            } else {
                $result = $this->checkPolicy($policy, $subject, $object);
            }

            if (!$result) {
                return false;
            }
        }

        return true;
    }

    /**
     * A policy that always returns `true`.
     *
     * This is useful in Authorizer configurations when you just want to
     * make some action generally accessible.
     */
    protected function policyAllow()
    {
        return true;
    }
}
