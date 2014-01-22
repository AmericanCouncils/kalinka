<?php

namespace AC\Kalinka\Authorizer;

use AC\Kalinka\Guard\IGuard;

/**
 * Base class for Authorizer classes, which grant or deny access to resources.
 *
 * Implementations of CommonAuthorizer must provide the getPermission() method.
 * They also must at some point call the registerGuards() and registerActions()
 * methods with the appropriate setup values, before any calls to can() are made.
 * The constructor is a convenient place to do this, just don't forget to call
 * the parent constructor as well.
 */
abstract class CommonAuthorizer implements IAuthorizer
{
    private $subject;

    /**
     * Sets up the authorizer with the given subject.
     *
     * @param $subject Passed as the first argument to all policy method calls.
     */
    public function __construct($subject = null)
    {
        $this->subject = $subject;
    }

    private $guards = [];
    /**
     * Associates resource types with Guard instances.
     *
     * Resource types are strings passed as the 2nd argument to `can()`,
     * which identify what sort of resource the user is trying to access.
     * By convention these are camel cased, like `thisExampleHere`.
     *
     * May be called multiple times to register more guards.
     *
     * @param $guardsMap An associative array mapping resource types to Guards,
     *                   e.g. ["document" => new MyApp\Guards\DocumentGuard]
     */
    public function registerGuards($guardsMap)
    {
        $this->guards = array_merge($this->guards, $guardsMap);
    }

    private $resourceActions = [];
    /**
     * Associates each resource type with a list of actions.
     *
     * Resource types are just descriptive strings; see registerGuards() for
     * more information on that.
     *
     * Actions are also just strings. By convention they are camel cased.
     * Try to stick with a consistent scheme for actions among your various
     * resource types, for example using "read" and "write", or using the
     * four CRUD verbs.
     *
     * May be called multiple times to register more actions.
     *
     * @param $actionsMap An associative array mapping resource types
     *                    to lists of actions, e.g. "document" => ["read","write"]
     */
    public function registerActions($actionsMap)
    {
        foreach ($actionsMap as $resType => $actions) {
            foreach ($actions as $action) {
                $this->resourceActions[$resType][$action] = true;
            }
        }
    }

    /**
     * Decides if an action on a resource is permitted.
     *
     * This method simply passes the appropriate Guard instance to the
     * getPermission() method along with all the arguments describing the action
     * to check, and returns that function's result.
     *
     * @param $action The action that we want to check, a string
     * @param $resType The resource type we're checking access to, a string
     * @param $guardObject (Optional) The object to pass to the Guard class
     *                     constructor. This can be `null` if that's appropriate for
     *                     the Guard class, e.g. if this is a "virtual" resource
     *                     (see Guard\BaseGuard for
     *                     more information on this).
     * @return Boolean
     */
    public function can($action, $resType, $guardObject = null)
    {
        if (!array_key_exists($resType, $this->guards)) {
            throw new \InvalidArgumentException(
                "Unknown resource type \"$resType\""
            );
        }

        if (
            !array_key_exists($resType, $this->resourceActions) ||
            !array_key_exists($action, $this->resourceActions[$resType])
        ) {
            throw new \InvalidArgumentException(
                "Unknown action \"$action\" for resource type \"$resType\""
            );
        }

        $guard = $this->guards[$resType];
        if (!is_a($guard, IGuard::class)) {
            throw new \LogicException("Invalid guard for $resType");
        }

        $result = $this->getPermission($action, $resType, $guard, $this->subject, $guardObject);
        if (is_bool($result)) {
            return $result;
        } else {
            throw new \LogicException(
                "Got invalid getPermission result: " . var_export($result, true)
            );
        }
    }

    /**
     * Method provided by subclasses to implement the Authorizer.
     *
     * This method is called by can() to make the decision about allowing or
     * denying access to perform an action on a resource.
     */
    abstract protected function getPermission($action, $resType, $guard, $subject, $object);
}
