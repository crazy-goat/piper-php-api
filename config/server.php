<?php

declare(strict_types=1);

return [
    'listen' => 'http://0.0.0.0:8000',
    'transport' => 'tcp',
    'context' => [],
    'name' => 'piper-tts',
    'count' => 4,
    'user' => '',
    'group' => '',
    'reusePort' => false,
    'event_loop' => '',
    'stop_timeout' => 2,
    'pid_file' => '/tmp/piper.pid',
    'status_file' => '/tmp/piper.status',
    'stdout_file' => 'php://stdout',
    'log_file' => 'php://stderr',
    'max_package_size' => 10485760,
];
