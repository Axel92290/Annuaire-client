<?php

declare(strict_types=1);

namespace App\Infrastructure;

use LogicException;
use PDO;
use PDOException;
use RuntimeException;

final class Db2Connection
{
    private ?PDO $connection = null;

    public function __construct(
        private readonly array $config
    ) {
        // Aucune connexion réseau dans le constructeur.
    }

    public function getConnection(): PDO
    {
        if (!($this->config['enabled'] ?? false)) {
            throw new LogicException(
                'La connexion Db2 est désactivée.'
            );
        }

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