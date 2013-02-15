<?php

namespace AC\Kalinka\Authorizer;

class RoleAuthorizer extends BaseAuthorizer
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

    protected function getPermission($action, $guardType, $guard)
    {
        foreach ($this->roles as $role) {
            $policies = $this->resolvePolicies($role, $guardType, $action);
            if (is_string($policies)) {
                $policies = [$policies];
            } elseif (is_null($policies)) {
                $policies = [];
            }

            $approved = false;
            foreach ($policies as $policy) {
                $result = $guard->checkPolicy($policy);
                if ($result === true) {
                    $approved = true;
                } elseif ($result === false) {
                    $approved = false;
                    break;
                }
                // If it's not true or false, then this policy abstains
            }
            if ($approved) {
                return true;
            }
        }
        return false;
    }

    private function resolvePolicies($role, $guardType, $action, $history = [])
    {
        $history[] = $role;
        if (array_search($role, $history) !== count($history)-1) {
            // TODO Test this failure condition
            // TODO If we eventually have references that change ct or action,
            // then this error needs to be for the root one and to track
            // role+ct+action for uniqueness in history, not just role
            throw new \LogicError(
                "Recursive loop while resolving \"$guardType\" \"$action\"" .
                " via : " . implode(",", $history)
            );
        }

        // TODO Maybe require that all roles be defined, at least as an empty
        // list? That way we avoid mispelling problems.
        $policies = null;
        $subRoles = [];
        if (array_key_exists($role, $this->rolePolicies)) {
            $roleDef = $this->rolePolicies[$role];
            if (array_key_exists($guardType, $roleDef)) {
                $guardDef = $roleDef[$guardType];
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
                    $guardType,
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
                $guardType,
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
                    $guardType,
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
