<?php

use AC\Kalinka\Guard\BaseGuard;

use Fixtures\MyAppGuard;
use Fixtures\Document;
use Fixtures\DocumentGuard;
use Fixtures\User;

class BadGuard extends BaseGuard
{
    protected function policyAckNoReturnValue()
    {
        // Do nothing
    }

    protected function policyAckBadReturnValue()
    {
        return 3;
    }
}

class GuardsTest extends KalinkaTestCase
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

    public function testPolicyLists()
    {
        $c = new MyAppGuard(new User("guest"));
        $this->assertTrue($c->checkPolicy("allow"));
        $this->assertFalse($c->checkPolicy("userIsOnFirst"));

        $this->assertCallsEqual([$c, "checkPolicyList"], [self::X1], [
            [true , "allow"],
            [true , ["allow"]],
            [false, "userIsOnFirst"],
            [false, ["userIsOnFirst"]],
            [true , ["allow", "usernameHasVowels"]],
            [true , ["allow", "allow"]],
            [false, ["allow", "userIsOnFirst"]],
            [false, ["userIsOnFirst", "userIsOnFirst"]],
            [true, ["allow", ["userIsOnFirst", "usernameHasVowels"]]],
            [false, ["allow", ["userIsOnFirst", "userIsOnSecond"]]],
            [false, ["userIsOnSecond", ["userIsOnFirst", "usernameHasVowels"]]],
            [false, ["userIsOnFirst", ["allow", "usernameHasVowels"]]],
            [true, [["allow", "usernameHasVowels"]]],
            [false, null],
            [false, []],
            [false, [[]]]
        ]);
    }

    public function testPolicyFailsWithNoReturnValue()
    {
        $c = new BadGuard();
        $this->setExpectedException("LogicException", "invalid return value");
        $c->checkPolicy("ackNoReturnValue");
    }

    public function testPolicyFailsWithBadReturnValue()
    {
        $c = new BadGuard(new User("guest"));
        $this->setExpectedException("LogicException", "invalid return value");
        $c->checkPolicy("ackBadReturnValue");
    }
}
