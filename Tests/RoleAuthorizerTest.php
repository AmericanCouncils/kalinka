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
            RoleAuthorizer::DEFAULT_POLICIES => [
                "comment" => [
                    "read" => "allow"
                ],
                "post" => [
                    "read" => "allow"
                ]
            ],
            "guest" => [],
            "contributor" => [
                "post" => [
                    "write" => "allow"
                ],
                "image" => [
                    "upload" => "allow"
                ]
            ],
            "editor" => [
                RoleAuthorizer::ACTS_AS => "contributor",
                "comment" => [
                    RoleAuthorizer::ALL_ACTIONS => "allow"
                ]
            ],
            "post_only_contributor" => [
                "post" => [
                    RoleAuthorizer::ACTS_AS => "contributor"
                ]
            ],
            "post_write_only_contributor" => [
                "post" => [
                    "write" => [
                        RoleAuthorizer::INCLUDE_POLICIES => "contributor"
                    ]
                ]
            ],
            "comment_editor" => [
                RoleAuthorizer::ACTS_AS => "editor",
                "post" => [
                    "write" => [] // No policies, so deny by default
                ]
            ],
            "admin" => [
                RoleAuthorizer::ALL_ACTIONS => "allow"
            ],
            "image_supplier" => [
                "image" => [
                    "upload" => "allow"
                ]
            ],
            "comment_supplier" => [
                "comment" => [
                    "write" => "allow"
                ]
            ],
        ]);
    }
}

class RoleAuthorizerTest extends KalinkaTestCase
{
    public function testGuestPolicies() {
        // Common policies only
        $auth = new OurRoleAuthorizer("guest");
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
        $auth = new OurRoleAuthorizer("contributor");
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
        $auth = new OurRoleAuthorizer("editor");
        $this->assertCallsEqual([$auth, "can"], [self::X1, self::X2], [
            [true,  "read",   "comment"],
            [true,  "read",   "post"],
            [true,  "write",  "comment"], // ALL_ACTIONS
            [true,  "write",  "post"], // ALL_ACTIONS
            [true,  "upload", "image"],
            [false, "reset",  "system"],
        ]);
    }

    public function testPostOnlyContributorPolicies() {
        // Including guard definition of another role
        $auth = new OurRoleAuthorizer("post_only_contributor");
        $this->assertCallsEqual([$auth, "can"], [self::X1, self::X2], [
            [true,  "read",   "comment"],
            [true,  "read",   "post"],
            [false, "write",  "comment"],
            [true,  "write",  "post"],
            [false, "upload", "image"],
            [false, "reset",  "system"],
        ]);
    }

    public function testPostWriteOnlyContributorPolicies() {
        // Including single action definition of another role
        $auth = new OurRoleAuthorizer("post_write_only_contributor");
        $this->assertCallsEqual([$auth, "can"], [self::X1, self::X2], [
            [true,  "read",   "comment"],
            [true,  "read",   "post"],
            [false, "write",  "comment"],
            [true,  "write",  "post"],
            [false, "upload", "image"],
            [false, "reset",  "system"],
        ]);
    }

    public function testCommentEditorPolicies() {
        $auth = new OurRoleAuthorizer("comment_editor");
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
        $auth = new OurRoleAuthorizer("admin");
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
        $auth = new OurRoleAuthorizer(["image_supplier", "comment_supplier"]);
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
        $auth = new OurRoleAuthorizer("guest");
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
