<?php

use AC\Kalinka\Authorizer\BaseAuthorizer;
use AC\Kalinka\Context\BaseContext;

use Fixtures\Document;
use Fixtures\RoleSubjectContext;
use Fixtures\DocumentContext;

class ContextsTest extends PHPUnit_Framework_TestCase
{
    public function testBuiltinPolicies()
    {
        $c = new RoleSubjectContext("guest");
        $this->assertEquals(true, $c->checkPolicy("allow"));
        $this->assertEquals(false, $c->checkPolicy("deny"));
        $this->assertEquals(BaseContext::ABSTAIN, $c->checkPolicy("abstain"));
    }

    public function testSimpleCustomPolicies()
    {
        $guest_c = new RoleSubjectContext("guest");
        $this->assertFalse($guest_c->checkPolicy("nonGuest"));

        $user_c = new RoleSubjectContext("user");
        $this->assertTrue($user_c->checkPolicy("nonGuest"));
    }

    public function testContextObjects()
    {
        $doc1 = new Document("dave", "Public info");
        $c1 = new DocumentContext("user", $doc1);
        $this->assertTrue($c1->checkPolicy("unclassified"));

        $doc2 = new Document("evan", "Secrets!", true);
        $c2 = new DocumentContext("user", $doc2);
        $this->assertFalse($c2->checkPolicy("unclassified"));
    }

    // TODO Test various context error conditions
}
