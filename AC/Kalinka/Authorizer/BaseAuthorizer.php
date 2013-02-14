<?php

namespace AC\Kalinka\Authorizer;

abstract class BaseAuthorizer
{
    private $subject;
    public function __construct($subject = null) {
        $this->subject = $subject;
    }

    private $contextClasses = [];
    public function registerContexts($contextsMap)
    {
        // TODO Check for invalid argument
        $this->contextClasses =
            array_merge($this->contextClasses, $contextsMap);
    }

    private $contextActions = [];
    public function registerActions($actionsMap)
    {
        // TODO Check for invalid argument
        foreach ($actionsMap as $context => $actions) {
            if (!array_key_exists($context, $this->contextActions)) {
                $this->contextActions[$context] = [];
            }
            foreach ($actions as $action) {
                $this->contextActions[$context][$action] = true;
            }
        }
    }

    public function can($action, $contextType, $contextObject = null)
    {
        if (!array_key_exists($contextType, $this->contextClasses)) {
            throw new \InvalidArgumentException(
                "Unknown context type \"$contextType\""
            );
        }
        $contextClass = $this->contextClasses[$contextType];

        if (
            !array_key_exists($contextType, $this->contextActions) ||
            !array_key_exists($action, $this->contextActions[$contextType])
        ) {
            throw new \InvalidArgumentException(
                "Unknown action \"$action\" for context type \"$contextType\""
            );
        }

        $context = new $contextClass($this->subject, $contextObject);
        return $this->getPermission($action, $context);
    }

    abstract protected function getPermission($action, $context);
}
