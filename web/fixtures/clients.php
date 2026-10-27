<?php

declare(strict_types=1);

use App\Models\Client;

$created = new DateTimeImmutable(
    '2026-03-12 08:42:00',
    new DateTimeZone('UTC')
);

$updated = new DateTimeImmutable(
    '2026-10-02 14:05:00',
    new DateTimeZone('UTC')
);

return [
    new Client(
        128,
        'Martin',
        'Camille',
        'camille.martin@example.com',
        '01 23 45 67 89',
        '12 rue des Lilas',
        '69003',
        'Lyon',
        $created,
        $updated
    ),
    new Client(
        94,
        'Marchand',
        'Inès',
        'ines.marchand@example.com',
        '03 45 67 89 01',
        '8 allée des Tilleuls',
        '59000',
        'Lille',
        $created,
        $updated
    ),
    new Client(
        211,
        'Marie',
        'Hugo',
        'hugo.marie@example.com',
        '04 56 78 90 12',
        '24 avenue des Oliviers',
        '06000',
        'Nice',
        $created,
        $updated
    ),
    new Client(
        47,
        'Martinez',
        'Léo',
        'leo.martinez@example.com',
        '02 34 56 78 90',
        '6 rue des Érables',
        '44000',
        'Nantes',
        $created,
        $updated,
    ),
    new Client(
        305,
        "D'Angelo & Fils",
        null,
        null,
        null,
        null,
        '01230',
        null,
        $created,
        $updated
    ),
];