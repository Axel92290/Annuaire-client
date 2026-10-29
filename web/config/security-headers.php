<?php

declare(strict_types=1);

// Politique commune ajoutée par index.php à toutes les réponses HTTP.
// Les assets sont locaux : la CSP n’autorise ni CDN ni JavaScript intégré au HTML.
return [
    'Content-Security-Policy' => implode('; ', [
        "default-src 'self'",
        "base-uri 'none'",
        "object-src 'none'",
        "frame-ancestors 'none'",
        "script-src 'self'",
        "style-src 'self'",
        "font-src 'self'",
        "img-src 'self' data:",
        "connect-src 'self'",
        "form-action 'self'",
    ]),
    // Double protection contre l’inclusion en iframe, y compris pour les anciens clients.
    'X-Frame-Options' => 'DENY',
    // Respecter les types MIME annoncés et limiter les informations de provenance.
    'X-Content-Type-Options' => 'nosniff',
    'Referrer-Policy' => 'strict-origin-when-cross-origin',
];