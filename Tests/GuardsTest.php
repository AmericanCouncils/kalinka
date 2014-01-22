<?php

use AC\Kalinka\Guard\BaseGuard;

use Fixtures\MyAppGuard;
use Fixtures\Document;
use Fixtures\DocumentGuard;
use Fixtures\User;

class BadGuard extends BaseGuard
{
    protected function policyAckNoReturnValue($subject)
    {
        // Do nothing
    }

    protected function policyAckBadReturnValue($subject)
    {
        return 3;
    }
}

class GuardsTest extends KalinkaTestCase
{
    public function testBuiltinAllowPolicy()
    {
        $c = new MyAppGuard();
        $u = new User("guest");
        $this->assertEquals(true, $c->checkPolicy("allow", $u));
    }

    public function testBaseGuardGetPolicies()
    {
        $dg = new DocumentGuard();
        $this->assertEquals(
            [
                "allow",
                "owned",
                "unclassified",
                "userIsOnFirst",
                "userIsOnSecond",
                "usernameHasVowels"
            ],
            $dg->getPolicies()
        );
    }

    public function testGuardSubjectPolicies()
    {
        $guest = new User("jfk");
        $guest_c = new MyAppGuard();
        $this->assertFalse($guest_c->checkPolicy("usernameHasVowels", $guest));

        $user = new User("dave");
        $user_c = new MyAppGuard();
        $this->assertTrue($user_c->checkPolicy("usernameHasVowels", $user));
    }

    public function testGuardObjectPolicies()
    {
        $dg = new DocumentGuard();

        $dave = new User("dave");
        $doc1 = new Document("evan", "Public info");
        $this->assertTrue($dg->checkPolicy("unclassified", $dave, $doc1));
        $this->assertFalse($dg->checkPolicy("owned", $dave, $doc1));
        $this->assertFalse($dg->checkPolicy("userIsOnSecond", $dave));
        $this->assertTrue($dg->checkPolicy("usernameHasVowels", $dave));

        $evan = new User("evan");
        $doc2 = new Document("evan", "Secrets!", true);
        $this->assertFalse($dg->checkPolicy("unclassified", $evan, $doc2));
        $this->assertTrue($dg->checkPolicy("owned", $evan, $doc2));
    }

    public function testPolicyLists()
    {
        $u = new User("guest");
        $c = new MyAppGuard();
        $this->assertTrue($c->checkPolicy("allow", $u));
        $this->assertFalse($c->checkPolicy("userIsOnFirst", $u));

        // The "onFirst" and "onSecond" policies will always return false below
        $this->assertCallsEqual([$c, "checkPolicyList"], [self::X1, $u], [
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
        $u = new User("guest");
        $c = new BadGuard();
        $this->setExpectedException("LogicException", "invalid return value");
        $c->checkPolicy("ackNoReturnValue", $u);
    }

    public function testPolicyFailsWithBadReturnValue()
    {
        $u = new User("guest");
        $c = new BadGuard();
        $this->setExpectedException("LogicException", "invalid return value");
        $c->checkPolicy("ackBadReturnValue", $u);
    }
}
