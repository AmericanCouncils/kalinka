<?php

namespace Kalinka;

abstract class BaseAccess
{
    private $actions;

    // TODO raise exception if invalid action
    protected function setupActions($actions)
    {
        $this->actions = [];
        foreach ($actions as $act) {
            $this->actions[$act] = true;
        }
    }

    private $objectTypes;

    // TODO raise exception if invalid objtype/property
    protected function setupObjectTypes($objectTypes)
    {
        $this->objectTypes = [];
        foreach ($objectTypes as $key => $value) {
            if (is_int($key)) {
                $this->objectTypes[$value] = [];
            } elseif (is_string($key)) {
                $value_set = [];
                foreach ($value as $v) {
                    $value_set[$v] = true;
                }
                $this->objectTypes[$key] = $value_set;
            } else {
                // TODO Freak out
            }
        }
    }

    protected function assertValidAction($action)
    {
        if (!array_key_exists($action, $this->actions)) {
            throw new \InvalidArgumentException(
                "Given invalid action '" . $action . "'"
            );
        }
    }

    protected function assertValidObjectType($objectType)
    {
        if (!array_key_exists($objectType, $this->objectTypes)) {
            throw new \InvalidArgumentException(
                "Given invalid object type '" . $objectType . "'"
            );
        }
    }

    protected function assertValidObjectTypeAndProperty($objectType, $property)
    {
        $this->assertValidObjectType($objectType);
        if ($property == "DEFAULT") { return; }
        if (!array_key_exists($property, $this->objectTypes[$objectType])) {
            throw new \InvalidArgumentException(
                "Given undefined property '" . $property . "'" .
                " for object type '" . $objectType . "'"
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

    abstract protected function check($action, $obj_class, $object, $property);

    protected function __construct()
    {
        $this->setupActions(["create", "read", "update", "destroy"]);
    }
}
