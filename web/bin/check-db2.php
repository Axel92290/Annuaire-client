<?php

declare(strict_types=1);

// Commande appelée par composer check:db2 depuis web/, jamais un endpoint HTTP.
// Code de sortie 0 : connexion et SELECT réussis ; 1 : prérequis absent ou échec.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

use App\Infrastructure\Db2Connection;

try {
    // Utiliser la même configuration validée que Wamp, sans charger les services web.
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

    // Le schéma est validé par bootstrap avant son interpolation dans le SQL.
    // COUNT(*) vérifie la lecture sans modifier les clients.
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
// Journaliser seulement le type et le code, sans recopier un message contenant le DSN.
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