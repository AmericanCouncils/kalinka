<?php

namespace Kalinka;

abstract class BaseAccess
{
    protected final function isNameValid($name) {
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
        if (!$this->isValidProperty($objectType, $property)) {
            throw new \InvalidArgumentException(
                "Given unknown property " . var_export($property, true) .
                " for object type " . var_export($objectType, true)
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
            $object_cls = $object;
            $object = null;
        } else {
            $object_cls = get_class($object);
        }
        $property = is_null($property) ? "DEFAULT" : $property;
        $this->assertValidObjectTypeAndProperty($object_cls, $property);

        return $this->check($action, $object_cls, $object, $property);
    }

    abstract protected function isValidAction($action);
    abstract protected function isValidObjectType($objectType);
    abstract protected function isValidProperty($objectType, $property);

    abstract protected function check($action, $obj_class, $object, $property);
}
