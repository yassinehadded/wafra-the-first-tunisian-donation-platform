<?php
/**
 * Autoloader for classes
 * Automatically loads classes from models, controllers, and services
 */

spl_autoload_register(function ($class) {
    $directories = [
        __DIR__ . '/../models/',
        __DIR__ . '/../controllers/',
        __DIR__ . '/../services/',
    ];
    
    foreach ($directories as $directory) {
        $file = $directory . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});


















