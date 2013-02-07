<?php

namespace Kalinka;

class HardcodedAccess extends BaseAccess
{
    private $permissions = [];

    // TODO Assert validity of arguments
    // TODO Raise exception if user tries to create an action or objclass
    // named "ANY"
    protected function allow($action, $object, $property = null, $func = true)
    {
        $property = is_null($property) ? "DEFAULT" : $property;
        $this->permissions[$action][$object][$property][] = $func;
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
        if ($property == "DEFAULT") {
            return true;
        } else {
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
    }

    protected function check($action, $obj_class, $object, $property)
    {
        $possible_paths = [
            [$action, $obj_class],
            ["ANY", $obj_class],
            [$action, "ANY"],
            ["ANY", "ANY"],
        ];
        foreach ($possible_paths as $path) {
            if (
                array_key_exists($path[0], $this->permissions) &&
                array_key_exists($path[1], $this->permissions[$path[0]])
            ) {
                $src = $this->permissions[$path[0]][$path[1]];
            } else {
                continue;
            }

            $funcs = null;
            if (array_key_exists($property, $src)) {
                $funcs = $src[$property];
            } elseif ($property != "DEFAULT" && array_key_exists("DEFAULT", $src)) {
                // Only use the permissions for property DEFAULT if there aren't
                // any permissions that are specifically for this property
                $funcs = $src["DEFAULT"];
            } else {
                continue;
            }

            if (is_null($object)) {
                return count($funcs) > 0;
            } else {
                foreach ($funcs as $f) {
                    if ($f === true) {
                        return true;
                    } elseif ($f($this, $object) === true) {
                        // TODO Complain if the function returns any non-bool
                        // e.g. to catch if they forgot the return statement
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
