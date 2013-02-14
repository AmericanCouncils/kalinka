<?php

use AC\Kalinka\Authorizer\RoleAuthorizer;
use AC\Kalinka\Context\BaseContext;

use Fixtures\User;

class AO
{
    public function __construct($user, $lang)
    {
        $this->user = $user;
        $this->lang = $lang;
    }
}

class Role
{
    public function __construct($name) {
        $this->name = $name;
    }
}

class Worklist
{
    public function __construct($aos = [], $users = [], $workflow = null) {
        $this->aos = $aos;
        $this->users = $users;
        $this->workflow = $workflow;
    }
}

class Workflow
{
    public function __construct($map = []) {
        $this->map = $map;
    }
}

class MyUserAuthorizer extends RoleAuthorizer
{
    public function __construct($user)
    {
        $roleNames = [];
        foreach ($user->roles as $role) {
            $roleNames[] = $role->name;
        }
        parent::__construct($roleNames);
    }
}

class UserContext extends BaseContext
{
    protected function isValidSubject()
    {
        return (
            gettype($this->subject) == "object" &&
            get_class($this->subject) == "User"
        );
    }
}

class AOPermissionsTest extends KalinkaTestCase
{
    protected function getAuth($user)
    {
        $auth = new MyUserAuthorizer($user);
        // TODO Configure auth
        return $auth;
    }

    protected function setUp()
    {
        // Something item writers can do that specialists cannot: create new AOs
        
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
        
        $this->ao1 = new AO($this->dsimon, 'ru');
        $this->ao2 = new AO($this->evillemez, 'ru');
        $this->ao3 = new AO(null, 'cn');
        $this->ao4 = new AO(null, 'ar');
        $this->ao5 = new AO($this->evillemez, 'ru');
        $this->ao6 = new AO($this->dsimon, 'ru');
        
        $this->worklist1 = new Worklist(
            [$this->ao1, $this->ao3],
            [$this->evillemez]
        );
        $this->worklist2 = new Worklist(
            [$this->ao1, $this->ao2],
            [$this->dsimon, $this->evillemez],
            new Workflow(['read_ratings' => false])
        );
        
    }
    
    public function testReadAO()
    {
        $auth = $this->getAuth($this->dsimon);
        $this->assertCallsEqual([$auth, "can"], ["read", "ao", self::X1], [
            [true,  $this->ao1], // In my language
            [true,  $this->ao2], // In my language
            [true,  $this->ao3], // Not in my worklist, but in my language
            [false, $this->ao4], // Not in my language
            [true,  $this->ao5], // Not in any worklist, but in my language
            [true,  $this->ao6], // In my language
        ]);
        
        $auth = $this->getAuth($this->evillemez);
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
        $auth = $this->getAuth($this->dsimon);
        $this->assertCallsEqual([$auth, "can"], ["read_ratings", "ao", self::X1], [
            [false, $this->ao1], // In worklist2 with blind rating
            [false, $this->ao2], // In worklist2 with blind rating
            [true,  $this->ao3], // Not in worklist2
            [false, $this->ao4], // Can't read the AO at all
            [true,  $this->ao5], // Not in worklist2
            [true,  $this->ao6], // Not in worklist2
        ]);
        
        $auth = $this->getAuth($this->evillemez);
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
