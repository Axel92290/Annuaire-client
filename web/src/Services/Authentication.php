<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Authentifie le compte web défini dans config/auth.local.php.
 *
 * Ce compte est distinct du profil IBM i utilisé par ODBC. Seuls le nom
 * et une empreinte du hash sont stockés en session, jamais le mot de passe.
 * Modifier le hash configuré rend les anciennes identités non valides.
 */
final class Authentication
{
    private readonly string $username;
    private readonly string $passwordHash;
    private readonly string $fingerprint;

    /**
     * Valide le compte local et calcule l’empreinte servant à vérifier les sessions.
     *
     * @param array{username?: mixed, password_hash?: mixed} $account Compte privé.
     * @throws RuntimeException Si le nom manque ou si le hash n’est pas reconnu.
     */
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

    /**
     * Retourne le nom seulement si l’identité en session correspond au compte actuel.
     *
     * Une identité absente, mal formée ou devenue obsolète équivaut à une
     * session non authentifiée. hash_equals compare les valeurs attendues.
     */
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

    /**
     * Exprime le contrôle d’accès à partir de l’identité validée, pas de sa seule présence.
     */
    public function isAuthenticated(): bool
    {
        return $this->username() !== null;
    }

    /**
     * Vérifie les deux identifiants puis renouvelle la session en cas de succès.
     *
     * Le mot de passe est vérifié même pour un nom erroné. Un échec retourne
     * false ; un succès efface les anciennes données et détruit l’ancien ID
     * pour limiter la fixation de session. L’identité est alors enregistrée.
     */
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

    /**
     * Invalide la session courante, y compris son identité et ses jetons CSRF.
     */
    public function logout(): void
    {
        $this->session->invalidate();
    }
}