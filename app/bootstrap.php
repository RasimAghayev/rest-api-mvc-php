<?php
require_once 'config/config.php';
require_once 'helpers/index.php';
spl_autoload_register(function($className) {
    $parts = explode('\\', $className);
    if(count($parts) > 1) {
        $classfile = 'libraries'. DIRECTORY_SEPARATOR .'OtherClass'. DIRECTORY_SEPARATOR .array_shift($parts) . DIRECTORY_SEPARATOR . strtolower(implode(DIRECTORY_SEPARATOR, $parts)) . ".php";
    } else {
        $classfile = 'libraries' . DIRECTORY_SEPARATOR . strtolower($className) . '.php';
    }
    if(FALSE === stream_resolve_include_path($classfile)){
        $classfile = 'libraries' . DIRECTORY_SEPARATOR . 'OtherClass'. DIRECTORY_SEPARATOR .strtolower($className) . '.php';
    }
    require_once $classfile;
});
