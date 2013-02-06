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
        $this->setExpectedException("InvalidArgumentException", "invalid action");
        $this->access->can("snarf", "Car");
    }

    public function testExceptionOnInvalidCheckedObjectType() {
        $this->setExpectedException("InvalidArgumentException", "invalid object");
        $this->access->can("go", "Yak");
    }

    public function testExceptionOnInvalidCheckedObject() {
        $yak = new Yak;
        $this->setExpectedException("InvalidArgumentException", "invalid object");
        $this->access->can("go", $yak);
    }

    public function testExceptionOnInvalidCheckedProperty() {
        $this->setExpectedException("InvalidArgumentException", "undefined property");
        $this->access->can("go", "TandemBicycle", "sidecar");
    }
}
