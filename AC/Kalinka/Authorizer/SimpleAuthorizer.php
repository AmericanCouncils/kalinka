<?php

namespace AC\Kalinka\Authorizer;

abstract class SimpleAuthorizer extends BaseAuthorizer
{
    private $policyMap = [];
    public function registerPolicies($policies)
    {
        $this->policyMap = $policies;
    }

    protected function getPermission($action, $guardType, $guard) {
        if (!array_key_exists($guardType, $this->policyMap)) {
            throw new \InvalidArgumentError("Unknown guard type '$guardType'");
        }

        if (!array_key_exists($action, $this->policyMap[$guardType])) {
            throw new \InvalidArgumentError("Unknown action '$action' in '$guardType'");
        }

        $policies = $this->policyMap[$guardType][$action];
        return $this->evaluatePolicyList($guard, $policies);
    }
}
