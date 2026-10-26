<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

// Récupération de la configuration locale.
$environment = getenv('APP_ENV') ?: 'dev';

if (!in_array($environment, ['dev', 'prod'], true)) {
    throw new RuntimeException('Environnement non reconnu.');
}

$configPath = __DIR__ . '/' . $environment . '.ini';

if (!is_file($configPath)) {
    throw new RuntimeException('Configuration locale absente.');
}

// Chargement de la configuration locale.
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

if (
    $environment === 'prod'
    && ($app['debug'] || $app['mode_demo'])
) {
    throw new RuntimeException(
        'Debug et démonstration interdits en production.'
    );
}

date_default_timezone_set($app['timezone']);

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

return [
    'environment' => $environment,
    'debug' => $app['debug'],
    'mode_demo' => $app['mode_demo'],
    'demo_scenario' => $scenario,
];