<?php

declare(strict_types=1);

namespace App\Controllers;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Environment;

/**
 * Partage le rendu Twig, les messages flash et les redirections.
 *
 * Les actions retournent une Response ; seul public/index.php l'envoie.
 * Cette classe ne contient ni accès aux données ni règle métier.
 */
abstract class BaseController
{
    /**
     * Reçoit le moteur de vues, la session et le témoin du mode démonstration.
     */
    public function __construct(
        protected readonly Environment $twig,
        private readonly Session $session,
        private readonly bool $modeDemo,
    ) {
    }

    /**
     * Consomme les messages flash et produit une réponse HTML.
     *
     * Les variables communes sont ajoutées après les données de la page,
     * pour conserver une définition unique de modeDemo et messagesFlash.
     * @param array<string, mixed> $data Variables propres à la vue.
     * @param int $status Statut HTTP conservé même pour une page d’erreur.
     */
    protected function render(
        string $template,
        array $data = [],
        int $status = 200
    ): Response {
        $messagesFlash = [];

        // all() retire les flash de la session : ils ne sont affichés qu’une fois.
        foreach ($this->session->getFlashBag()->all() as $type => $messages) {
            foreach ($messages as $message) {
                $messagesFlash[] = [
                    'type' => $type,
                    'message' => $message,
                ];
            }
        }

        return new Response(
            $this->twig->render($template, array_merge(
                $data,
                [
                    'modeDemo' => $this->modeDemo,
                    'messagesFlash' => $messagesFlash,
                ]
            )),
            $status
        );
    }

    /**
     * Construit une redirection ; l’appelant doit retourner cette réponse.
     *
     * Le statut 303 après un POST demande au navigateur de poursuivre en GET.
     */
    protected function redirect(
        string $url,
        int $status = 302
    ): RedirectResponse {
        return new RedirectResponse($url, $status);
    }
}