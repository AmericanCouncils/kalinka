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
    private $roles = [];
    public function getRoles()
    {
        return $this->roles;
    }
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
        // TODO If one of our roles doesn't exist, raise an exception
        foreach ($this->roles as $role) {
            if (
                array_key_exists($role, $this->rolePolicies) &&
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
        }

        return false;
    }
}
