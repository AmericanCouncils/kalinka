<?php

namespace AC\Kalinka\Authorizer;

abstract class BaseAuthorizer
{
    public function registerContexts($contexts)
    {
    }

    public function registerActions($actions)
    {
    }

    public function can($action, $contextType, $contextObject = null)
    {
        return false;
    }

    abstract protected function getPermission($action, $context);
}
