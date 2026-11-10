<?php

declare(strict_types=1);

use App\Infrastructure\Db2Connection;
use App\Infrastructure\IbmiProgramGateway;

/*
 * Contrôle manuel de la passerelle depuis le terminal.
 * Sortie 0 : tous les contrôles ont réussi.
 * Sortie 1 : configuration, appel ou résultat incorrect.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

try {
    $config = require dirname(__DIR__) . '/config/bootstrap.php';

    if (!($config['database']['enabled'] ?? false)) {
        throw new RuntimeException(
            'Intégration désactivée.'
        );
    }

    /*
     * Utiliser la même classe que l'application, sans construire
     * les services web ni charger le compte d'authentification.
     */
    $gateway = new IbmiProgramGateway(
        new Db2Connection($config['database']),
        $config['database']['schema']
    );

    // Premier cas : succès du programme RPG.
    $success = $gateway->ping('PING');

    if (
        $success['status'] !== 'success'
        || $success['code'] !== '00000'
        || $success['reply'] !== 'PONG'
    ) {
        throw new RuntimeException(
            'Résultat de succès inattendu.'
        );
    }

    echo "Appel PING : succès, PONG, code 00000.\n";

    // Deuxième cas : refus applicatif retourné normalement par RPG.
    $refusal = $gateway->ping('UNKNOWN');

    if (
        $refusal['status'] !== 'business_error'
        || $refusal['code'] !== 'B0001'
        || $refusal['reply'] !== 'Unsupported request'
    ) {
        throw new RuntimeException(
            'Résultat de refus inattendu.'
        );
    }

    echo "Appel UNKNOWN : refus applicatif B0001.\n";

    /*
     * Troisième cas : valeur incompatible avec le contrat.
     * Cette demande doit être refusée avant tout appel IBM i.
     */
    $invalidInputRejected = false;

    try {
        $gateway->ping('INVALID/REQUEST');
    } catch (InvalidArgumentException $exception) {
        $invalidInputRejected = true;
    }

    if (!$invalidInputRejected) {
        throw new RuntimeException(
            'Entrée invalide non refusée.'
        );
    }

    echo "Entrée invalide : refusée par la passerelle.\n";
    echo "Contrôle de la passerelle réussi.\n";

    exit(0);
} catch (Throwable $exception) {
    /*
     * Aucun message brut du pilote ni trace avec arguments.
     * Les détails techniques autorisés sont journalisés
     * par les classes d'infrastructure.
     */
    error_log(
        'Contrôle passerelle échoué ; type='
            . get_class($exception)
            . '; code='
            . (string) $exception->getCode()
    );

    fwrite(
        STDERR,
        "Le contrôle de la passerelle a échoué.\n"
    );

    exit(1);
}
