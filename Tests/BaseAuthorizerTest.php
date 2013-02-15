<?php

use AC\Kalinka\Authorizer\BaseAuthorizer;

class MyAuthorizer extends BaseAuthorizer
{
    protected function getPermission($action, $guardType, $guard) {
        return true;
    }
}

class BaseAuthorizerTest extends KalinkaTestCase
{
    private $auth;
    protected function setUp() {
        $this->auth = new MyAuthorizer();
        $this->auth->registerGuards([
            "comment" => "AC\Kalinka\Guard\BaseGuard",
        ]);
        $this->auth->registerActions([
            "comment" => ["read", "write"]
        ]);
    }

    public function testExplicitAllow() {
        $this->assertTrue($this->auth->can("read", "comment"));
    }

    public function testExceptionOnUnknownGuard() {
        $this->setExpectedException(
            "InvalidArgumentException", "Unknown guard"
        );
        $this->auth->can("write", "something");
    }

    public function testExceptionOnUnknownAction() {
        $this->setExpectedException(
            "InvalidArgumentException", "Unknown action"
        );
        $this->auth->can("nom", "comment");
    }

    public function testExceptionOnUnknownBoth() {
        $this->setExpectedException(
            "InvalidArgumentException", "Unknown guard"
        );
        $this->auth->can("nom", "something");
    }
}
