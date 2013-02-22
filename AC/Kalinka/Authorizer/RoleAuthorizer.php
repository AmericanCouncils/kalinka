<?php

namespace AC\Kalinka\Authorizer;

/**
 * Authorizer that maps named roles to applicable policies.
 *
 * To use this class effectively, derive your own subclass of it and
 * call registerRolePolicies() in your subclass's constructor,
 * in addition to the registerGuards() and registerActions()
 * methods supplied by AuthorizerAbstract.
 *
 * See the <a href="index.html#roles">"Roles" section in README.md</a>
 * for examples.
 */
abstract class RoleAuthorizer extends AuthorizerAbstract
{
    private $rolesValidated = false;

    private $roles = [];
    /**
     * Returns the list of roles.
     */
    public function getRoles()
    {
        return $this->roles;
    }
    /**
     * Returns true if a given role is in the list of roles
     */
    public function hasRole($role)
    {
        return (array_search($role, $this->roles) !== false);
    }

    /**
     * Constructs a RoleAuthorizer.
     *
     * This should be called from a derivative class's constructor.
     *
     * @param $roles A list of roles (strings) that are held by the subject.
     * @param $subject The subject passed as the first argument to all Guard
     *                 instances constructed by `can()`.
     */
    public function __construct($subject, $roles)
    {
        parent::__construct($subject);

        if (is_string($roles)) {
            $roles = [$roles];
        }
        $this->roles = $roles;
    }

    private $roleInclusions = [];
    /**
     * Specifies specific sub-parts of roles to use in access checks.
     *
     * These are considered as though they were additional roles,
     * in that access will be permitted if the included policy lists
     * allow access or if the regular role policy lists do so.
     *
     * You may include the policies for every action of a resource type,
     * or only for particular actions.
     *
     * @param $roleInclusions An associative array mapping resource types to
     *                        role names e.g. `"post" => "editor"`, or to
     *                        arrays mapping particular
     *                        actions to role names e.g. `"post" => ["write"
     *                        => "editor", "read" => "guest"]`
     */
    protected function registerRoleInclusions($roleInclusions)
    {
        foreach ($roleInclusions as $resType => $inclusions) {
            $this->roleInclusions[$resType][] = $inclusions;
        }
    }

    private $rolePolicies = [];
    /**
     * Associates roles with policy settings.
     *
     * See <a href="index.html#roles">"Roles" section
     * in README.md</a> for more details on the argument to this method.
     *
     * @param $rolePolicies A three-level associative array mapping roles,
     *                      resource types, and actions to policy lists,
     *                      e.g. `"contributor" => ["document" => ["read" =>
     *                      "allow", "write" => "owner"]]`
     */
    protected function registerRolePolicies($rolePolicies)
    {
        foreach ($rolePolicies as $role => $resTypes) {
            if (!array_key_exists($role, $this->rolePolicies)) {
                $this->rolePolicies[$role] = [];
            }
            foreach ($resTypes as $resType => $actions) {
                foreach ($actions as $action => $policyList) {
                    $this->rolePolicies[$role][$resType][$action] = $policyList;
                }
            }
        }
    }

    /**
     * Implementation of abstract method from AuthorizerAbstract.
     */
    protected function getPermission($action, $resType, $guard)
    {
        $this->assertValidRoles();

        foreach ($this->roles as $role) {
            if ($this->getRolePermission($role, $action, $resType, $guard)) {
                return true;
            }
        }

        if (array_key_exists($resType, $this->roleInclusions)) {
            foreach ($this->roleInclusions[$resType] as $tgt) {
                $tgtRole = null;
                if (is_string($tgt)) {
                    $tgtRole = $tgt;
                } elseif (is_array($tgt) && array_key_exists($action, $tgt)) {
                    $tgtRole = $tgt[$action];
                }
                if (!is_null($tgtRole)) {
                    if (!array_key_exists($tgtRole, $this->rolePolicies)) {
                        throw new \RuntimeException(
                            "No such role $tgtRole registered"
                        );
                    }
                    if (
                        $this->getRolePermission($tgtRole, $action, $resType, $guard)
                    ) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function getRolePermission($role, $action, $resType, $guard)
    {
        if (
            array_key_exists($resType, $this->rolePolicies[$role]) &&
            array_key_exists($action, $this->rolePolicies[$role][$resType])
        ) {
            $policies = $this->rolePolicies[$role][$resType][$action];
            if (
                !is_null($policies) &&
                $guard->checkPolicyList($policies)
            ) {
                return true;
            }
        }

        return false;
    }

    private function assertValidRoles()
    {
        if ($this->rolesValidated) {
            return true;
        }

        foreach ($this->roles as $role) {
            if (!array_key_exists($role, $this->rolePolicies)) {
                throw new \RuntimeException("No such role $role registered");
            }
        }

        $this->rolesValidated = true;
    }
}
