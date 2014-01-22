<?php

use AC\Kalinka\Authorizer\CommonAuthorizer;
use Fixtures\DocumentGuard;

class MyAuthorizer extends CommonAuthorizer
{
    public function __construct($subject = null)
    {
        parent::__construct($subject);
        $this->registerGuards([
            "comment" => new DocumentGuard,
        ]);
    }

    protected function getPermission($action, $guardType, $guard, $subject, $object)
    {
        return true;
    }
}

class BadValuesAuthorizer extends CommonAuthorizer
{
    public function __construct($subject = null)
    {
        parent::__construct($subject);
        $this->registerGuards([
            "something" => new DocumentGuard,
        ]);
    }

    protected function getPermission($action, $resType, $guard, $subject, $object)
    {
        if ($action == "read") {
            // Do nothing
        } else {
            return 3;
        }
    }
}

class InvalidGuardAuthorizer extends CommonAuthorizer
{
    public function __construct($subject = null)
    {
        parent::__construct($subject);
        $this->registerGuards([
            "something" => "foo",
        ]);
    }

    protected function getPermission($action, $resType, $guard, $subject, $object)
    {
        return true;
    }
}

class CommonAuthorizerTest extends KalinkaTestCase
{
    private $auth;
    protected function setUp()
    {
        $this->auth = new MyAuthorizer();
        $this->badAuth = new BadValuesAuthorizer();
        $this->invalidAuth = new InvalidGuardAuthorizer();
    }

    public function testExplicitAllow()
    {
        $this->assertTrue($this->auth->can("read", "comment"));
    }

    public function testExceptionOnUnknownResourceType()
    {
        $this->setExpectedException(
            "InvalidArgumentException", "Unknown resource type"
        );
        $this->auth->can("write", "something");
    }

    public function testExceptionOnUnknownAction()
    {
        $this->setExpectedException(
            "InvalidArgumentException", "Unknown action"
        );
        $this->auth->can("nom", "comment");
    }

    public function testExceptionOnUnknownBoth()
    {
        $this->setExpectedException(
            "InvalidArgumentException", "Unknown resource type"
        );
        $this->auth->can("nom", "something");
    }

    public function testExceptionOnNullGetPermissionResult()
    {
        $this->setExpectedException(
            "LogicException", "invalid getPermission result"
        );
        $this->badAuth->can("read", "something");
    }

    public function testExceptionOnInvalidGetPermissionResult()
    {
        $this->setExpectedException(
            "LogicException", "invalid getPermission result"
        );
        $this->badAuth->can("write", "something");
    }

    public function testExceptionOnInvalidGuard()
    {
        $this->setExpectedException(
            "LogicException", "Invalid guard"
        );
        $this->invalidAuth->can("write", "something");
    }
}
