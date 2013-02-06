<?php

namespace Fixtures;

class Document
{
    private $title;
    public function getTitle() {
        return $this->title;
    }

    private $content;
    public function getContent() {
        return $this->content;
    }

    private $classified;
    public function isClassified() {
        return $this->classified;
    }

    public function __construct($title, $content, $classified = false) {
        $this->title = $title;
        $this->content = $content;
        $this->classified = $classified;
    }
}
