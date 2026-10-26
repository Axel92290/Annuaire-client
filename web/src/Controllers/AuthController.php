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

final class AuthController extends BaseController
{
    public function __construct(
        Environment $twig,
        Session $session,
        bool $modeDemo,
        private readonly Authentication $authentication,
        private readonly CsrfTokenManagerInterface $csrf
    ) {
        parent::__construct($twig, $session, $modeDemo);
    }

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
