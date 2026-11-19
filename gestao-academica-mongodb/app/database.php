<?php
declare(strict_types=1);

use MongoDB\Client;
use MongoDB\Database;

function mongo_db(): Database
{
    static $database = null;

    if ($database instanceof Database) {
        return $database;
    }

    if (!extension_loaded('mongodb')) {
        throw new RuntimeException('A extensao PHP mongodb nao esta habilitada. Consulte o README.md para instalar.');
    }

    $uri = getenv('MONGODB_URI') ?: 'mongodb://127.0.0.1:27017';
    $databaseName = getenv('MONGODB_DATABASE') ?: 'gestao_academica';

    $client = new Client($uri, [], [
        'typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array'],
    ]);
    $database = $client->selectDatabase($databaseName);

    return $database;
}
