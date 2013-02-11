<?php

use AC\Kalinka\Authorizer\RoleAuthorizer;

class RoleAuthorizerTest extends PHPUnit_Framework_TestCase
{
    protected function getAuth($roles) {
        $auth = new RoleAuthorizer($roles);

        $auth->registerContexts([
            "comment" => "AC\Kalinka\Context\ObjectlessContext",
            "post" => "AC\Kalinka\Context\ObjectlessContext",
            "system" => "AC\Kalinka\Context\ObjectlessContext",
        ]);
        $auth->registerActions([
            "comment" => ["read", "write"],
            "post" => ["read", "write"],
            "image" => ["upload"],
            "system" => ["reset"]
        ]);
        $auth->registerRolePolicies([
            RoleAuthorizer::COMMON_POLICIES => [
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
                RoleAuthorizer::INCLUDE_ROLE => "contributor",
                "comment" => [
                    RoleAuthorizer::ALL_ACTIONS => "allow"
                ]
            ],
            "post_only_contributor" => [
                "post" => [
                    RoleAuthorizer::INCLUDE_ROLE => "contributor"
                ]
            ],
            "post_write_only_contributor" => [
                "post" => [
                    "write" => [
                        RoleAuthorizer::INCLUDE_ROLE => "contributor"
                    ]
                ]
            ],
            "comment_editor" => [
                RoleAuthorizer::INCLUDE_ROLE => "editor",
                "post" => [
                    "write" => "force_deny"
                ]
            ],
            "admin" => [
                RoleAuthorizer::ALL_CONTEXTS_AND_ACTIONS => "allow"
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

        return $auth;
    }

    public function testGuestPolicies() {
        // Common policies only
        $a = $this->getAuth("guest");
        $this->assertTrue($a->can("read", "comment"));
        $this->assertTrue($a->can("read", "post"));
        $this->assertFalse($a->can("write", "comment"));
        $this->assertFalse($a->can("write", "post"));
        $this->assertFalse($a->can("upload", "image"));
        $this->assertFalse($a->can("reset", "system"));
    }

    public function testContributorPolicies() {
        // Adding to common policies
        $a = $this->getAuth("contributor");
        $this->assertTrue($a->can("read", "comment"));
        $this->assertTrue($a->can("read", "post"));
        $this->assertFalse($a->can("write", "comment"));
        $this->assertTrue($a->can("write", "post"));
        $this->assertTrue($a->can("upload", "image"));
        $this->assertFalse($a->can("reset", "system"));
    }

    public function testEditorPolicies() {
        // Including another role and expanding on it
        $a = $this->getAuth("editor");
        $this->assertTrue($a->can("read", "comment"));
        $this->assertTrue($a->can("read", "post"));
        $this->assertTrue($a->can("write", "comment")); // ALL_ACTIONS
        $this->assertTrue($a->can("write", "post")); // ALL_ACTIONS
        $this->assertTrue($a->can("upload", "image"));
        $this->assertFalse($a->can("reset", "system"));
    }

    public function testPostOnlyContributorPolicies() {
        // Including context definition of another role 
        $a = $this->getAuth("contributor");
        $this->assertTrue($a->can("read", "comment"));
        $this->assertTrue($a->can("read", "post"));
        $this->assertFalse($a->can("write", "comment"));
        $this->assertTrue($a->can("write", "post"));
        $this->assertFalse($a->can("upload", "image"));
        $this->assertFalse($a->can("reset", "system"));
    }

    public function testPostWriteOnlyContributorPolicies() {
        // Including single action definition of another role
        $a = $this->getAuth("contributor");
        $this->assertTrue($a->can("read", "comment"));
        $this->assertTrue($a->can("read", "post"));
        $this->assertFalse($a->can("write", "comment"));
        $this->assertTrue($a->can("write", "post"));
        $this->assertFalse($a->can("upload", "image"));
        $this->assertFalse($a->can("reset", "system"));
    }

    public function testCommentEditorPolicies() {
        $a = $this->getAuth("comment_editor");
        $this->assertTrue($a->can("read", "comment"));
        $this->assertTrue($a->can("read", "post"));
        $this->assertTrue($a->can("write", "comment"));
        $this->assertFalse($a->can("write", "post")); // Force_deny
        $this->assertTrue($a->can("upload", "image")); // Recursive inclusion
        $this->assertFalse($a->can("reset", "system"));
    }

    public function testAdminPolicies() {
        // Unrestricted access
        $a = $this->getAuth("admin");
        $this->assertTrue($a->can("read", "comment"));
        $this->assertTrue($a->can("read", "post"));
        $this->assertTrue($a->can("write", "comment"));
        $this->assertTrue($a->can("write", "post"));
        $this->assertTrue($a->can("upload", "image"));
        $this->assertTrue($a->can("reset", "system"));
    }

    public function testMultipleRoles() {
        $a = $this->getAuth(["image_supplier", "comment_supplier"]);
        $this->assertTrue($a->can("read", "comment"));
        $this->assertTrue($a->can("read", "post"));
        $this->assertTrue($a->can("write", "comment"));
        $this->assertFalse($a->can("write", "post"));
        $this->assertTrue($a->can("upload", "image"));
        $this->assertFalse($a->can("reset", "system"));
    }

    public function testAuthSpecificOverride() {
        $a = $this->getAuth("guest");
        $a->appendPolicies(
            "post" => [
                "write" => [
                    RoleAuthorizer::INCLUDE_ROLE => "contributor"
                ]
            ]
        );
    }
}
