<?php

use AC\Kalinka\Authorizer\BaseAuthorizer;

class MyAuthorizer extends BaseAuthorizer
{
    protected function getPolicies($action, $context) {
        return ["allow"];
    }
}

class BaseAuthorizerTest extends PHPUnit_Framework_TestCase
{
    private $auth;
    protected function setUp() {
        $this->auth = new MyAuthorizer();
        $this->auth->registerContexts([
            "comment" => "AC\Kalinka\Context\ObjectlessContext",
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
            "InvalidArgumentException", "unknown context"
        );
        $this->auth->can("write", "something");
    }

    public function testExceptionOnUnknownAction() {
        $this->setExpectedException(
            "InvalidArgumentException", "unknown action"
        );
        $this->auth->can("nom", "comment");
    }

    public function testExceptionOnUnknownBoth() {
        $this->setExpectedException(
            "InvalidArgumentException", "unknown context"
        );
        $this->auth->can("nom", "something");
    }
}
