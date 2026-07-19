<?php

// Shared .env loader used by tests (bootstrap) and the demo.
// Not part of the shipped library — a convenience for local dev.

if (!function_exists('acetone_load_env')) {
    /**
     * Minimal .env loader. Does NOT override variables already set in the real
     * environment (so CI/shell values win over the file).
     *
     * @param string $file
     *
     * @return void
     */
    function acetone_load_env(string $file): void
    {
        if (!is_file($file)) {
            return;
        }
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            [$name, $value] = array_pad(explode('=', $line, 2), 2, '');
            $name = trim($name);
            $value = trim($value, " \t\"'");
            if ($name !== '' && getenv($name) === false) {
                putenv("$name=$value");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}
