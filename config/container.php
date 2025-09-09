<?php
use App\Helpers\Database;
use DI\Container;

return [
    'db' => function () {
        return Database::getInstance()->getPdo();
    },
    'settings' => [
        'displayErrorDetails' => true,
        'logErrors' => true,
        'logErrorDetails' => true,
        'jwt' => [
            'secret' => $_ENV['JWT_SECRET'],
            'algorithm' => 'HS256',
            'lifetime' => 3600 * 24 // 24 hours
        ]
    ]
];
