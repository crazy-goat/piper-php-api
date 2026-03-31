<?php

declare(strict_types=1);

return [
    'default' => [
        'handlers' => [
            [
                'class' => Monolog\Handler\StreamHandler::class,
                'constructor' => [
                    'stream' => 'php://stdout',
                    'level' => Monolog\Level::Debug,
                ],
            ],
        ],
    ],
];
