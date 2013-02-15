<?php

namespace AC\Kalinka\Authorizer;

abstract class BaseAuthorizer
{
    private $subject;
    public function getSubject() {
        return $this->subject;
    }

    public function __construct($subject = null) {
        $this->subject = $subject;
    }

    private $guardClasses = [];
    protected function registerGuards($guardsMap)
    {
        // TODO Check for invalid argument
        $this->guardClasses =
            array_merge($this->guardClasses, $guardsMap);
    }

    private $guardActions = [];
    protected function registerActions($actionsMap)
    {
        // TODO Check for invalid argument
        foreach ($actionsMap as $guard => $actions) {
            foreach ($actions as $action) {
                $this->guardActions[$guard][$action] = true;
            }
        }
    }

    public function can($action, $guardType, $guardObject = null)
    {
        if (!array_key_exists($guardType, $this->guardClasses)) {
            throw new \InvalidArgumentException(
                "Unknown guard type \"$guardType\""
            );
        }
        $guardClass = $this->guardClasses[$guardType];

        if (
            !array_key_exists($guardType, $this->guardActions) ||
            !array_key_exists($action, $this->guardActions[$guardType])
        ) {
            throw new \InvalidArgumentException(
                "Unknown action \"$action\" for guard type \"$guardType\""
            );
        }

        $guard = new $guardClass($this->subject, $guardObject);
        return $this->getPermission($action, $guardType, $guard);
    }

    abstract protected function getPermission($action, $guardType, $guard);
}
