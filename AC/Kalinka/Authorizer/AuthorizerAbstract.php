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
    public function getSubject() {
        return $this->subject;
    }

    public function __construct($subject = null) {
        // TODO Set a flag when this is called, check for that flag in can()
        $this->subject = $subject;
    }

    private $resourceGuardClasses = [];
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

    protected function evaluatePolicyList($guard, $policies)
    {
        if (is_string($policies)) {
            $policies = [$policies];
        } elseif (is_null($policies)) {
            $policies = [];
        }

        $approved = false;
        foreach ($policies as $policy) {
            $result = $guard->checkPolicy($policy);
            if ($result === true) {
                $approved = true;
            } elseif ($result === false) {
                $approved = false;
                break;
            }
            // If it's not true or false, then this policy abstains
        }
        return $approved;
    }
}
