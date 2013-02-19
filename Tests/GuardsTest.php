<?php

use AC\Kalinka\Guard\BaseGuard;

use Fixtures\MyAppGuard;
use Fixtures\Document;
use Fixtures\DocumentGuard;
use Fixtures\User;

class GuardsTest extends PHPUnit_Framework_TestCase
{
    public function testBuiltinAllowPolicy()
    {
        $c = new MyAppGuard(new User("guest"));
        $this->assertEquals(true, $c->checkPolicy("allow"));
    }

    public function testGuardSubjectPolicies()
    {
        $guest_c = new MyAppGuard(new User("jfk"));
        $this->assertFalse($guest_c->checkPolicy("usernameHasVowels"));

        $user_c = new MyAppGuard(new User("dave"));
        $this->assertTrue($user_c->checkPolicy("usernameHasVowels"));
    }

    public function testGuardObjectPolicies()
    {
        $doc1 = new Document("evan", "Public info");
        $c1 = new DocumentGuard(new User("dave"), $doc1);
        $this->assertTrue($c1->checkPolicy("unclassified"));
        $this->assertFalse($c1->checkPolicy("owned"));

        $doc2 = new Document("evan", "Secrets!", true);
        $c2 = new DocumentGuard(new User("evan"), $doc2);
        $this->assertFalse($c2->checkPolicy("unclassified"));
        $this->assertTrue($c2->checkPolicy("owned"));
    }

    // TODO Test various guard error conditions
}
