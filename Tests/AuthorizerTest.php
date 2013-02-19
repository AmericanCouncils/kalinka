<?php

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

    protected function getPermission($action, $guardType, $guard)
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
}
