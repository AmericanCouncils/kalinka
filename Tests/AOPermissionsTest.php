<?php

use AC\Kalinka\Authorizer\RoleAuthorizer;
use AC\Kalinka\Guard\BaseGuard;

use Fixtures\MyAppGuard;
use Fixtures\User;

class AO
{
    public function __construct($user, $lang, $worklists = [])
    {
        $this->user = $user;
        $this->lang = $lang;
        $this->worklists = $worklists;
    }
}

class AOGuard extends MyAppGuard
{
    public function policyLanguageMatch($subject, $object)
    {
        return (array_search($object->lang, $subject->langs) !== false);
    }

    public function policyRatingsNotBlinded($subject, $object)
    {
        foreach ($object->worklists as $worklist) {
            if (!is_null($worklist->workflow)) {
                foreach ($worklist->workflow->map as $k => $v) {
                    if ($k == "blind_ratings" && $v === true) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    public function policyWorklistMembership($subject, $object)
    {
        foreach ($object->worklists as $worklist) {
            if (array_search($subject, $worklist->users) !== false) {
                return true;
            }
        }

        return false;
    }

    public function policyOwnership($subject, $object)
    {
        return ($object->user === $subject);
    }

    public function getActions()
    {
      return ["read", "read_ratings"];
    }
}

class Role
{
    public function __construct($name)
    {
        $this->name = $name;
    }
}

class Worklist
{
    public function __construct($users = [], $workflow = null)
    {
        $this->users = $users;
        $this->workflow = $workflow;
    }
}

class Workflow
{
    public function __construct($map = [])
    {
        $this->map = $map;
    }
}

class MyAppAuthorizer extends RoleAuthorizer
{
    public function __construct(User $user)
    {
        $roleNames = [];
        foreach ($user->roles as $role) {
            $roleNames[] = $role->name;
        }
        parent::__construct($user, $roleNames);

        $this->registerGuards([
            "ao" => new AOGuard()
        ]);
        $this->registerRolePolicies([
            "itemDev" => [
                "ao" => [
                    "read" => [
                        "languageMatch",
                        ["worklistMembership", "ownership"]
                    ],
                    "read_ratings" => [
                        "languageMatch",
                        "ratingsNotBlinded",
                        ["worklistMembership", "ownership"]
                    ]
                ]
            ],
            "specialist" => [
                "ao" => [
                    "read" => [
                        "languageMatch",
                    ],
                    "read_ratings" => [
                        "languageMatch",
                        "ratingsNotBlinded",
                    ]
                ]
            ],
        ]);
    }
}

class AOPermissionsTest extends KalinkaTestCase
{
    protected function setUp()
    {
        $this->dsimon = new User(
            'dsimon',
            [new Role('itemDev'), new Role('specialist')],
            ['cn','ru']
        );
        $this->evillemez = new User(
            'evillemez',
            [new Role('itemDev')],
            ['ru']
        );

        $this->worklist1 = new Worklist(
            [$this->evillemez]
        );
        $this->worklist2 = new Worklist(
            [$this->dsimon, $this->evillemez],
            new Workflow(['blind_ratings' => true])
        );

        $this->ao1 = new AO($this->dsimon, 'ru', [$this->worklist1, $this->worklist2]);
        $this->ao2 = new AO($this->evillemez, 'ru', [$this->worklist2]);
        $this->ao3 = new AO(null, 'cn', [$this->worklist1]);
        $this->ao4 = new AO(null, 'ar');
        $this->ao5 = new AO($this->evillemez, 'ru');
        $this->ao6 = new AO($this->dsimon, 'ru');
    }

    public function testReadAO()
    {
        $auth = new MyAppAuthorizer($this->dsimon);
        $this->assertCallsEqual([$auth, "can"], ["read", "ao", self::X1], [
            [true,  $this->ao1], // In my language
            [true,  $this->ao2], // In my language
            [true,  $this->ao3], // Not in my worklist, but in my language
            [false, $this->ao4], // Not in my language
            [true,  $this->ao5], // Not in any worklist, but in my language
            [true,  $this->ao6], // In my language
        ]);

        $auth = new MyAppAuthorizer($this->evillemez);
        $this->assertCallsEqual([$auth, "can"], ["read", "ao", self::X1], [
            [true,  $this->ao1], // In worklist1
            [true,  $this->ao2], // In worklist2
            [false, $this->ao3], // Not in my language
            [false, $this->ao4], // Not in my language
            [true,  $this->ao5], // Not in any worklist, but owned by me
            [false, $this->ao6], // Not owned be me or in my worklists
        ]);
    }

    public function testBlindRatingFromWorkflow()
    {
        $auth = new MyAppAuthorizer($this->dsimon);
        $this->assertCallsEqual([$auth, "can"], ["read_ratings", "ao", self::X1], [
            [false, $this->ao1], // In worklist2 with blind rating
            [false, $this->ao2], // In worklist2 with blind rating
            [true,  $this->ao3], // Not in worklist2
            [false, $this->ao4], // Can't read the AO at all
            [true,  $this->ao5], // Not in worklist2
            [true,  $this->ao6], // Not in worklist2
        ]);

        $auth = new MyAppAuthorizer($this->evillemez);
        $this->assertCallsEqual([$auth, "can"], ["read_ratings", "ao", self::X1], [
            [false, $this->ao1], // In worklist2 with blind rating
            [false, $this->ao2], // In worklist2 with blind rating
            [false, $this->ao3], // Can't read the AO at all
            [false, $this->ao4], // Can't read the AO at all
            [true,  $this->ao5], // Not in worklist2
            [false, $this->ao6], // Can't read the AO at all
        ]);
    }
}
