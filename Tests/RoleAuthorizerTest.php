<?php

use AC\Kalinka\Authorizer\RoleAuthorizer;
use AC\Kalinka\Guard\BaseGuard;

class DummyGuard extends BaseGuard
{
    private $actions;
    public function __construct($actions) { $this->actions = $actions; }
    public function getActions() { return $this->actions; }
}

class OurRoleAuthorizer extends RoleAuthorizer
{
    public function __construct($roles, $roleInclusions = [])
    {
        parent::__construct(null, $roles);

        $this->registerRoleInclusions($roleInclusions);
        $this->registerGuards([
            "comment" => new DummyGuard(["read", "write", "disemvowel"]),
            "post" => new DummyGuard(["read", "write"]),
            "system" => new DummyGuard(["reset"]),
            "image" => new DummyGuard(["upload"])
        ]);
        $this->registerRolePolicies([
            "admin" => [], // Handled specially in getRolePermission
            "_common" => [
                "comment" => [
                    "read" => "allow"
                ],
                "post" => [
                    "read" => "allow"
                ]
            ],
            "guest" => [], // No rights beyond the default
            "contributor" => [
                "post" => [
                    "write" => "allow"
                ],
                "image" => [
                    "upload" => "allow"
                ]
            ],
            "editor" => [
                "comment" => [
                    "write" => "allow",
                    "disemvowel" => "allow"
                ],
                "post" => [
                    "write" => "allow"
                ],
                "image" => [
                    "upload" => "allow"
                ]
            ],
            "comment_editor" => [
                "comment" => [
                    "write" => "allow",
                    "disemvowel" => "allow"
                ],
                "post" => [
                    "write" => [] // Should be the same as skipping post section
                ],
                "image" => [
                    "upload" => "allow"
                ]
            ],
            "image_supplier" => [
                "image" => [
                    "upload" => "allow"
                ]
            ],
            "comment_supplier" => [
                "comment" => [
                    "write" => "allow"
                ],
                "post" => [
                    "read" => "allow"
                ]
            ],
        ]);
    }

    protected function getRolePermission($role, $action, $resType, $guard, $subject, $object)
    {
        if ($role == "admin") {
            return true;
        } else {
            return parent::getRolePermission($role, $action, $resType, $guard, $subject, $object);
        }
    }
}

class RoleAuthorizerTest extends KalinkaTestCase
{
    public function testGuestPolicies() {
        // Common policies only
        $auth = new OurRoleAuthorizer(["_common", "guest"]);
        $this->assertCallsEqual([$auth, "can"], [self::X1, self::X2], [
            [true,  "read",   "comment"],
            [true,  "read",   "post"],
            [false, "write",  "comment"],
            [false, "write",  "post"],
            [false, "disemvowel", "comment"],
            [false, "upload", "image"],
            [false, "reset",  "system"],
        ]);
    }

    public function testContributorPolicies() {
        // Adding to common policies
        $auth = new OurRoleAuthorizer(["_common", "contributor"]);
        $this->assertCallsEqual([$auth, "can"], [self::X1, self::X2], [
            [true,  "read",   "comment"],
            [true,  "read",   "post"],
            [false, "write",  "comment"],
            [true,  "write",  "post"],
            [false, "disemvowel", "comment"],
            [true,  "upload", "image"],
            [false, "reset",  "system"],
        ]);
    }

    public function testEditorPolicies() {
        // Including another role and expanding on it
        $auth = new OurRoleAuthorizer(["_common", "editor"]);
        $this->assertCallsEqual([$auth, "can"], [self::X1, self::X2], [
            [true,  "read",   "comment"],
            [true,  "read",   "post"],
            [true,  "write",  "comment"],
            [true,  "write",  "post"],
            [true,  "disemvowel", "comment"],
            [true,  "upload", "image"],
            [false, "reset",  "system"],
        ]);
    }

    public function testCommentEditorPolicies() {
        $auth = new OurRoleAuthorizer(["_common", "comment_editor"]);
        $this->assertCallsEqual([$auth, "can"], [self::X1, self::X2], [
            [true,  "read",   "comment"],
            [true,  "read",   "post"],
            [true,  "write",  "comment"],
            [false, "write",  "post"],
            [true,  "disemvowel", "comment"],
            [true,  "upload", "image"],
            [false, "reset",  "system"],
        ]);
    }

    public function testAdminPolicies() {
        // Unrestricted access due to overriding getRolePermission
        $auth = new OurRoleAuthorizer(["_common", "admin"]);
        $this->assertCallsEqual([$auth, "can"], [self::X1, self::X2], [
            [true,  "read",   "comment"],
            [true,  "read",   "post"],
            [true,  "write",  "comment"],
            [true,  "write",  "post"],
            [true,  "disemvowel", "comment"],
            [true,  "upload", "image"],
            [true,  "reset",  "system"],
        ]);
    }

    public function testMultipleRoles() {
        $auth = new OurRoleAuthorizer(["_common", "image_supplier", "comment_supplier"]);
        $this->assertCallsEqual([$auth, "can"], [self::X1, self::X2], [
            [true,  "read",   "comment"],
            [true,  "read",   "post"],
            [true,  "write",  "comment"],
            [false, "write",  "post"],
            [false, "disemvowel", "comment"],
            [true,  "upload", "image"],
            [false, "reset",  "system"],
        ]);
    }

    public function testRoleInclusions() {
        $auth = new OurRoleAuthorizer(["_common", "guest"], [
            "image" => "editor",
            "post" => ["write" => "editor"]
        ]);
        $this->assertCallsEqual([$auth, "can"], [self::X1, self::X2], [
            [true,  "read",   "comment"],
            [true,  "read",   "post"],
            [false, "write",  "comment"],
            [true, "write",  "post"],
            [false, "disemvowel", "comment"],
            [true, "upload", "image"],
            [false, "reset",  "system"],
        ]);
    }

    public function testExceptionOnInvalidRole() {
        $auth = new OurRoleAuthorizer(["_common", "foo"]);
        $this->setExpectedException("RuntimeException", "No such role");
        $auth->can("read", "comment");
    }

    public function testExceptionOnInvalidRoleInclusion() {
        $auth = new OurRoleAuthorizer(["_common", "guest"],
            [
            "image" => "foo",
            ]
        );
        $this->setExpectedException("RuntimeException", "No such role");
        $auth->can("upload", "image");
    }
}
