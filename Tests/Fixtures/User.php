<?php

namespace Fixtures;

class User
{
    public function __construct($name, $roles = [], $langs = []) {
        $this->name = $name;
        $this->roles = $roles;
        $this->langs = $langs;
    }
}

