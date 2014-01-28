<?php

namespace AC\Kalinka\Authorizer;

/**
 * Authorizer that maps named roles to applicable policies.
 *
 * See the <a href="index.html#roles">"Roles" section in README.md</a>
 * for examples.
 */
class RoleAuthorizer extends CommonAuthorizer
{
    private $rolesValidated = false;

    private $roles = [];

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

    private $rolePolicies = [];

    /**
     * Associates roles with policy settings.
     *
     * See <a href="index.html#roles">"Roles" section
     * in README.md</a> for more details on the argument to this method.
     *
     * May be called multiple times to add additional role->policy associations.
     *
     * @param $rolePolicies A three-level associative array mapping roles,
     *                      resource types, and actions to policy lists,
     *                      e.g. `"contributor" => ["document" => ["read" =>
     *                      "allow", "write" => "owner"]]`
     */
    public function registerRolePolicies($rolePolicies)
    {
        foreach ($rolePolicies as $role => $resTypes) {
            if (!isset($this->rolePolicies[$role])) {
                $this->rolePolicies[$role] = [];
            }

            foreach ($resTypes as $resType => $actions) {
                if (!is_array($actions)) {
                    continue;
                }

                if (!isset($this->rolePolicies[$role][$resType])) {
                    $this->rolePolicies[$role][$resType] = [];
                }

                foreach ($actions as $action => $policyList) {
                    $this->rolePolicies[$role][$resType][$action] = $policyList;
                }
            }
        }
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
     * May be called multiple times to add additional role inclusions.
     *
     * @param $roleInclusions An associative array mapping resource types to
     *                        role names e.g. `"post" => "editor"`, or to
     *                        arrays mapping particular
     *                        actions to role names e.g. `"post" => ["write"
     *                        => "editor", "read" => "guest"]`
     */
    public function registerRoleInclusions($roleInclusions)
    {
        foreach ($roleInclusions as $resType => $inclusions) {
            $this->roleInclusions[$resType][] = $inclusions;
        }
    }

    /**
     * Implementation of abstract method from CommonAuthorizer.
     */
    protected function getPermission($action, $resType, $guard, $subject, $object)
    {
        $this->assertValidRoles();

        //NOTE: this is a default whitelist?
        foreach ($this->roles as $role) {
            if (
                $this->getRolePermission($role, $action, $resType, $guard, $subject, $object)
            ) {
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
                        $this->getRolePermission($tgtRole, $action, $resType, $guard, $subject, $object)
                    ) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Used by getPermission to determine access checks under a given role.
     *
     * If you want to implement roles with unusual semantics (e.g. a superadmin
     * role which always has access to everything), override this method.
     */
    protected function getRolePermission($role, $action, $resType, $guard, $subject, $object)
    {
        if (
            array_key_exists($resType, $this->rolePolicies[$role]) &&
            array_key_exists($action, $this->rolePolicies[$role][$resType])
        ) {
            $policies = $this->rolePolicies[$role][$resType][$action];
            if (
                !is_null($policies) &&
                $guard->checkPolicyList($policies, $subject, $object)
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
