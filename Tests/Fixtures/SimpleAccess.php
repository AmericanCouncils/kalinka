<?php

namespace Fixtures;

use \Closure;
use Kalinka\BaseAccess;
use Kalinka\HardcodedAccess;

class SimpleAccess extends HardcodedAccess
{
    public function __construct($level)
    {
        $this->setupActions(["create", "read"]);

        $this->setupObjectTypes([
            "Fixtures\Document" => ["content"]
        ]);

        if ($level == "super") {
            $this->allowEverything();
        } else if ($level == "regular") {
            $this->allow("read", "Fixtures\Document");
            $this->allow("read", "Fixtures\Document", "content",
                Closure::bind(function(Document $d) {
                    return !$d->isClassified();
                }, $this)
            );
        } else if ($level == "guest") {
            // Don't allow anything
        } else {
            throw new \RuntimeError("Weird level $level");
        }
    }
}

