<?php

// PHPUnit bootstrap: autoload + load .env into environment variables.

if (is_file(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    require_once __DIR__ . '/../src/autoload.php';
}

require_once __DIR__ . '/../env.php';

acetone_load_env(__DIR__ . '/../.env');
