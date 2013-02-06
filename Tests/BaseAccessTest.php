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
     * Regular access level
     *******/

    public function testRegularAccessCanReadDocs()
    {
        // At least some document properties can be read
        $this->assertTrue($this->regularAccess->can(
            "read", "Document"
        ));
    }

    public function testRegularAccessCanReadDocsContent()
    {
        // At least some documents' contents can be read
        $this->assertTrue($this->regularAccess->can(
            "read", "Document", "content"
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
}
