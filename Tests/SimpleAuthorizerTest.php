<?php

use AC\Kalinka\Authorizer\SimpleAuthorizer;

use Fixtures\Document;
use Fixtures\User;

class OurSimpleAuthorizer extends SimpleAuthorizer
{
    public function __construct($subject)
    {
        parent::__construct($subject);

        $this->registerGuards([
            "document" => "Fixtures\DocumentGuard"
        ]);
        $this->registerActions([
            "document" => ["read", "write"]
        ]);
        $this->registerPolicies([
            "document" => [
                "read" => [ "unclassified" ],
                "write" => "owned"
            ]
        ]);
    }
}

class SimpleAuthorizerTest extends KalinkaTestCase
{
    public function testSimpleAuthorizer() {
        $user = new User("dave");
        $auth = new OurSimpleAuthorizer($user);
        $doc1 = new Document("dave", "My stuff");
        $doc2 = new Document("dave", "My secret stuff", true);
        $doc3 = new Document("evan", "His stuff");
        $doc4 = new Document("evan", "His secret stuff", true);

        $this->assertCallsEqual([$auth, "can"], ["read", "document", self::X1], [
            [true, $doc1],
            [false, $doc2],
            [true, $doc3],
            [false, $doc4]
        ]);

        $this->assertCallsEqual([$auth, "can"], ["write", "document", self::X1], [
            [true, $doc1],
            [true, $doc2],
            [false, $doc3],
            [false, $doc4]
        ]);
    }
}
