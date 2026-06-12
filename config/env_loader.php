<?php
/**
 * Environment Variables Loader
 * Loads variables from .env file in the project root
 */
function loadEnv($envPath = null) {
    // Use default path if not provided
    if ($envPath === null) {
        $envPath = __DIR__ . '/../.env';
    }
    
    // Check if .env file exists
    if (!file_exists($envPath)) {
        return false;
    }
    
    // Read all lines from .env file
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    // Process each line
    foreach ($lines as $line) {
        // Skip comment lines
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse KEY=VALUE format
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                $value = substr($value, 1, -1);
            }
            
            // Set environment variable if not already set
            if (!getenv($key)) {
                // Special handling for DB_PASS: if empty, don't set it
                if ($key === 'DB_PASS' && trim($value) === '') {
                    continue;
                }
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }
    
    return true;
}

// Automatically load .env file when this file is included
loadEnv();


















