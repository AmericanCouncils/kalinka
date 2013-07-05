<?php

use AC\Kalinka\Guard\BaseGuard;
use AC\Kalinka\Authorizer\AuthorizerAbstract;

class MyAuthorizer extends AuthorizerAbstract
{
    public function __construct($subject = null)
    {
        parent::__construct($subject);
        $this->registerGuards([
            "comment" => "AC\Kalinka\Guard\BaseGuard",
        ]);
        $this->registerActions([
            "comment" => ["read", "write"]
        ]);
    }

    protected function getPermission($action, $guardType, $guard, $subject, $object)
    {
        if (!($guard instanceof BaseGuard)) {
            throw new RuntimeException;
        }
        return true;
    }
}

class MyLambdaGuardAuthorizer extends AuthorizerAbstract
{
    public function __construct($subject = null)
    {
        parent::__construct($subject);
        $this->registerGuards([
            "comment" => function() {
                return new BaseGuard();
            }
        ]);
        $this->registerActions([
            "comment" => ["read", "write"]
        ]);
    }

    protected function getPermission($action, $guardType, $guard, $subject, $object)
    {
        if (!($guard instanceof BaseGuard)) {
            throw new RuntimeException;
        }
        return true;
    }
}

class BadValuesAuthorizer extends AuthorizerAbstract
{
    public function __construct($subject = null)
    {
        parent::__construct($subject);
        $this->registerGuards([
            "something" => "AC\Kalinka\Guard\BaseGuard"
        ]);
        $this->registerActions([
            "something" => ["read", "write"]
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

class BadConstructAuthorizer extends AuthorizerAbstract
{
    public function __construct($subject = null)
    {
        // Forgot to call parent::__construct
        $this->registerGuards([
            "something" => "AC\Kalinka\Guard\BaseGuard"
        ]);
        $this->registerActions([
            "something" => ["read", "write"]
        ]);
    }

    protected function getPermission($action, $resType, $guard, $subject, $object)
    {
        return true;
    }
}

class AuthorizerTest extends KalinkaTestCase
{
    private $auth;
    protected function setUp()
    {
        $this->auth = new MyAuthorizer();
        $this->lambAuth = new MyLambdaGuardAuthorizer();
        $this->badAuth = new BadValuesAuthorizer();
    }

    public function testExplicitAllow()
    {
        $this->assertTrue($this->auth->can("read", "comment"));
    }

    public function testLambdaGuardGeneration()
    {
        $this->assertTrue($this->lambAuth->can("read", "comment"));
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

    public function testExceptionOnNotCallingAbstractAuthorizerConstruct()
    {
        $this->setExpectedException(
            "LogicException", "must call parent::__construct"
        );
        $a = new BadConstructAuthorizer;
    }
}
