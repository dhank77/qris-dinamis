<?php

/**
 * Autoloader mandiri — dipakai jika tidak menggunakan Composer.
 * Dengan Composer cukup: require 'vendor/autoload.php';
 */
spl_autoload_register(static function (string $class): void {
    $prefix = 'QrisDinamis\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file = __DIR__ . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});
