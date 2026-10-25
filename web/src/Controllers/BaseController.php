<?php

declare(strict_types=1);

namespace App\Controllers;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Environment;

abstract class BaseController
{
    public function __construct(
        protected readonly Environment $twig,
        private readonly Session $session,
        private readonly bool $modeDemo,
    ) {
    }

    protected function render(
        string $template,
        array $data = [],
        int $status = 200
    ): Response {
        $messagesFlash = [];

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

    protected function redirect(
        string $url,
        int $status = 302
    ): RedirectResponse {
        return new RedirectResponse($url, $status);
    }
}