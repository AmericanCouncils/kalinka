<?php

namespace Fixtures;

class Document
{
    private $owner;
    public function getOwner()
    {
        return $this->owner;
    }

    private $content;
    public function getContent()
    {
        return $this->content;
    }

    private $classified;
    public function isClassified()
    {
        return $this->classified;
    }

    public function __construct($owner, $content, $classified = false) {
        $this->owner = $owner;
        $this->content = $content;
        $this->classified = $classified;
    }
}
