<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment;
use App\Services\ClientInputValidator;
use App\Services\ClientService;
use RuntimeException;

/**
 * Pilote les écrans clients de démonstration à partir des fixtures PHP.
 *
 * Aucune méthode de ce contrôleur ne lit Db2 ou n'enregistre de client.
 * Les scénarios permettent de vérifier les états des écrans sans IBM i.
 */
final class ClientController extends BaseController
{
    // Limites de caractères pour chaque champ du formulaire.
    private const FIELDS = ClientInputValidator::FIELDS;

    // Le paramètre $scenario permet de simuler différents comportements
    // de l'application pour les tests fonctionnels.
    /**
     * Injecte les dépendances communes et le scénario choisi côté serveur.
     *
     * Le validateur traite les saisies ; le gestionnaire CSRF lie le formulaire
     * à la session et à l’action de création ou au client à modifier.
     */
    public function __construct(
        Environment $twig,
        Session $session,
        private readonly bool $demoEnabled,
        private readonly CsrfTokenManagerInterface $csrf,
        private readonly ClientInputValidator $inputValidator,
        private readonly ClientService $clientService,
        private readonly string $scenario = 'normal',

    ) {
        parent::__construct(
            $twig,
            $session,
            $demoEnabled
        );
    }

    // Cette méthode permet de simuler des interruptions de service
    // pour les tests fonctionnels. Elle retourne une réponse d'erreur  
    /**
     * Interrompt un parcours si la démonstration est désactivée ou en panne simulée.
     *
     * Retourne null lorsque l’action peut continuer, sinon une réponse 503 ou 500.
     * Le mode réel n’est pas encore raccordé aux écrans.
     */
    private function interruption(): ?Response
    {
        if (
            !$this->demoEnabled
            || $this->scenario === 'unavailable'
        ) {
            return $this->error(503);
        }

        if ($this->scenario === 'error') {
            return $this->error(500);
        }

        return null;
    }

    /**
     * Charge le jeu fictif à chaque appel ; aucune mutation n’est conservée.
     *
     * Le scénario empty retourne une liste vide ; long-city remplace un objet
     * immuable pour éprouver les mises en page avec un texte long.
     * @return list<Client>
     */
    private function clients(): array
    {
        if ($this->scenario === 'empty') {
            return [];
        }

        $clients = require dirname(__DIR__, 2)
            . '/fixtures/clients.php';

        if ($this->scenario === 'long-city') {
            $client = $clients[0];

            $clients[0] = new Client(
                $client->id,
                $client->nom,
                $client->prenom,
                $client->email,
                $client->telephone,
                $client->adresse,
                $client->codePostal,
                'Saint-Rémy-en-Bouzemont-Saint-Genest-et-Isson'
                    . ' — quartier des jardins',
                $client->creeLe,
                $client->modifieLe
            );
        }

        return $clients;
    }

    /**
     * Cherche une fixture en comparant les identifiants comme chaînes.
     *
     * Évite une conversion numérique des identifiants fournis dans l’URL.
     * @throws ResourceNotFoundException Si aucun client ne correspond ; index.php produit la 404.
     */
    private function find(string $id): Client
    {
        foreach ($this->clients() as $client) {
            if ((string) $client->id === $id) {
                return $client;
            }
        }

        throw new ResourceNotFoundException(
            'Client absent.'
        );
    }

    /**
     * Affiche la liste fictive ou réelle selon le mode configuré.
     *
     * Le template reste identique dans les deux parcours.
     * Une panne de lecture réelle produit une réponse 503.
     */
    public function index(Request $request): Response
    {
    /*
     * Les scénarios visuels concernent uniquement la démonstration.
     *
     * L'ancienne méthode interruption() bloque les parcours réels
     * non raccordés. On ne l'appelle donc ici qu'en mode démonstration.
     */
        if ($this->demoEnabled) {
            $response = $this->interruption();

            if ($response !== null) {
                return $response;
            }
        }

     /*
     * Accepter uniquement une recherche textuelle.
     * Une URL comme ?q[]=Martin ne doit pas provoquer une erreur PHP.
     */
        $query = $request->query->all()['q'] ?? '';
        $query = is_string($query)
            ? trim($query)
            : '';

        try {
            /*
         * Le choix de la source est explicite.
         *
         * En mode réel, une erreur Db2 ne provoque jamais
         * un retour silencieux aux fixtures.
         */
            $clients = $this->demoEnabled
                ? $this->clients()
                : $this->clientService->findAll();
        } catch (RuntimeException $exception) {
            /*
         * Ne pas afficher ni journaliser ici le message technique brut.
         * La page 503 existante présente une erreur générique.
         */
            error_log(
                'Affichage de la liste des clients indisponible.'
            );

            return $this->error(503);
        }

        /*
     * Étape transitoire pour l'issue 5 :
     * conserver le comportement de recherche déjà disponible.
     *
     * Le filtrage est encore effectué en PHP après la lecture.
     * L'issue 7 le remplacera par une recherche SQL paramétrée.
     */
        $clients = array_values(array_filter(
            $clients,
            static fn(Client $client): bool =>
            $query === ''
                || mb_stripos($client->nom, $query) !== false
        ));

        return $this->render('clients/index.html.twig', [
            'clients' => $clients,
            'recherche' => $query,

            /*
         * Ne jamais afficher une confirmation fictive
         * dans le parcours alimenté par Db2.
         */
            'apercuConfirmation' =>
            $this->demoEnabled
                && $this->scenario === 'confirmation',
        ]);
    }

    /**
     * Affiche le formulaire vide, sauf interruption de la démonstration.
     */
    public function new(): Response
    {
        return $this->interruption()
            ?? $this->form(null);
    }

    /**
     * Affiche la fiche trouvée ou laisse remonter son absence en 404.
     */
    public function show(string $id): Response
    {
        if ($response = $this->interruption()) {
            return $response;
        }

        return $this->render('clients/show.html.twig', [
            'client' => $this->find($id),
        ]);
    }

    /**
     * Préremplit le formulaire avec la fixture existante, sans la modifier.
     */
    public function edit(string $id): Response
    {
        if ($response = $this->interruption()) {
            return $response;
        }

        return $this->form($this->find($id));
    }

    /**
     * Prépare les variables communes aux formulaires d’ajout et de modification.
     *
     * Sans valeurs explicites, préremplit depuis le client ou avec des chaînes vides.
     * Après un POST, conserve les valeurs saisies et les erreurs par champ.
     * Le jeton distingue client_create de client_update_{id}.
     * @param array<string, string>|null $values Saisies à réafficher.
     * @param array<string, list<string>> $errors Erreurs par champ, ou _global.
     * @param bool $validated Signale uniquement un aperçu valide, sans sauvegarde.
     */
    private function form(
        ?Client $client,
        ?array $values = null,
        array $errors = [],
        int $status = 200,
        bool $validated = false
    ): Response {
        if ($values === null) {
            $values = [];

            foreach (self::FIELDS as $field => $limit) {
                $values[$field] = $client?->$field ?? '';
            }

            if ($this->scenario === 'form-errors') {
                $values = array_replace($values, [
                    'nom' => '',
                    'prenom' => 'Camille',
                    'email' => 'camille.martin@',
                    'telephone' => '01 23 45 67 89',
                    'adresse' => '12 rue des Lilas',
                    'codePostal' => '69003',
                    'ville' => 'Lyon',
                ]);

                $errors = [
                    'nom' => [
                        'Renseignez le nom du client.',
                    ],
                    'email' => [
                        'L’adresse e-mail n’est pas valide.',
                    ],
                ];
            }
        }

        return $this->render(
            $client
                ? 'clients/edit.html.twig'
                : 'clients/new.html.twig',
            [
                'client' => $client,
                'valeurs' => $values,
                'erreurs' => $errors,
                'actionFormulaire' => $client
                    ? '/clients/' . $client->id . '/edit'
                    : '/clients',
                'actionApercu' => $client
                    ? '/clients/' . $client->id . '/preview'
                    : '/clients/preview',
                'csrfToken' => $this->csrf
                    ->getToken(
                        $client === null
                            ? 'client_create'
                            : 'client_update_' . $client->id
                    )
                    ->getValue(),
                'ecritureActive' => false,
                'apercuValide' => $validated,
            ],
            $status
        );
    }

    /**
     * Contrôle le CSRF puis valide les saisies, sans effectuer d’écriture.
     *
     * Un jeton refusé donne 403 ; des données invalides donnent 422 ;
     * un aperçu valide donne 200. Le service retourne aussi data (normalisé),
     * mais cette action ne l’utilise pas pour persister un client.
     */
    public function preview(
        Request $request,
        ?string $id = null
    ): Response {
        if ($response = $this->interruption()) {
            return $response;
        }

        $client = $id === null
            ? null
            : $this->find($id);

        $input = $request->request->all();
        $token = $input['_token'] ?? '';

        $tokenId = $client === null
            ? 'client_create'
            : 'client_update_' . $client->id;

        if (
            !is_string($token)
            || !$this->csrf->isTokenValid(
                new CsrfToken($tokenId, $token)
            )
        ) {
            return $this->form(
                $client,
                $this->inputValidator->values($input),
                [
                    '_global' => [
                        'La page a expiré. Vérifiez vos informations'
                            . ' puis réessayez.',
                    ],
                ],
                403
            );
        }

        $result = $this->inputValidator->validate($input);

        return $this->form(
            $client,
            $result['values'],
            $result['errors'],
            $result['errors'] === [] ? 200 : 422,
            $result['errors'] === []
        );
    }

    /**
     * Rend la vue correspondant au statut interne demandé (404, 500 ou 503).
     */
    public function error(int $status): Response
    {
        return $this->render(
            'errors/' . $status . '.html.twig',
            status: $status
        );
    }
}
