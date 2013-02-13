<?php

namespace AC\Kalinka\Authorizer;

class RoleAuthorizer extends BaseAuthorizer
{
    const COMMON_POLICIES = "KALINKA_ROLEAUTH_KEY_COMMON_POLICIES";
    const INCLUDE_ROLE = "KALINKA_ROLEAUTH_KEY_INCLUDE_ROLE";
    const ALL_ACTIONS = "KALINKA_ROLEAUTH_KEY_ALL_ACTIONS";
    const ALL_CONTEXTS_AND_ACTIONS = "KALINKA_ROLEAUTH_KEY_ALL_CONTEXTS_AND_ACTIONS";

    public function __construct($roles)
    {
    }

    public function registerRolePolicies($rolePolicies)
    {
    }

    public function appendPolicies($policies)
    {
    }

    protected function getPermission($action, $context)
    {
    }
}
