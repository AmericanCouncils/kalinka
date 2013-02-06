<?php

use Fixtures\BaseAccessWaldo;

class Bus { }
class Yak { }

class BaseAccessTest extends PHPUnit_Framework_TestCase
{
    protected $access;

    protected function setUp() {
        $this->access = $a = new BaseAccessWaldo(
            ["go", "stop"],
            [
            "Bus",
            "Car",
            "TandemBicycle" => ["front", "back"]
            ]
        );
    }

    public function testBaseAccessAllowsNormalBehavior() {
        $this->assertTrue($this->access->can("stop", "Car"));
        $this->assertTrue($this->access->can("go", "TandemBicycle", "front"));

        $bus = new Bus;
        $this->assertTrue($this->access->can("go", $bus));
    }

    public function testExceptionOnInvalidCheckedAction() {
        $this->setExpectedException("InvalidArgumentException", "unknown action");
        $this->access->can("snarf", "Car");
    }

    public function testExceptionOnInvalidCheckedObjectType() {
        $this->setExpectedException("InvalidArgumentException", "unknown object");
        $this->access->can("go", "Yak");
    }

    public function testExceptionOnInvalidCheckedObject() {
        $yak = new Yak;
        $this->setExpectedException("InvalidArgumentException", "unknown object");
        $this->access->can("go", $yak);
    }

    public function testExceptionOnInvalidCheckedProperty() {
        $this->setExpectedException("InvalidArgumentException", "unknown property");
        $this->access->can("go", "TandemBicycle", "sidecar");
    }

    public function testExceptionOnInvalidSetupAction() {
        $this->setExpectedException("InvalidArgumentException", "invalid action");
        $foo = new BaseAccessWaldo(["foo", 3, "bar"], ["Narf", "Bork"]);
    }

    public function testExceptionOnInvalidSetupObjectType() {
        $this->setExpectedException("InvalidArgumentException", "invalid object");
        $foo = new BaseAccessWaldo(["foo", "bar"], [null, "Narf", "Bork"]);
    }

    public function testExceptionOnInvalidSetupObjectProperty() {
        $this->setExpectedException("InvalidArgumentException", "invalid property name");
        $foo = new BaseAccessWaldo(["foo", "bar"], ["Narf", "Bork" => ["abc", null]]);
    }

    public function testExceptionOnInvalidSetupObjectPropertyList() {
        $this->setExpectedException("InvalidArgumentException", "invalid property list");
        $foo = new BaseAccessWaldo(["foo", "bar"], ["Narf", "Bork" => true]);
    }
}
