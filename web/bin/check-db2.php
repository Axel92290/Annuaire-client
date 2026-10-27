<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

use App\Infrastructure\Db2Connection;

try {
    $config = require dirname(__DIR__)
        . '/config/bootstrap.php';

    if (!($config['database']['enabled'] ?? false)) {
        fwrite(
            STDERR,
            "Intégration Db2 désactivée dans la configuration.\n"
        );

        exit(1);
    }

    if (!extension_loaded('pdo_odbc')) {
        fwrite(
            STDERR,
            "L’extension PDO_ODBC est requise pour ce contrôle.\n"
        );

        exit(1);
    }

    $db2 = new Db2Connection($config['database']);

    $pdo = $db2->getConnection();

    $schema = $config['database']['schema'];


    $statement = $pdo->query(
        'SELECT COUNT(*) FROM '
        . $schema
        . '.CLIENTS'
    );

    $count = (int) $statement->fetchColumn();

    echo "Connexion Db2 réussie.\n";
    echo "Lecture de {$schema}.CLIENTS réussie.\n";
    echo "Nombre de clients : {$count}\n";

    exit(0);
} catch (Throwable $exception) {
    error_log(
        'Contrôle Db2 échoué ; type='
        . get_class($exception)
        . '; code='
        . (string) $exception->getCode()
    );

    fwrite(
        STDERR,
        "Le contrôle Db2 a échoué. Consultez le diagnostic privé.\n"
    );

    exit(1);
}