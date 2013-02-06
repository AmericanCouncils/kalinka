<?php

namespace Fixtures;

use Kalinka\BaseAccess;

class SimpleAccess extends BaseAccess
{
    public function __construct($level)
    {
        $this->setupObjectTypes([
            "Document" => ["content"]
        ]);

        if ($level == "super") {
            $this->allowEverything();
        } else if ($level == "regular") {
            $this->allow("read", "Fixtures\Document");
            $this->allow("read", "Fixtures\Document", "content", function($a, Document $d) {
                return !$d->isClassified();
            });
        } else if ($level == "guest") {
            // Don't allow anything
        } else {
            throw new \RuntimeError("Weird level $level");
        }
    }
}

