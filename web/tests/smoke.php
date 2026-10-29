<?php

// Ancien test de démonstration : lire les réserves ci-dessous avant utilisation.
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Controllers\ClientController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

$checks = 0;
/**
 * Interrompt le script au premier contrôle refusé et compte les succès.
 *
 * Attention : ce script historique doit être adapté à l’authentification,
 * au service client_input_validator et aux jetons create/update actuels.
 * Il ne constitue pas en l’état une validation complète du socle actuel.
 */
function check(bool $condition, string $message): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException($message);
    }
    ++$checks;
}

// Construction directe des contrôleurs : ce script ne teste pas le filtrage HTTP
// d’index.php. La fabrique exige désormais également auth.local.php.
$request = Request::create('/clients');
$config = ['debug' => true, 'mode_demo' => true];
$services = (require dirname(__DIR__) . '/config/services.php')($config, $request);
$session = new Session(new MockArraySessionStorage());
$request->setSession($session);
$stack = new RequestStack();
$stack->push($request);
$csrf = new CsrfTokenManager(null, new SessionTokenStorage($stack));
// Fabrique de controleurs pour chaque scenario de test ; ne lance pas de requete HTTP.
$controller = static fn (string $scenario = 'normal', bool $demo = true): ClientController => new ClientController(
    $services['twig'], $session, $demo, $csrf, $services['validator'], $scenario
);

// Liste et consultation : filtrage, ID de presentation, echappement et valeurs facultatives.
$normal = $controller();
$response = $normal->index(Request::create('/clients?q=Mar'));
check($response->getStatusCode() === 200, 'La liste doit répondre 200.');
$document = new DOMDocument();
@$document->loadHTML('<?xml encoding="UTF-8">' . $response->getContent());
$xpath = new DOMXPath($document);
check($xpath->query('//table/tbody/tr')->length === 4, 'Mar doit retourner quatre clients.');
check(str_contains($response->getContent(), '000128'), 'La référence doit être formatée.');
check(str_contains($response->getContent(), '/clients/128'), 'Les routes gardent l’identifiant technique.');
check(str_contains($normal->index(Request::create('/clients?q=introuvable'))->getContent(), 'Aucun client trouvé'), 'État sans résultat absent.');
check(str_contains($normal->index(Request::create('/clients?q=%22%3E%3Cscript%3E'))->getContent(), '&lt;script&gt;'), 'La recherche doit être échappée.');
check($normal->index(Request::create('/clients?q[]=Mar'))->getStatusCode() === 200, 'Une recherche structurée ne doit pas provoquer de 500.');
check(str_contains($normal->show('305')->getContent(), '—'), 'Les valeurs facultatives doivent être représentées.');
check(str_contains($normal->edit('128')->getContent(), 'value="Camille"'), 'La modification doit être préremplie.');
try {
    $normal->show('9223372036854775808');
    throw new RuntimeException('Un identifiant hors plage ne doit pas trouver un client.');
} catch (ResourceNotFoundException) {
    check(true, 'Client absent.');
}

// Apercus POST : verifier erreurs, conservation des saisies et absence de mutation des fixtures.
// Les identifiants CSRF ci-dessous sont historiques et doivent etre adaptes avant execution.
$before = hash_file('sha256', dirname(__DIR__) . '/fixtures/clients.php');
$token = $csrf->getToken('client_preview')->getValue();
$invalid = $normal->preview(Request::create('/clients/preview', 'POST', [
    '_token' => $token, 'nom' => ' ', 'prenom' => 'Camille', 'email' => 'invalide',
]));
check($invalid->getStatusCode() === 422, 'Une saisie invalide doit répondre 422.');
check(str_contains($invalid->getContent(), 'aria-invalid="true"'), 'Les erreurs doivent être associées aux champs.');
check(str_contains($invalid->getContent(), 'value="Camille"'), 'Les saisies doivent être conservées.');
$valid = $normal->preview(Request::create('/clients/preview', 'POST', [
    '_token' => $token, 'nom' => 'Nouveau client', 'codePostal' => '01230',
]));
check($valid->getStatusCode() === 200, 'Un aperçu valide doit répondre 200.');
check(str_contains($valid->getContent(), 'Cet aperçu n’enregistre aucun client.'), 'Aucun faux succès ne doit être affiché.');
check(str_contains($valid->getContent(), 'value="01230"'), 'Le code postal doit rester du texte.');
check(hash_file('sha256', dirname(__DIR__) . '/fixtures/clients.php') === $before, 'L’aperçu ne doit pas modifier les fixtures.');
check($normal->preview(Request::create('/clients/preview', 'POST', ['nom' => 'Client']))->getStatusCode() === 403, 'Un jeton absent doit être refusé.');
check($normal->preview(Request::create('/clients/preview', 'POST', ['_token' => [], 'nom' => []]))->getStatusCode() === 403, 'Les valeurs structurées doivent être refusées sans exception.');
check($normal->preview(Request::create('/clients/128/preview', 'POST', ['_token' => $token, 'nom' => 'Autre nom']), '128')->getStatusCode() === 200, 'L’aperçu de modification doit fonctionner.');
check(str_contains($normal->show('128')->getContent(), 'Martin'), 'La fiche doit rester inchangée après l’aperçu.');

// Etats simules : liste vide, erreurs de saisie, confirmation fictive et ville longue.
check(str_contains($controller('empty')->index($request)->getContent(), 'Votre annuaire est encore vide'), 'Scénario empty absent.');
check(str_contains($controller('form-errors')->new()->getContent(), 'resume-erreurs'), 'Scénario form-errors absent.');
check(str_contains($controller('confirmation')->index($request)->getContent(), 'Aperçu de confirmation'), 'La confirmation doit être explicitement fictive.');
check(str_contains($controller('long-city')->show('128')->getContent(), 'Saint-Rémy'), 'Scénario ville longue absent.');
foreach (['unavailable' => 503, 'error' => 500] as $scenario => $status) {
    check($controller($scenario)->index($request)->getStatusCode() === $status, "Le scénario $scenario doit répondre $status.");
}
check($controller('normal', false)->index($request)->getStatusCode() === 503, 'Le mode réel non raccordé doit répondre 503.');
check($controller('normal', false)->preview(Request::create('/clients/preview', 'POST'))->getStatusCode() === 503, 'Les aperçus doivent être désactivés hors démonstration.');
check($normal->error(404)->getStatusCode() === 404, 'Le template 404 doit fonctionner.');

echo "$checks vérifications réussies.\n";
