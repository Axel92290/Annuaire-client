<?php

declare(strict_types=1);

// Copier vers auth.local.php et renseigner un compte local.
// Ne jamais stocker de mot de passe en clair.

// Modèle de structure pour le compte web, sans lien avec le profil ODBC.
// Générer son propre hash avec password_hash() dans le fichier local ignoré par Git.
return [
    'username' => 'axel',
    'password_hash' => '$2y$10$ctHfqNGc5Y988xc6dmPCDuL/haxpCGWpdpuruT44n1RU9lGezGeDi',
];