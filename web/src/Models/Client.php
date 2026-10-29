<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;

/**
 * Représente une fiche client immuable transmise aux vues Twig.
 *
 * L'identifiant technique est distinct de sa présentation sur six chiffres.
 * Téléphone et code postal restent textuels pour préserver les zéros initiaux.
 * Les champs facultatifs peuvent valoir null ; les dates portent leur fuseau.
 */
final readonly class Client
{
    /**
     * Initialise les propriétés publiques en lecture seule.
     *
     * Ce modèle transporte des données ; les limites métier sont vérifiées
     * par ClientInputValidator et les contraintes Db2, pas ici.
     */
    public function __construct(
        public int $id,
        public string $nom,
        public ?string $prenom,
        public ?string $email,
        public ?string $telephone,
        public ?string $adresse,
        public ?string $codePostal,
        public ?string $ville,
        public DateTimeImmutable $creeLe,
        public DateTimeImmutable $modifieLe,
    ) {
    }

    /**
     * Expose l’identifiant technique, sans appliquer le format d’affichage.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Expose le nom ; Twig peut aussi lire directement la propriété publique.
     */
    public function getNom(): string
    {
        return $this->nom;
    }
}