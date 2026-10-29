<?php

declare(strict_types=1);

namespace App\Infrastructure;

use LogicException;
use PDO;
use PDOException;
use RuntimeException;

/**
 * Fournit une connexion PDO_ODBC à la demande, réutilisée dans cette instance.
 *
 * La configuration est validée par config/bootstrap.php avant injection.
 * Construire ce service ne nécessite ni accès réseau ni pilote ODBC :
 * les pages de démonstration peuvent donc fonctionner sans IBM i.
 */
final class Db2Connection
{
    private ?PDO $connection = null;

    /**
     * Mémorise les paramètres ; aucune connexion n’est ouverte ici.
     *
     * @param array{enabled: bool, dsn?: string, username?: string, password?: string, schema?: string} $config Configuration validée.
     */
    public function __construct(
        private readonly array $config
    ) {
        // Aucune connexion réseau dans le constructeur.
    }

    /**
     * Ouvre la connexion au premier appel puis retourne la même instance PDO.
     *
     * @throws LogicException Si l’intégration est désactivée.
     * @throws RuntimeException Si le pilote manque ou si la connexion échoue.
     * Les détails du pilote sont remplacés par une erreur publique générique.
     */
    public function getConnection(): PDO
    {
        if (!($this->config['enabled'] ?? false)) {
            throw new LogicException(
                'La connexion Db2 est désactivée.'
            );
        }

        // Réutilisation limitée à cet objet, sans activer de connexion PDO persistante.
        if ($this->connection !== null) {
            return $this->connection;
        }

        if (
            !in_array(
                'odbc',
                PDO::getAvailableDrivers(),
                true
            )
        ) {
            throw new RuntimeException(
                'L’extension PDO_ODBC est indisponible.'
            );
        }

        try {
            $this->connection = new PDO(
                $this->config['dsn'],
                $this->config['username'],
                $this->config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $exception) {
            // Ne conserver qu’un code borné : le message IBM peut révéler le DSN.
            $sqlState = (string) (
                $exception->errorInfo[0]
                ?? $exception->getCode()
            );

            if (!preg_match('/^[A-Z0-9]{5}$/D', $sqlState)) {
                $sqlState = 'UNKNOWN';
            }

            // Aucun identifiant, mot de passe ou DSN journalisé.
            error_log(
                'Échec de connexion Db2 — SQLSTATE : '
                . $sqlState
            );

            throw new RuntimeException(
                'Connexion Db2 indisponible.'
            );
        }

        return $this->connection;
    }
}