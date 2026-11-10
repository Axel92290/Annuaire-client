<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Client;
use App\Repositories\ClientRepository;

/**
 * Fournit les opérations de lecture des clients au contrôleur.
 *
 * Le contrôleur dépend de ce service et ne connaît pas le SQL.
 * Le repository reste responsable des lectures et de leur conversion.
 */
final class ClientService
{
    /**
     * Reçoit le repository par injection.
     *
     * Aucun accès à Db2 n'est déclenché pendant la construction.
     */
    public function __construct(
        private readonly ClientRepository $clients
    ) {}

    /**
     * Retourne la liste réelle des clients.
     *
     * Aucune donnée fictive n'est substituée en cas de panne :
     * l'erreur remonte jusqu'au contrôleur.
     *
     * @return list<Client>
     */
    public function findAll(): array
    {
        return $this->clients->findAll();
    }

    /**
     * Retourne un client réel à partir de son identifiant.
     *
     * Le repository contrôle l'identifiant et réalise la lecture.
     * null indique une valeur invalide ou un client absent.
     *
     * Le service ne choisit pas de statut HTTP :
     * cette responsabilité appartient au contrôleur.
     */
    public function findById(string $id): ?Client
    {
        return $this->clients->findById($id);
    }
}
