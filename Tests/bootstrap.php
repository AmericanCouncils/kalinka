<?php

require_once __DIR__ . "/../vendor/autoload.php";

set_include_path(
    get_include_path() . PATH_SEPARATOR .
    dirname(__DIR__) . PATH_SEPARATOR .
    dirname(__DIR__) . "/Tests"
);
spl_autoload_register(function($c) {
    $path = strtr($c, '\\_', '//').'.php'; 
    require_once("Tests/" . $path);
});
