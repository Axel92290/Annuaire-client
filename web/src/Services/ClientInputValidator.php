<?php

declare(strict_types=1);

namespace App\Services;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ClientInputValidator
{
    public const FIELDS = [
        'nom' => 100,
        'prenom' => 100,
        'email' => 254,
        'telephone' => 30,
        'adresse' => 255,
        'codePostal' => 20,
        'ville' => 100,
    ];

    public function __construct(
        private readonly ValidatorInterface $validator
    ) {
    }

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

    public function validate(array $input): array
    {
        $values = $this->values($input);
        $data = [];
        $errors = [];

        $allowedFields = [
            ...array_keys(self::FIELDS),
            '_token',
        ];

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