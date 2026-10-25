<?php

declare(strict_types=1);

namespace App\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

final class ClientController extends BaseController
{
    public function index(Request $request): Response
    {
        $recherche = $request->query->get('q', '');

        return $this->render('clients/index.html.twig', [
            'clients' => [],
            'recherche' => is_string($recherche)
                ? trim($recherche)
                : '',
        ]);
    }

    public function new(): Response
    {
        return $this->render('clients/new.html.twig', [
            'client' => null,
            'valeurs' => [
                'nom' => '',
                'prenom' => '',
                'email' => '',
                'telephone' => '',
                'adresse' => '',
                'codePostal' => '',
                'ville' => '',
            ],
            'erreurs' => [],
            'actionFormulaire' => '/clients',
            'csrfToken' => null,
            'ecritureActive' => false,
        ]);
    }

    public function show(string $id): Response
    {
        throw new ResourceNotFoundException(
            'Aucune source de clients configurée.'
        );
    }

    public function edit(string $id): Response
    {
        throw new ResourceNotFoundException(
            'Aucune source de clients configurée.'
        );
    }

    public function error(int $status): Response
    {
        return $this->render(
            'errors/' . $status . '.html.twig',
            status: $status
        );
    }
}