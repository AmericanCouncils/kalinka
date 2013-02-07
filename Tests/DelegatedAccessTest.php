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

        $this->assertTrue($this->access->can("nom", "IceCream"));
        $this->assertFalse($this->access->can("nom", "SheetMetal"));
    }
}
