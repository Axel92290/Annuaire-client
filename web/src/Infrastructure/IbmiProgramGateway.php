<?php

declare(strict_types=1);

namespace App\Infrastructure;

use InvalidArgumentException;
use LogicException;
use PDO;
use PDOException;
use RuntimeException;

/**
 * Encapsule les appels IBM i autorisés par l'application.
 *
 * Cette première version expose uniquement le programme de test GWPING,
 * via la procédure SQL GW_PING_CALL.
 *
 * Aucun nom de programme, commande ou procédure ne peut être fourni
 * comme argument par le navigateur.
 *
 * Construire cet objet n'ouvre aucune connexion.
 */
final class IbmiProgramGateway
{
    /**
     * Reçoit la connexion différée et la bibliothèque autorisée.
     *
     * @throws InvalidArgumentException Si le schéma est invalide.
     */
    public function __construct(
        private readonly Db2Connection $db2,
        private readonly string $schema
    ) {
        /*
         * Le schéma sera utilisé comme identifiant SQL.
         * Le nom de procédure reste fixé dans le code de ping().
         */
        if (!preg_match('/^[A-Z][A-Z0-9_]{0,9}$/D', $schema)) {
            throw new InvalidArgumentException(
                'Bibliothèque de passerelle invalide.'
            );
        }
    }

    /**
     * Appelle le programme de test sans modifier de données métier.
     *
     * Contrat d'entrée : 1 à 10 caractères ASCII parmi A-Z, 0-9 et _,
     * avec une première lettre obligatoire.
     *
     * La demande est une donnée, jamais un nom de programme.
     *
     * @return array{
     *     status: 'success'|'business_error',
     *     code: string,
     *     reply: string
     * }
     *
     * @throws InvalidArgumentException Si l'entrée est invalide.
     * @throws RuntimeException Si l'appel ou sa réponse est invalide.
     */
    public function ping(string $request = 'PING'): array
    {
        /*
         * Refuser avant l'accès réseau toute valeur incompatible
         * avec le contrat CHAR(10) de ce programme de test.
         *
         * Ce contrat ASCII ne doit pas être réutilisé tel quel
         * pour les futurs champs clients Unicode.
         */
        if (
            preg_match('/^[A-Z][A-Z0-9_]{0,9}$/D', $request) !== 1
        ) {
            throw new InvalidArgumentException(
                'Demande de test invalide.'
            );
        }

        try {
            $pdo = $this->db2->getConnection();

            /*
             * Seule cette procédure est appelable par cette méthode.
             * La demande reste un paramètre préparé.
             */
            $statement = $pdo->prepare(
                'CALL ' . $this->schema . '.GW_PING_CALL(?)'
            );

            if ($statement === false) {
                throw new RuntimeException(
                    'Préparation de l’appel IBM i impossible.'
                );
            }

            try {
                $statement->bindValue(
                    1,
                    $request,
                    PDO::PARAM_STR
                );

                $statement->execute();

                $row = false;

                /*
                 * Parcourir les éventuels résultats intermédiaires
                 * jusqu'à la première ligne retournée.
                 */
                do {
                    if ($statement->columnCount() > 0) {
                        $row = $statement->fetch(PDO::FETCH_ASSOC);

                        if ($row !== false) {
                            break;
                        }
                    }
                } while ($statement->nextRowset());
            } finally {
                $statement->closeCursor();
            }

            if ($row === false) {
                throw new RuntimeException(
                    'Réponse IBM i absente.'
                );
            }

            $row = array_change_key_case($row, CASE_UPPER);

            /*
             * La procédure SQL convertit les sorties en UTF-8,
             * puis en hexadécimal ASCII pour le transport ODBC.
             */
            $reply = $this->decodeText(
                $row['REPLY_HEX'] ?? null,
                30
            );

            $resultCode = $this->decodeText(
                $row['RESULT_CODE_HEX'] ?? null,
                5
            );

            /*
             * Contrat actuel du programme de test.
             * Un code inconnu signifie que la réponse ne correspond
             * plus au contrat attendu : c'est une erreur technique.
             */
            if (!in_array($resultCode, ['00000', 'B0001'], true)) {
                throw new RuntimeException(
                    'Code de réponse IBM i inattendu.'
                );
            }

            return [
                'status' => $resultCode === '00000'
                    ? 'success'
                    : 'business_error',
                'code' => $resultCode,
                'reply' => $reply,
            ];
        } catch (PDOException $exception) {
            /*
             * Journaliser uniquement un code borné.
             * Ne pas recopier le message brut du pilote,
             * la demande ou les paramètres de connexion.
             */
            $sqlState = (string) (
                $exception->errorInfo[0]
                ?? $exception->getCode()
            );

            if (!preg_match('/^[A-Z0-9]{5}$/D', $sqlState)) {
                $sqlState = 'UNKNOWN';
            }

            error_log(
                'Appel IBM i impossible — SQLSTATE : ' . $sqlState
            );

            throw new RuntimeException(
                'Passerelle IBM i indisponible.'
            );
        } catch (LogicException $exception) {
            /*
             * Db2Connection lance notamment une LogicException
             * lorsque l'intégration est désactivée.
             *
             * L'appelant de la passerelle reçoit un échec technique
             * et non un faux résultat métier.
             */
            throw new RuntimeException(
                'Passerelle IBM i désactivée ou indisponible.'
            );
        }
    }

    /**
     * Décode et vérifie une sortie textuelle du programme.
     *
     * @param mixed $value Valeur hexadécimale retournée par SQL.
     * @param int $maxLength Longueur maximale en caractères.
     *
     * @throws RuntimeException Si la représentation est invalide.
     */
    private function decodeText(
        mixed $value,
        int $maxLength
    ): string {
        if (!is_string($value)) {
            throw new RuntimeException(
                'Sortie IBM i invalide.'
            );
        }

        $hex = trim($value);

        if (
            strlen($hex) % 2 !== 0
            || preg_match('/\A[0-9A-Fa-f]*\z/', $hex) !== 1
        ) {
            throw new RuntimeException(
                'Sortie hexadécimale IBM i invalide.'
            );
        }

        $text = hex2bin($hex);

        if (
            $text === false
            || preg_match('//u', $text) !== 1
        ) {
            throw new RuntimeException(
                'Encodage de sortie IBM i invalide.'
            );
        }

        if (mb_strlen($text, 'UTF-8') > $maxLength) {
            throw new RuntimeException(
                'Longueur de sortie IBM i invalide.'
            );
        }

        return $text;
    }
}
