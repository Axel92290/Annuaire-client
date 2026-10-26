<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;

final readonly class Client
{
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

    public function getId(): int
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }
}