<?php

namespace Kalinka;

/**
 * Parent for Access classes with permissions specified directly in PHP.
 *
 * This class allows you to write in the CanCan style, with all permissions
 * specified upfront when the class is instantiated.
 *
 * You must derive this class to use it, since the methods used to set up
 * everything (actions, objects, permissions, etc.) are protected. You must
 * call setupActions and setupObjectTypes before doing any access
 * checks.
 *
 * See BaseAccess for information on Kalinka's overall strategy for figuring
 * out access rights, particularly when we have more than one possible permission
 * that could apply.
 */
abstract class HardcodedAccess extends BaseAccess
{
    private $permissions = [];

    // TODO Assert validity of arguments
    // TODO Raise exception if user tries to create an action or objclass
    // named "ANY" or a property named "DEFAULT"
    /**
     * Specify a privilege for an action on a particular object type & property.
     *
     * You may specify "ANY" for $action and/or $objectType to have
     * this permission apply to all actions and/or object types respectively.
     */
    protected function allow($action, $objectType, $property = null, $priv = true)
    {
        $property = is_null($property) ? "DEFAULT" : $property;
        $this->permissions[$action][$objectType][$property][] = $priv;
    }

    // TODO Test me
    /**
     * Convenience method to specify a hard denial privilege.
     */
    protected function deny($action, $objectType, $property = null)
    {
        $this->allow($action, $objectType, $property, false);
    }

    /**
     * Convenience method to specify a privilege that applies to everything.
     */
    protected function allowEverything($priv = true)
    {
        $this->allow("ANY", "ANY", null, $priv);
    }

    private $actions;

    /**
     * Sets the list of recognized actions.
     *
     * @param $actions A list of strings, each of which is a valid action.
     */
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

    /**
     * Sets the list of recognized object types and their propertries.
     *
     * @param $objectTypes A list of strings, each of which is a recognized
     *                     object type. If there is a string key for an item,
     *                     then than key is taken as the object type name, and
     *                     the value is a list of valid properties.
     */
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
