<?php

namespace AC\Kalinka\Authorizer;

/**
 * Base class for Authorizer classes, which grant or deny access to resources.
 *
 * Implementations of AuthorizerAbstract must provide the getPermission() method.
 * They also must at some point call the registerGuards() and registerActions()
 * methods with the appropriate setup values, before any calls to can() are made.
 * The constructor is a convenient place to do this, just don't forget to call
 * the parent constructor as well.
 */
abstract class AuthorizerAbstract
{
    private $subject;
    /**
     * Returns the subject that was set by the constructor.
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Sets up the authorizer with the given subject.
     *
     * The subject is passed as the first argument to all Guard instances
     * constructed by `can()`.
     */
    public function __construct($subject = null)
    {
        // TODO Set a flag when this is called, check for that flag in can()
        // It's okay if it was set to a null value, we just want to make sure
        // that they didn't forget to call upwards.
        $this->subject = $subject;
    }

    private $resourceGuardClasses = [];
    /**
     * Associates resource types with Guard classes.
     *
     * Resource types are strings passed as the 2nd argument to `can()`,
     * which identify what sort of resource the user is trying to access.
     * These should generally be camel cased with the first character lowercase,
     * like `thisExample`.
     */
    protected function registerGuards($guardsMap)
    {
        // TODO Check for invalid argument
        $this->resourceGuardClasses =
            array_merge($this->resourceGuardClasses, $guardsMap);
    }

    private $resourceActions = [];
    protected function registerActions($actionsMap)
    {
        // TODO Check for invalid argument
        foreach ($actionsMap as $guard => $actions) {
            foreach ($actions as $action) {
                $this->resourceActions[$guard][$action] = true;
            }
        }
    }

    public function can($action, $resType, $guardObject = null)
    {
        if (!array_key_exists($resType, $this->resourceGuardClasses)) {
            throw new \InvalidArgumentException(
                "Unknown resource type \"$resType\""
            );
        }
        $guardClass = $this->resourceGuardClasses[$resType];

        if (
            !array_key_exists($resType, $this->resourceActions) ||
            !array_key_exists($action, $this->resourceActions[$resType])
        ) {
            throw new \InvalidArgumentException(
                "Unknown action \"$action\" for resource type \"$resType\""
            );
        }

        $guard = new $guardClass($this->subject, $guardObject);

        return $this->getPermission($action, $resType, $guard);
    }

    abstract protected function getPermission($action, $resType, $guard);
}
