<?php

namespace Kalinka;

abstract class BaseAccess
{
    private function isNameValid($name) {
        $pat = "/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/";
        return (is_string($name) && preg_match($pat, $name));
    }

    private $actions;

    // TODO raise exception if invalid action
    protected function setupActions($actions)
    {
        $this->actions = [];
        foreach ($actions as $act) {
            if (!$this->isNameValid($act)) {
                throw new \InvalidArgumentException(
                    "Given invalid action name " . var_export($act, true)
                );
            }
            $this->actions[$act] = true;
        }
    }

    private $objectTypes;

    // TODO raise exception if invalid objtype/property
    protected function setupObjectTypes($objectTypes)
    {
        $this->objectTypes = [];
        foreach ($objectTypes as $key => $value) {
            $objType = null;
            $props = [];
            if (is_int($key)) {
                $objType = $value;
            } elseif (is_string($key)) {
                $objType = $key;
                if (!is_array($value)) {
                    throw new \InvalidArgumentException(
                        "Given invalid property list " . var_export($value, true)
                    );
                }
                foreach ($value as $v) {
                    $props[$v] = true;
                }
            } else {
                throw new \InvalidArgumentException(
                    "Given invalid object type " . var_export($key, true) .
                    " => " . var_export($value, true)
                );
            }

            if (!$this->isNameValid($objType)) {
                throw new \InvalidArgumentException(
                    "Given invalid object type " . var_export($objType, true)
                );
            }

            foreach ($props as $p => $val) {
                if (!$this->isNameValid($p)) {
                    throw new \InvalidArgumentException(
                        "Given invalid property name " . var_export($p, true)
                    );
                }
            }

            $this->objectTypes[$objType] = $props;
        }
    }

    protected function assertValidAction($action)
    {
        if (!array_key_exists($action, $this->actions)) {
            throw new \InvalidArgumentException(
                "Given unknown action " . var_export($action, true)
            );
        }
    }

    protected function assertValidObjectType($objectType)
    {
        if (!array_key_exists($objectType, $this->objectTypes)) {
            throw new \InvalidArgumentException(
                "Given unknown object type " . var_export($objectType, true)
            );
        }
    }

    protected function assertValidObjectTypeAndProperty($objectType, $property)
    {
        $this->assertValidObjectType($objectType);
        if ($property == "DEFAULT") { return; }
        if (!array_key_exists($property, $this->objectTypes[$objectType])) {
            throw new \InvalidArgumentException(
                "Given unknown property " . var_export($property, true) .
                " for object type '" . var_export($objectType, true)
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
