<?php

use Fixtures\BaseAccessDummy;

class OkThing { }
class Yak { }

class BaseAccessTest extends PHPUnit_Framework_TestCase
{
    protected $access;

    protected function setUp() {
        // BaseAccessDummy allows anything on actions/types/properties that
        // start with the string "ok". Everything else it claims to not be
        // set up for.
        $this->access = new BaseAccessDummy();
    }

    public function testBaseAccessAllowsNormalBehavior() {
        $this->assertTrue($this->access->can("okAct", "OkThing"));
        $this->assertTrue($this->access->can("okAct", "OkThing", "okProp"));

        $thing = new OkThing;
        $this->assertTrue($this->access->can("okAct", $thing));
        $this->assertTrue($this->access->can("okAct", $thing, "okProp"));
    }

    public function testExceptionOnInvalidCheckedAction() {
        $this->setExpectedException("InvalidArgumentException", "unknown action");
        $this->access->can("snarf", "OkThing");
    }

    public function testExceptionOnInvalidCheckedObjectType() {
        $this->setExpectedException("InvalidArgumentException", "unknown object");
        $this->access->can("okAct", "Yak");
    }

    public function testExceptionOnInvalidCheckedObject() {
        $yak = new Yak;
        $this->setExpectedException("InvalidArgumentException", "unknown object");
        $this->access->can("okAct", $yak);
    }

    public function testExceptionOnInvalidCheckedProperty() {
        $this->setExpectedException("InvalidArgumentException", "unknown property");
        $this->access->can("okAct", "okThing", "sprinkles");
    }
}
