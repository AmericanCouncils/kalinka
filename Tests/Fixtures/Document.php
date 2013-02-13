<?php

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

    public function __construct($owner, $content) {
        $this->owner = $owner;
        $this->content = $content;
    }
}
