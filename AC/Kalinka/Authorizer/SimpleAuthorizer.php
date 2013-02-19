<?php

namespace AC\Kalinka\Authorizer;

/**
 * Authorizer that applies policies simply based on resource type.
 *
 * To use this class effectively, derive your own subclass of it and
 * call registerPolicies() in your subclass's constructor,
 * in addition to the registerGuards() and registerActions()
 * methods supplied by AuthorizerAbstract.
 */
abstract class SimpleAuthorizer extends AuthorizerAbstract
{
    private $policyMap = [];
    public function registerPolicies($policies)
    {
        $this->policyMap = $policies;
    }

    protected function getPermission($action, $resType, $guard) {
        if (!array_key_exists($resType, $this->policyMap)) {
            throw new \InvalidArgumentError("Unknown resource type '$resType'");
        }

        if (!array_key_exists($action, $this->policyMap[$resType])) {
            throw new \InvalidArgumentError("Unknown action '$action' in '$resType'");
        }

        $policies = $this->policyMap[$resType][$action];
        return $this->evaluatePolicyList($guard, $policies);
    }
}
