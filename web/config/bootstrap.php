<?php

declare(strict_types=1);

// Chargement commun HTTP/CLI : aucun service ni accès réseau n’est créé ici.
require dirname(__DIR__) . '/vendor/autoload.php';

// Récupération de la configuration locale.
// APP_ENV provient du processus serveur, jamais d’un paramètre fourni par le navigateur.
$environment = getenv('APP_ENV') ?: 'dev';

if (!in_array($environment, ['dev', 'prod'], true)) {
    throw new RuntimeException('Environnement non reconnu.');
}

$configPath = __DIR__ . '/' . $environment . '.ini';

if (!is_file($configPath)) {
    throw new RuntimeException('Configuration locale absente.');
}

// Chargement de la configuration locale.
// Les sections sont conservées ; INI_SCANNER_TYPED distingue notamment false de "false".
$config = parse_ini_file(
    $configPath,
    true,
    INI_SCANNER_TYPED
);

if ($config === false || !isset($config['app'])) {
    throw new RuntimeException('Configuration locale invalide.');
}

$app = $config['app'];

if (
    !isset($app['debug'], $app['mode_demo'], $app['timezone'])
    || !is_bool($app['debug'])
    || !is_bool($app['mode_demo'])
    || !is_string($app['timezone'])
) {
    throw new RuntimeException('Paramètres applicatifs invalides.');
}

// Échouer explicitement plutôt que publier des fixtures ou activer le debug en production.
if (
    $environment === 'prod'
    && ($app['debug'] || $app['mode_demo'])
) {
    throw new RuntimeException(
        'Debug et démonstration interdits en production.'
    );
}

date_default_timezone_set($app['timezone']);

// Liste fermée de scénarios : aucun nom de fichier ne vient d’une entrée utilisateur.
$scenario = $config['demo']['scenario'] ?? 'normal';

$allowedScenarios = [
    'normal',
    'empty',
    'form-errors',
    'confirmation',
    'unavailable',
    'error',
    'long-city',
];

if (!in_array($scenario, $allowedScenarios, true)) {
    throw new RuntimeException(
        'Scénario de démonstration inconnu.'
    );
}

// Db2 est optionnel : les champs de connexion ne sont exigés que si enabled=true.
$databaseSection = $config['database'] ?? [];

if (!is_array($databaseSection)) {
    throw new RuntimeException(
        'Configuration database invalide.'
    );
}

$databaseEnabled = $databaseSection['enabled'] ?? false;

if (!is_bool($databaseEnabled)) {
    throw new RuntimeException(
        'Le paramètre database.enabled doit être un booléen.'
    );
}

$database = [
    'enabled' => $databaseEnabled,
];

if ($databaseEnabled) {
    foreach (
        ['dsn', 'username', 'password', 'schema'] as $parameter
    ) {
        $value = $databaseSection[$parameter] ?? null;

        if (!is_string($value) || trim($value) === '') {
            throw new RuntimeException(
                'Configuration database incomplète.'
            );
        }
    }

    if (
        !str_starts_with(
            $databaseSection['dsn'],
            'odbc:'
        )
    ) {
        throw new RuntimeException(
            'La connexion database doit utiliser ODBC.'
        );
    }

    // Le schéma sera utilisé comme identifiant SQL dans les contrôles de lecture.
    // Cette liste de caractères autorisés interdit les fragments SQL arbitraires.
    if (
        !preg_match(
            '/^[A-Z][A-Z0-9_]{0,9}$/D',
            $databaseSection['schema']
        )
    ) {
        throw new RuntimeException(
            'Nom de schéma database invalide.'
        );
    }

    $database = [
        'enabled' => true,
        'dsn' => $databaseSection['dsn'],
        'username' => $databaseSection['username'],
        'password' => $databaseSection['password'],
        'schema' => $databaseSection['schema'],
    ];
}

// Ne transmettre aux services que la configuration reconnue et validée.
return [
    'environment' => $environment,
    'debug' => $app['debug'],
    'mode_demo' => $app['mode_demo'],
    'demo_scenario' => $scenario,
    'database' => $database,
];