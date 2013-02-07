<?php

namespace Kalinka;

abstract class HardcodedAccess extends BaseAccess
{
    private $permissions = [];

    // TODO Assert validity of arguments
    // TODO Raise exception if user tries to create an action or objclass
    // named "ANY" or a property named "DEFAULT"
    protected function allow($action, $object, $property = null, $func = true)
    {
        $property = is_null($property) ? "DEFAULT" : $property;
        $this->permissions[$action][$object][$property][] = $func;
    }

    // TODO Test me
    protected function deny($action, $object, $property = null)
    {
        $this->allow($action, $object, $property, false);
    }

    protected function allowEverything()
    {
        $this->allow("ANY", "ANY");
    }

    private $actions;

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

    protected function isValidAction($action)
    {
        if (!is_array($this->actions)) {
            throw new \LogicException(
                "You must call setupActions on HardcodedAccess before checks"
            );
        }
        return array_key_exists($action, $this->actions);
    }

    protected function isValidObjectType($objectType)
    {
        if (!is_array($this->objectTypes)) {
            throw new \LogicException(
                "You must call setupObjectTypes on HardcodedAccess before checks"
            );
        }
        return array_key_exists($objectType, $this->objectTypes);
    }

    protected function isValidProperty($objectType, $property)
    {
        if (!is_array($this->objectTypes)) {
            throw new \LogicException(
                "You must call setupObjectTypes on HardcodedAccess before checks"
            );
        }
        return (
            array_key_exists($objectType, $this->objectTypes) &&
            array_key_exists($property, $this->objectTypes[$objectType])
        );
    }

    protected function getPrivileges($action, $objectType, $property)
    {
        $property = is_null($property) ? "DEFAULT" : $property;

        $possible_paths = [
            [$action, $objectType],
            ["ANY", $objectType],
            [$action, "ANY"],
            ["ANY", "ANY"],
        ];

        $privs = [];
        foreach ($possible_paths as $path) {
            if (
                array_key_exists($path[0], $this->permissions) &&
                array_key_exists($path[1], $this->permissions[$path[0]]) &&
                array_key_exists($property, $this->permissions[$path[0]][$path[1]])
            ) {
                $privs[] = $this->permissions[$path[0]][$path[1]][$property];
            }
        }
        return $privs;
    }
}
