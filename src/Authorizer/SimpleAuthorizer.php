<?php

namespace AC\Kalinka\Authorizer;

/**
 * Authorizer that applies policies simply based on resource type.
 *
 * See the <a href="index.html#getting-started">"Getting Started" section
 * in README.md</a> for examples.
 */
class SimpleAuthorizer extends CommonAuthorizer
{
    private $policyMap = [];
    /**
     * Associates resource types and actions with policy lists.
     *
     * See <a href="index.html#combining-policies">"Combining Policies" section
     * in README.md</a> for details on how policy lists work.
     *
     * May be called multiple times to register more policy associations.
     *
     * @param $policies Two-level associative array mapping resource types
     *                  and actions to policy lists, e.g. `"document" => ["read" =>
     *                  "allow", "write" => "owner"]`
     */
    public function registerPolicies($policies)
    {
        foreach ($policies as $resType => $actions) {
            foreach ($actions as $action => $policyList) {
                $this->policyMap[$resType][$action] = $policyList;
            }
        }
    }

    /**
     * Implementation of abstract method from CommonAuthorizer.
     */
    protected function getPermission($action, $resType, $guard, $subject, $object)
    {
        if (!array_key_exists($resType, $this->policyMap)) {
            throw new \InvalidArgumentError("Unknown resource type '$resType'");
        }

        if (!array_key_exists($action, $this->policyMap[$resType])) {
            throw new \InvalidArgumentError("Unknown action '$action' in '$resType'");
        }

        $policies = $this->policyMap[$resType][$action];

        return $guard->checkPolicyList($policies, $subject, $object);
    }
}
