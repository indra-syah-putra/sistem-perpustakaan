<?php
// Simple .env loader
function loadEnv($path = null) {
    if ($path === null) {
        $path = __DIR__ . '/.env';
    }
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }
        $key = trim($parts[0]);
        $value = trim($parts[1]);
        // Remove surrounding quotes
        if ($value !== '' && (($value[0] === '"' && substr($value, -1) === '"') ||
            ($value[0] === "'" && substr($value, -1) === "'"))) {
            $value = substr($value, 1, -1);
        }
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

loadEnv();

// Helper to get env value with default
function env($key, $default = null) {
    $val = $_ENV[$key] ?? getenv($key);
    if ($val === false) {
        return $default;
    }
    return $val;
}
