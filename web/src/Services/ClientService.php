<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Client;
use App\Repositories\ClientRepository;
use InvalidArgumentException;

/**
 * Fournit les opérations de lecture des clients au contrôleur.
 *
 * Le contrôleur dépend de ce service et ne connaît pas le SQL.
 * Le repository reste responsable des lectures et de leur conversion.
 */
final class ClientService
{
    public const MAX_SEARCH_LENGTH = 100;
    /**
     * Reçoit le repository par injection.
     *
     * Aucun accès à Db2 n'est déclenché pendant la construction.
     */
    public function __construct(
        private readonly ClientRepository $clients
    ) {}

    /**
     * Fournit la liste complète ou les résultats d'une recherche.
     *
     * La validation est appliquée ici aussi pour les appels au service
     * qui ne proviendraient pas du contrôleur HTTP.
     *
     * @return list<Client>
     */
    public function findAll(string $search = ''): array
    {
        $search = $this->normalizeSearch($search);

        if ($search === '') {
            return $this->clients->findAll();
        }

        return $this->clients->findByName($search);
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

    /**
     * Vérifie et normalise le paramètre de recherche.
     *
     * Une entrée HTTP n'est pas nécessairement une chaîne :
     * q[]=Mar produit par exemple un tableau.
     *
     * @throws InvalidArgumentException Si le type, l'encodage
     *                                  ou la longueur est invalide.
     */
    public function normalizeSearch(mixed $input): string
    {
        if (!is_string($input)) {
            throw new InvalidArgumentException(
                'La recherche doit être un texte.'
            );
        }

        if (preg_match('//u', $input) !== 1) {
            throw new InvalidArgumentException(
                'La recherche contient un texte invalide.'
            );
        }

        // Retirer les espaces aux extrémités, sans modifier les espaces internes.
        $search = trim($input);

        if (
            mb_strlen($search, 'UTF-8')
            > self::MAX_SEARCH_LENGTH
        ) {
            throw new InvalidArgumentException(
                'La recherche est limitée à 100 caractères.'
            );
        }

        return $search;
    }
}
