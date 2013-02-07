<?php

namespace Kalinka;

abstract class BaseAccess
{
    protected final function isNameValid($name)
    {
        $pat = "/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/";
        return (is_string($name) && preg_match($pat, $name));
    }

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

    // TODO Raise exception if invalid action, object class, or property
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
}
