<?php

namespace AC\Kalinka\Authorizer;

/**
 * Authorizer that maps named roles to applicable policies.
 *
 * To use this class effectively, derive your own subclass of it and
 * call registerRolePolicies() in your subclass's constructor,
 * in addition to the registerGuards() and registerActions()
 * methods supplied by AuthorizerAbstract.
 */
class RoleAuthorizer extends AuthorizerAbstract
{
    const DEFAULT_POLICIES = "KALINKA_ROLEAUTH_KEY_DEFAULT_POLICIES";
    const ACTS_AS = "KALINKA_ROLEAUTH_KEY_ACTS_AS";
    const INCLUDE_POLICIES = "KALINKA_ROLEAUTH_KEY_INCLUDE_POLICIES";
    const ALL_ACTIONS = "KALINKA_ROLEAUTH_KEY_ALL_ACTIONS";

    private $roles = [];
    public function getRoles()
    {
        return $this->roles;
    }

    public function __construct($roles, $subject = null)
    {
        parent::__construct($subject);

        if (is_string($roles)) {
            $roles = [$roles];
        }
        $this->roles = $roles;
    }

    private $rolePolicies = [];
    protected function registerRolePolicies($rolePolicies)
    {
        // TODO Check for validity
        $this->rolePolicies = $rolePolicies;
    }

    public function appendPolicies($policies)
    {
    }

    protected function getPermission($action, $resType, $guard)
    {
        foreach ($this->roles as $role) {
            $policies = $this->resolvePolicies($role, $resType, $action);
            if ($this->evaluatePolicyList($guard, $policies)) {
                return true;
            }
        }

        return false;
    }

    private function resolvePolicies($role, $resType, $action, $history = [])
    {
        $history[] = $role;
        if (array_search($role, $history) !== count($history)-1) {
            // TODO Test this failure condition
            // TODO If we eventually have references that change ct or action,
            // then this error needs to track
            // role+ct+action for uniqueness in history, not just role
            throw new \LogicError(
                "Recursive loop while resolving \"$resType\" \"$action\"" .
                " via : " . implode(",", $history)
            );
        }

        // TODO Maybe require that all roles be defined, at least as an empty
        // list? That way we avoid mispelling problems.
        $policies = null;
        $subRoles = [];
        if (array_key_exists($role, $this->rolePolicies)) {
            $roleDef = $this->rolePolicies[$role];
            if (array_key_exists($resType, $roleDef)) {
                $guardDef = $roleDef[$resType];
                if (array_key_exists($action, $guardDef)) {
                    $policies = $guardDef[$action];
                } elseif (array_key_exists(self::ALL_ACTIONS, $guardDef)) {
                    $policies = $guardDef[self::ALL_ACTIONS];
                } elseif (array_key_exists(self::ACTS_AS, $guardDef)) {
                    $refRoles = $guardDef[self::ACTS_AS];
                    if (is_string($refRoles)) {
                        $refRoles = [$refRoles];
                    }
                    $subRoles = array_merge($refRoles, $subRoles);
                }
            }

            if (
                is_null($policies) &&
                array_key_exists(self::ALL_ACTIONS, $roleDef)
            ) {
                $policies = $roleDef[self::ALL_ACTIONS];
            }

            if (
                is_null($policies) &&
                array_key_exists(self::ACTS_AS, $roleDef)
            ) {
                $refRoles = $roleDef[self::ACTS_AS];
                if (is_string($refRoles)) {
                    $refRoles = [$refRoles];
                }
                $subRoles = array_merge($refRoles, $subRoles);
            }
        }

        if (is_string($policies)) {
            $policies = [$policies];
        }

        // TODO Test that ACTS_AS at the action level takes priority over
        // any ACTS_AS at the guard level

        if (is_null($policies)) {
            // Reversed so that latter roles override earlier ones
            foreach (array_reverse($subRoles) as $subRole) {
                $policies = $this->resolvePolicies(
                    $subRole,
                    $resType,
                    $action,
                    $history
                );
                if (!is_null($policies)) {
                    break;
                }
            }
        }

        if (
            is_null($policies) &&
            $role != self::DEFAULT_POLICIES &&
            array_key_exists(self::DEFAULT_POLICIES, $this->rolePolicies)
        ) {
            $policies = $this->resolvePolicies(
                self::DEFAULT_POLICIES,
                $resType,
                $action,
                $history
            );
        }

        if (
            !is_null($policies) &&
            array_key_exists(self::INCLUDE_POLICIES, $policies)
        ) {
            $includedRoles = $policies[self::INCLUDE_POLICIES];
            if (is_string($includedRoles)) {
                $includedRoles = [$includedRoles];
            }
            unset($policies[self::INCLUDE_POLICIES]);

            foreach ($includedRoles as $includedRole) {
                $includedPolicies = $this->resolvePolicies(
                    $includedRole,
                    $resType,
                    $action,
                    $history
                );
                if (!is_null($includedPolicies)) {
                    $policies = array_merge($policies, $includedPolicies);
                }
            }
        }

        return $policies;
    }
}
