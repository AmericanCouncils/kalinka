<?php

namespace Kalinka;

/**
 * Base for all classes that do permissions checks.
 *
 * Privileges are checked based on three parameters:
 *
 * - action : What verb we are trying to perform, e.g. "read".
 * - objectType : The type of object we're guarding, e.g. "BlogPost".
 * - property : (Optional) Some sub-part of the object, e.g. "author".
 *
 * All three must be strings that are valid PHP identifiers, i.e. starting
 * with a letter or underscore and consisting entirely of letters,
 * numbers, and underscores.
 *
 * Children of this class must provide a method getPrivileges that
 * returns privilege results. The results returned must be one of the following:
 *
 * - null : Soft Denial
 * - true : Approval
 * - false : Hard Denial
 *
 * (See the getPriviliges documentation for more detail on how it should
 * return values.)
 *
 * When we are checking a specific property of an object, and we get back
 * only soft denial, then the check is tried again without a property
 * specified.
 *
 * When we have multiple results to compare (i.e. if getPriviliges returns
 * an array of results), then false takes top priority, because it means
 * that we are explicitly denying access. If none of the results are false,
 * then any true results will take priority and access is granted.
 *
 * However,if all the results are null, or if no results are provided, then
 * access is denied by default.
 */
abstract class BaseAccess
{
    private function assertValidAction($action)
    {
        if (!$this->isValidAction($action)) {
            throw new \InvalidArgumentException(
                "Given unknown action " . var_export($action, true)
            );
        }
    }

    private function assertValidObjectTypeAndProperty($objectType, $property)
    {
        if (!$this->isValidObjectType($objectType)) {
            throw new \InvalidArgumentException(
                "Given unknown object type " . var_export($objectType, true)
            );
        }
        if (
            !is_null($property) &&
            !$this->isValidProperty($objectType, $property)
        ) {
            throw new \InvalidArgumentException(
                "Given unknown property " . var_export($property, true) .
                " for object type " . var_export($objectType, true)
            );
        }
    }

    private function coalescePrivileges($p, $object)
    {
        if (is_null($p) || $p === true || $p === false) {
            return $p;
        } elseif (is_callable($p)) {
            if (is_null($object)) {
                // With a null object, we're only interested in theoretical
                // allowability. But we can't call a function with no object,
                // so let's assume that there is some possible set of
                // arguments that would make the function approve access.
                return true;
            } else {
                return $this->coalescePrivileges($p($object), $object);
            }
        } elseif (is_array($p)) {
            $cur = null;
            foreach ($p as $subp) {
                $result = $this->coalescePrivileges($subp, $object);
                if ($result == false) {
                    // Got a hard denial, reject access right away
                    return false;
                } elseif ($result === true) {
                    $cur = true;
                }
            }
            return $cur;
        } else {
            throw new \LogicError(
                "Unintelligible privilege result " . var_export($p, true)
            );
        }
    }

    /**
     * Returns true or false, indicating whether an action is permitted.
     *
     * See the class documentation for BaseAccess for more details.
     *
     * An InvalidArgumentException will be thrown if any of the arguments
     * are reported as invalid by the corresponding isValid* method.
     *
     * @param action What verb we are trying to perform, e.g. "read".
     * @param objectType The type of object we're guarding, e.g. "BlogPost".
     * @param property (Optional) Some sub-part of the object, e.g. "author".
     */
    final public function can($action, $object, $property = null)
    {
        $this->assertValidAction($action);

        if (is_string($object)) {
            // We only got the name of an object class, so let's check
            // if it's possible for any such objects to be permitted.
            $objectType = $object;
            $object = null;
        } else {
            $objectType = get_class($object);
        }
        $this->assertValidObjectTypeAndProperty($objectType, $property);

        $result = $this->coalescePrivileges(
            $this->getPrivileges($action, $objectType, $property),
            $object
        );
        if (is_null($result) && !is_null($property)) {
            // If we didn't get a hard true or false for this specific property
            // then see if we can get an answer for the object in general
            $result = $this->coalescePrivileges(
                $this->getPrivileges($action, $objectType, null),
                $object
            );
        }

        // Casting to bool here to treat null as false
        return (bool)$result;
    }

    abstract protected function isValidAction($action);
    abstract protected function isValidObjectType($objectType);
    abstract protected function isValidProperty($objectType, $property);

    abstract protected function getPrivileges($action, $objectType, $property);

    /**
     * Returns true if $name is OK to use as an objectType, action, or property.
     */
    protected final function isNameValid($name)
    {
        $pat = "/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/";
        return (is_string($name) && preg_match($pat, $name));
    }
}
