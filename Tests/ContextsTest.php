<?php

use AC\Kalinka\Authorizer\BaseAuthorizer;
use AC\Kalinka\Context\BaseContext;

use Fixtures\Document;
use Fixtures\RoleSubjectContext;

class ContextsTest extends PHPUnit_Framework_TestCase
{
    public function testBuiltinPolicies()
    {
        $c = new RoleSubjectContext("guest");
        $this->assertEquals(true, $c->checkPolicy("allow"));
        $this->assertEquals(false, $c->checkPolicy("deny"));
        $this->assertEquals(BaseContext::ABSTAIN, $c->checkPolicy("abstain"));
    }

    public function testCustomPolicies()
    {
        $guest_c = new RoleSubjectContext("guest");
        $this->assertFalse($guest_c->checkPolicy("nonGuest"));

        $user_c = new RoleSubjectContext("user");
        $this->assertTrue($user_c->checkPolicy("nonGuest"));
    }

    // TODO Test various context error conditions
}
