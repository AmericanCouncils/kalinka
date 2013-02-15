<?php

use AC\Kalinka\Authorizer\BaseAuthorizer;
use AC\Kalinka\Context\BaseContext;

use Fixtures\MyAppContext;
use Fixtures\Document;
use Fixtures\DocumentContext;
use Fixtures\User;

class ContextsTest extends PHPUnit_Framework_TestCase
{
    public function testBuiltinPolicies()
    {
        $c = new MyAppContext(new User("guest"));
        $this->assertEquals(true, $c->checkPolicy("allow"));
        $this->assertEquals(false, $c->checkPolicy("deny"));
        $this->assertEquals(BaseContext::ABSTAIN, $c->checkPolicy("abstain"));
    }

    public function testContextSubjectPolicies()
    {
        $guest_c = new MyAppContext(new User("jfk"));
        $this->assertFalse($guest_c->checkPolicy("usernameHasVowels"));

        $user_c = new MyAppContext(new User("dave"));
        $this->assertTrue($user_c->checkPolicy("usernameHasVowels"));
    }

    public function testContextObjectPolicies()
    {
        $doc1 = new Document("evan", "Public info");
        $c1 = new DocumentContext(new User("dave"), $doc1);
        $this->assertTrue($c1->checkPolicy("unclassified"));
        $this->assertFalse($c1->checkPolicy("owned"));

        $doc2 = new Document("evan", "Secrets!", true);
        $c2 = new DocumentContext(new User("evan"), $doc2);
        $this->assertFalse($c2->checkPolicy("unclassified"));
        $this->assertTrue($c2->checkPolicy("owned"));
    }

    // TODO Test various context error conditions
}
