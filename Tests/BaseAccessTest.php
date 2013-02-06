<?php

use Fixtures\Document;
use Fixtures\SimpleAccess;

class BaseAccessTest extends PHPUnit_Framework_TestCase
{
    protected $openDoc;
    protected $classifiedDoc;

    protected $regularAccess;

    protected function setUp()
    {
        $this->openDoc = new Document("Open Document", "Anyone can read me");
        $this->classifiedDoc = new Document("Classified", "Top Secret Info!", true);

        $this->guestAccess = new SimpleAccess("guest");
        $this->regularAccess = new SimpleAccess("regular");
        $this->superAccess = new SimpleAccess("super");
    }

    /*******
     * Guest access level
     *******/

    public function testGuestCannotReadSomeDocs()
    {
        $this->assertFalse($this->guestAccess->can(
            "read", "Fixtures\Document"
        ));
        $this->assertFalse($this->guestAccess->can(
            "read", "Fixtures\Document", "content"
        ));
    }

    public function testGuestCannotReadSpecificDocs()
    {
        $this->assertFalse($this->guestAccess->can(
            "read", $this->openDoc
        ));
        $this->assertFalse($this->guestAccess->can(
            "read", $this->openDoc, "content"
        ));
        $this->assertFalse($this->guestAccess->can(
            "read", $this->classifiedDoc
        ));
        $this->assertFalse($this->guestAccess->can(
            "read", $this->classifiedDoc, "content"
        ));
    }

    public function testGuestCannotCreateDocument()
    {
        $this->assertFalse($this->guestAccess->can(
            "create", "Fixtures\Document"
        ));
        $this->assertFalse($this->guestAccess->can(
            "create", new Document("foo", "bar")
        ));
    }

    /*******
     * Regular access level
     *******/

    public function testRegularAccessCanReadSomeDocs()
    {
        // At least some document properties can be read
        $this->assertTrue($this->regularAccess->can(
            "read", "Fixtures\Document"
        ));
    }

    public function testRegularAccessCanReadSomeDocsContent()
    {
        // At least some documents' contents can be read
        $this->assertTrue($this->regularAccess->can(
            "read", "Fixtures\Document", "content"
        ));
    }

    public function testRegularAccessCanReadOpenDoc()
    {
        // This particular document can be read
        $this->assertTrue($this->regularAccess->can(
            "read", $this->openDoc
        ));
    }

    public function testRegularAccessCanReadOpenDocContent()
    {
        // A specific part of this document can be read
        $this->assertTrue($this->regularAccess->can(
            "read", $this->openDoc, "content"
        ));
    }

    public function testRegularAccessCanReadClassifiedDoc()
    {
        // The classified document is accessible overall
        // i.e. it can show up in search results
        $this->assertTrue($this->regularAccess->can(
            "read", $this->classifiedDoc
        ));
    }

    public function testRegularAccessCannotReadClassifiedDocContent()
    {
        // The content of the classified document is not accessible
        $this->assertFalse($this->regularAccess->can(
            "read", $this->classifiedDoc, "content"
        ));
    }

    public function testRegularAccessCannotCreateDocument()
    {
        // We have no create permissions whatsoever
        $this->assertFalse($this->regularAccess->can(
            "create", "Document"
        ));
        $this->assertFalse($this->regularAccess->can(
            "create", new Document("foo", "bar")
        ));
    }

    /*******
     * Super access level
     *******/

    public function testSuperCanReadSomeDocs()
    {
        $this->assertTrue($this->superAccess->can(
            "read", "Fixtures\Document"
        ));
        $this->assertTrue($this->superAccess->can(
            "read", "Fixtures\Document", "content"
        ));
    }

    public function testSuperCanReadSpecificDocs()
    {
        $this->assertTrue($this->superAccess->can(
            "read", $this->openDoc
        ));
        $this->assertTrue($this->superAccess->can(
            "read", $this->openDoc, "content"
        ));
        $this->assertTrue($this->superAccess->can(
            "read", $this->classifiedDoc
        ));
        $this->assertTrue($this->superAccess->can(
            "read", $this->classifiedDoc, "content"
        ));
    }

    public function testSuperCanCreateDocument()
    {
        $this->assertTrue($this->superAccess->can(
            "create", "Fixtures\Document"
        ));
        $this->assertTrue($this->superAccess->can(
            "create", new Document("foo", "bar")
        ));
    }
}
