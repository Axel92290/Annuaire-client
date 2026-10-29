<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Authentication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment;

/**
 * Traduit les demandes HTTP de connexion et déconnexion en réponses.
 *
 * Authentication gère l'identité ; ce contrôleur vérifie les entrées
 * et le jeton CSRF, choisit le statut HTTP et prépare la vue.
 */
final class AuthController extends BaseController
{
    /**
     * Injecte le service d’identité et le gestionnaire CSRF de la requête.
     */
    public function __construct(
        Environment $twig,
        Session $session,
        bool $modeDemo,
        private readonly Authentication $authentication,
        private readonly CsrfTokenManagerInterface $csrf
    ) {
        parent::__construct($twig, $session, $modeDemo);
    }

    /**
     * Affiche le formulaire en GET et vérifie les identifiants en POST.
     *
     * Retourne 403 pour un CSRF refusé, 422 pour des champs absents ou mal
     * typés, 401 pour des identifiants incorrects et 303 après connexion.
     * Seul l’identifiant est réaffiché ; le mot de passe ne rejoint pas Twig.
     */
    public function login(Request $request): Response
    {
        if ($this->authentication->isAuthenticated()) {
            return $this->redirect('/clients');
        }

        $username = '';
        $errors = [];
        $status = 200;

        if ($request->isMethod('POST')) {
            $input = $request->request->all();

            $rawUsername = $input['username'] ?? '';
            $password = $input['password'] ?? '';
            $token = $input['_token'] ?? '';

            $username = is_string($rawUsername)
                ? trim($rawUsername)
                : '';

            // Contrôler le CSRF avant de tenter l’authentification.
            if (
                !is_string($token)
                || !$this->csrf->isTokenValid(
                    new CsrfToken('auth_login', $token)
                )
            ) {
                $errors[] =
                    'La page a expiré. Réessayez.';
                $status = 403;
            } elseif (
                $username === ''
                || !is_string($password)
                || $password === ''
            ) {
                $errors[] =
                    'Renseignez votre identifiant et votre mot de passe.';
                $status = 422;
            } elseif (
                !$this->authentication->login(
                    $username,
                    $password
                )
            ) {
                $errors[] =
                    'Identifiant ou mot de passe incorrect.';
                $status = 401;
            } else {
                return $this->redirect('/clients', 303);
            }
        }

        return $this->render('auth/login.html.twig', [
            'identifiant' => $username,
            'erreursConnexion' => $errors,
            'csrfToken' => $this->csrf
                ->getToken('auth_login')
                ->getValue(),
        ], $status);
    }

    /**
     * Vérifie la session et le jeton auth_logout avant de l’invalider.
     *
     * La route limite cette action au POST ; un refus retourne immédiatement 403.
     */
    public function logout(Request $request): Response
    {
        if (!$this->authentication->isAuthenticated()) {
            return new Response('Accès refusé.', 403);
        }

        $token = $request->request->all()['_token'] ?? '';

        if (
            !is_string($token)
            || !$this->csrf->isTokenValid(
                new CsrfToken('auth_logout', $token)
            )
        ) {
            return new Response(
                'La demande de déconnexion a expiré.',
                403
            );
        }

        $this->authentication->logout();

        return $this->redirect('/login', 303);
    }
}
