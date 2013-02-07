<?php

use Fixtures\Document;
use Fixtures\SimpleAccess;
use Fixtures\SpecialActionAccess;
use Kalinka\DelegatedAccess;

class DelegatedAccessTest extends PHPUnit_Framework_TestCase
{
    protected $access;

    protected function setUp() {
        $this->access = new DelegatedAccess([
            new SimpleAccess("regular"),
            new SpecialActionAccess()
        ]);
    }

    public function testCombinedActions() {
        $this->assertTrue($this->access->can("read", "Fixtures\Document"));
        $this->assertTrue($this->access->can("read", new Document("a","b")));
        $this->assertFalse($this->access->can("create", new Document("a", "b")));
        $this->assertFalse($this->access->can(
            "read", new Document("x", "x", true), "content"
        ));

        $this->assertTrue($this->access->can("nom", "IceCream"));
        $this->assertFalse($this->access->can("nom", "SheetMetal"));
    }

    public function testExceptionOnGloballyUnknownAction() {
        $this->setExpectedException("InvalidArgumentException", "unknown action");
        $this->access->can("snarf", "Fixtures\Document");
    }

    public function testExceptionOnGloballyUnknownObject() {
        $this->setExpectedException("InvalidArgumentException", "unknown object");
        $this->access->can("nom", "Quasar");
    }

    public function testExceptionOnGloballyUnknownProperty() {
        $this->setExpectedException("InvalidArgumentException", "unknown property");
        $this->access->can("nom", "IceCream", "gravy");
    }
}
