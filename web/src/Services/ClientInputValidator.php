<?php

declare(strict_types=1);

namespace App\Services;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Normalise et valide les champs clients reçus dans un POST.
 *
 * Sépare les valeurs à réafficher, les données normalisées et les erreurs.
 * Ne sauvegarde rien et n'encode pas le HTML : Twig échappe à la sortie.
 */
final class ClientInputValidator
{
    // Limites métier en caractères, partagées avec le contrôleur et à aligner avec Db2.
    public const FIELDS = [
        'nom' => 100,
        'prenom' => 100,
        'email' => 254,
        'telephone' => 30,
        'adresse' => 255,
        'codePostal' => 20,
        'ville' => 100,
    ];

    /**
     * Reçoit le moteur Symfony chargé d’exécuter les contraintes par champ.
     */
    public function __construct(
        private readonly ValidatorInterface $validator
    ) {
    }

    /**
     * Extrait uniquement les chaînes à réafficher dans le formulaire.
     *
     * Les tableaux ou autres types deviennent une chaîne vide pour le rendu ;
     * validate() les refuse séparément. Aucun champ inconnu n’est réinjecté.
     * @param array<string, mixed> $input Corps POST non fiable.
     * @return array<string, string>
     */
    public function values(array $input): array
    {
        $values = [];

        foreach (self::FIELDS as $field => $limit) {
            $value = $input[$field] ?? '';

            $values[$field] = is_string($value)
                ? $value
                : '';
        }

        return $values;
    }

    /**
     * Refuse les champs inconnus, normalise les textes et applique les limites.
     *
     * _token est autorisé comme métadonnée mais n’est pas une donnée métier ;
     * le contrôleur doit vérifier sa validité avant d’appeler cette méthode.
     * Les champs facultatifs vides deviennent null ; nom reste obligatoire.
     * @param array<string, mixed> $input Données POST à contrôler.
     * @return array{values: array<string, string>, data: array<string, string|null>, errors: array<string, list<string>>}
     */
    public function validate(array $input): array
    {
        $values = $this->values($input);
        $data = [];
        $errors = [];

        $allowedFields = [
            ...array_keys(self::FIELDS),
            '_token',
        ];

        // Liste blanche : des champs ajoutés manuellement au POST sont refusés.
        $unknownFields = array_diff(
            array_keys($input),
            $allowedFields
        );

        if ($unknownFields !== []) {
            $errors['_global'][] =
                'Le formulaire contient des champs non autorisés.';
        }

        foreach (self::FIELDS as $field => $limit) {
            $rawValue = $input[$field] ?? '';

            if (!is_string($rawValue)) {
                $errors[$field][] =
                    'Renseignez une valeur textuelle.';

                $data[$field] = null;
                continue;
            }

            // Normaliser sans encoder : les valeurs restent réutilisables hors du HTML.
            $normalized = trim($rawValue);

            $data[$field] = (
                $field !== 'nom'
                && $normalized === ''
            )
                ? null
                : $normalized;

            $constraints = [
                new Assert\Length(
                    max: $limit,
                    maxMessage:
                        'Limitez ce champ à {{ limit }} caractères.'
                ),
            ];

            if ($field === 'nom') {
                $constraints[] = new Assert\NotBlank(
                    message: 'Renseignez le nom du client.'
                );
            }

            if ($field === 'email') {
                $constraints[] = new Assert\Email(
                    message: 'L’adresse e-mail n’est pas valide.'
                );
            }

            $violations = $this->validator->validate(
                $data[$field],
                $constraints
            );

            foreach ($violations as $violation) {
                $errors[$field][] = $violation->getMessage();
            }
        }

        return [
            'values' => $values,
            'data' => $data,
            'errors' => $errors,
        ];
    }
}