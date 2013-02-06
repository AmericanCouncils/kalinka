<?php

namespace Kalinka;

abstract class BaseAccess
{
    // TODO Raise exception if user tries to create an action or objclass
    // named "ANY" or a property named "DEFAULT".
    private $actions = ["create", "read", "update", "destroy"];

    protected function setupActions($actions)
    {
    }

    protected function setupObjectTypes($objectTypes)
    {
    }

    protected function assertValidAction($action)
    {
    }

    protected function assertValidObjectType($objectType)
    {
    }

    protected function assertValidObjectTypeAndProperty($objectType, $property)
    {
    }

    // TODO Raise exception if invalid action, object class, or property
    final public function can($action, $object, $property = null)
    {
        if (is_string($object)) {
            $object_cls = $object;
            $object = null;
        } else {
            $object_cls = get_class($object);
        }
        $property = is_null($property) ? "DEFAULT" : $property;

        $this->assertValidAction($action);
        $this->assertValidObjectTypeAndProperty($object_cls, $property);

        return $this->check($action, $object_cls, $object, $property);
    }

    abstract protected function check($action, $obj_class, $object, $property);
}
