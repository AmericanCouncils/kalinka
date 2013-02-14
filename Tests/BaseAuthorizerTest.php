<?php

use AC\Kalinka\Authorizer\BaseAuthorizer;

class MyAuthorizer extends BaseAuthorizer
{
    protected function getPermission($action, $contextType, $context) {
        return true;
    }
}

class BaseAuthorizerTest extends KalinkaTestCase
{
    private $auth;
    protected function setUp() {
        $this->auth = new MyAuthorizer();
        $this->auth->registerContexts([
            "comment" => "AC\Kalinka\Context\BaseContext",
        ]);
        $this->auth->registerActions([
            "comment" => ["read", "write"]
        ]);
    }

    public function testExplicitAllow() {
        $this->assertTrue($this->auth->can("read", "comment"));
    }

    public function testExceptionOnUnknownContext() {
        $this->setExpectedException(
            "InvalidArgumentException", "Unknown context"
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
            "InvalidArgumentException", "Unknown context"
        );
        $this->auth->can("nom", "something");
    }
}
