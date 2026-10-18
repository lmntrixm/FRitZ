<?php

// Creates web/.env for local development from .env.example (which holds production defaults).
// Used by `composer setup`; never overwrites an existing .env.

$env = __DIR__.'/../.env';
if (file_exists($env)) {
    exit(0);
}

$text = file_get_contents(__DIR__.'/../.env.example');
foreach ([
    'APP_ENV' => 'local',
    'APP_DEBUG' => 'true',
    'APP_URL' => 'http://127.0.0.1:8000',
    'LOG_LEVEL' => 'debug',
    'DB_CONNECTION' => 'sqlite',
    'FRITZ_DEMO_DATA' => 'true',
] as $key => $value) {
    $text = preg_replace('/^'.$key.'=.*$/m', $key.'='.$value, $text);
}
file_put_contents($env, $text);
echo "Created .env for local development\n";
