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
    private $constructorCalled = false;
    private $subject;

    /**
     * Sets up the authorizer with the given subject.
     *
     * @param $subject Passed as the first argument to all Guard instances
     *                 constructed by `can()`.
     */
    public function __construct($subject = null)
    {
        $this->constructorCalled = true;
        $this->subject = $subject;
    }

    private $resourceGuardClasses = [];
    /**
     * Associates resource types with Guard classes.
     *
     * Resource types are strings passed as the 2nd argument to `can()`,
     * which identify what sort of resource the user is trying to access.
     * By convention these are camel cased, like `thisExampleHere`.
     *
     * The class is passed in as a string with the fully-qualified class name.
     *
     * @param $guardsMap An associative array mapping resource types to Guard
     *                   classes, e.g. "document" => "MyApp\Guards\DocumentGuard"
     */
    protected function registerGuards($guardsMap)
    {
        $this->assertCtorCalled();
        $this->resourceGuardClasses =
            array_merge($this->resourceGuardClasses, $guardsMap);
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
     * @param $actionsMap An associative array mapping resource types
     *                    to lists of actions, e.g. "document" => ["read","write"]
     */
    protected function registerActions($actionsMap)
    {
        $this->assertCtorCalled();
        foreach ($actionsMap as $resType => $actions) {
            foreach ($actions as $action) {
                $this->resourceActions[$resType][$action] = true;
            }
        }
    }

    /**
     * Decides if an action on a resource is permitted.
     *
     * This method constructs the appropriate Guard instance with the
     * subject passed to this Authorizer's constructor and the given
     * (optional) object argument. It then passes this Guard instance to the
     * getPermission() method, and returns its result.
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

        $result = $this->getPermission($action, $resType, $guard);
        if (is_bool($result)) {
            return $result;
        } else {
            throw new \LogicException(
                "Got invalid getPermission result " . var_export($result, true)
            );
        }
    }

    /**
     * Method provided by subclasses to implement the Authorizer.
     *
     * This method is called by can() to make the decision about allowing or
     * denying access to perform an action on a resource.
     */
    abstract protected function getPermission($action, $resType, $guard);

    private function assertCtorCalled()
    {
        if (!($this->constructorCalled)) {
            throw new \LogicException(
                "You must call parent::__construct with the subject" .
                " from the constructor of any derivative of AuthorizerAbstract"
            );
        }
    }
}
