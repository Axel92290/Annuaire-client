<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class Authentication
{
    private readonly string $username;
    private readonly string $passwordHash;
    private readonly string $fingerprint;

    public function __construct(
        private readonly SessionInterface $session,
        array $account
    ) {
        $username = $account['username'] ?? null;
        $passwordHash = $account['password_hash'] ?? null;

        if (
            !is_string($username)
            || trim($username) === ''
            || !is_string($passwordHash)
            || password_get_info($passwordHash)['algo'] === null
        ) {
            throw new RuntimeException(
                'Configuration d’authentification invalide.'
            );
        }

        $this->username = $username;
        $this->passwordHash = $passwordHash;
        $this->fingerprint = hash('sha256', $passwordHash);
    }

    public function username(): ?string
    {
        $identity = $this->session->get('security.identity');

        if (
            !is_array($identity)
            || !is_string($identity['username'] ?? null)
            || !is_string($identity['fingerprint'] ?? null)
        ) {
            return null;
        }

        if (
            !hash_equals($this->username, $identity['username'])
            || !hash_equals(
                $this->fingerprint,
                $identity['fingerprint']
            )
        ) {
            return null;
        }

        return $identity['username'];
    }

    public function isAuthenticated(): bool
    {
        return $this->username() !== null;
    }

    public function login(
        string $username,
        string $password
    ): bool {
        // Vérifier le mot de passe même si l'identifiant est incorrect.
        $passwordIsValid = password_verify(
            $password,
            $this->passwordHash
        );

        $usernameIsValid = hash_equals(
            $this->username,
            $username
        );

        if (!$usernameIsValid || !$passwordIsValid) {
            return false;
        }

        // Supprimer les données de la session non authentifiée.
        $this->session->clear();

        // Renouveler l'identifiant et supprimer l'ancien.
        if (!$this->session->migrate(true)) {
            throw new RuntimeException(
                'Impossible de renouveler la session.'
            );
        }

        $this->session->set('security.identity', [
            'username' => $this->username,
            'fingerprint' => $this->fingerprint,
        ]);

        return true;
    }

    public function logout(): void
    {
        $this->session->invalidate();
    }
}