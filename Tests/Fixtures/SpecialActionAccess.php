<?php

namespace Fixtures;

use Kalinka\DefinedAccess;

class SpecialActionAccess extends DefinedAccess
{
    public function __construct()
    {
        $this->setupActions(["nom"]);
        $this->setupObjectTypes(["IceCream", "Brownie", "SheetMetal"]);

        $this->allow("nom", "IceCream");
        $this->allow("nom", "Brownie");
        // Not allowed to nom on sheet metal
    }
}
