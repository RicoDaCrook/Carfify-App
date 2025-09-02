<?php

class Autoloader
{
    public static function register()
    {
        spl_autoload_register([__CLASS__, 'autoload']);
    }
    
    public static function autoload($className)
    {
        // Namespace-Backslashes zu Directory-Slashes konvertieren
        $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
        
        // Mögliche Pfade definieren
        $paths = [
            __DIR__ . '/../classes/' . $className . '.php',
            __DIR__ . '/../models/' . $className . '.php',
            __DIR__ . '/../controllers/' . $className . '.php',
            __DIR__ . '/../services/' . $className . '.php'
        ];
        
        foreach ($paths as $path) {
            if (file_exists($path)) {
                require_once $path;
                return;
            }
        }
    }
}