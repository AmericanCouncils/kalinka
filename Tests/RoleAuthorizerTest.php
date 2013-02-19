<?php

use AC\Kalinka\Authorizer\RoleAuthorizer;

class OurRoleAuthorizer extends RoleAuthorizer
{
    public function __construct($roles)
    {
        parent::__construct($roles);

        // We aren't using any guard objects, so we can just use
        // the BaseGuard class, which expects null for subject and
        // object, for all these things
        $this->registerGuards([
            "comment" => "AC\Kalinka\Guard\BaseGuard",
            "post" => "AC\Kalinka\Guard\BaseGuard",
            "system" => "AC\Kalinka\Guard\BaseGuard",
            "image" => "AC\Kalinka\Guard\BaseGuard",
        ]);
        $this->registerActions([
            "comment" => ["read", "write"],
            "post" => ["read", "write"],
            "image" => ["upload"],
            "system" => ["reset"]
        ]);
        $this->registerRolePolicies([
            "_common" => [
                "comment" => [
                    "read" => "allow"
                ],
                "post" => [
                    "read" => "allow"
                ]
            ],
            "guest" => [
            ],
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
                    "write" => "allow"
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
                    "write" => "allow"
                ],
                "post" => [
                    "write" => []
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

    protected function getPermission($action, $resType, $guard)
    {
        if (array_search("admin", $this->getRoles()) !== FALSE) {
            return true;
        } else {
            return parent::getPermission($action, $resType, $guard);
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
            [true,  "write",  "comment"], // ALL_ACTIONS
            [true,  "write",  "post"], // ALL_ACTIONS
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
            [false, "write",  "post"], // Force_deny
            [true,  "upload", "image"], // Recursive inclusion
            [false, "reset",  "system"],
        ]);
    }

    public function testAdminPolicies() {
        // Unrestricted access
        $auth = new OurRoleAuthorizer(["_common", "admin"]);
        $this->assertCallsEqual([$auth, "can"], [self::X1, self::X2], [
            [true,  "read",   "comment"],
            [true,  "read",   "post"],
            [true,  "write",  "comment"],
            [true,  "write",  "post"],
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
            [true,  "upload", "image"],
            [false, "reset",  "system"],
        ]);
    }

    public function testAuthAppend() {
        // TODO Make this happen as though I were adding in another tiny role
        $auth = new OurRoleAuthorizer(["_common", "guest"]);
        $auth->appendPolicies([
            "post" => [
                "write" => [
                    RoleAuthorizer::INCLUDE_POLICIES => "contributor"
                ]
            ]
        ]);
        // TODO Assert something here
    }

    // TODO Allow us to block all role-supplied policies for a guard/action

    // TODO Test merging of policy lists via INCLUDE_POLICIES
}
