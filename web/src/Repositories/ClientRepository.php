<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Infrastructure\Db2Connection;
use App\Models\Client;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PDO;
use PDOException;
use RuntimeException;

/**
 * Lit les clients stockés dans Db2.
 *
 * Cette classe contient les requêtes SQL de lecture et transforme
 * les résultats en objets Client utilisables par les contrôleurs.
 *
 * Elle ne produit pas de HTML et ne réalise aucune écriture.
 */
final class ClientRepository
{
    /**
     * Reçoit le service de connexion et la bibliothèque à interroger.
     *
     * Le constructeur n'ouvre aucune connexion réseau.
     *
     * @param Db2Connection $db2 Connexion ouverte seulement à la demande.
     * @param string $schema Bibliothèque IBM i, par exemple AXEL921.
     *
     * @throws InvalidArgumentException Si le nom de bibliothèque est invalide.
     */
    public function __construct(
        private readonly Db2Connection $db2,
        private readonly string $schema
    ) {
        /*
         * Le schéma sera inséré dans le texte SQL comme identifiant.
         *
         * Un paramètre préparé ne peut pas remplacer un nom de table
         * ou de bibliothèque. On limite donc strictement sa forme.
         */
        if (!preg_match('/^[A-Z][A-Z0-9_]{0,9}$/D', $schema)) {
            throw new InvalidArgumentException(
                'Nom de bibliothèque Db2 invalide.'
            );
        }
    }

    /**
     * Retourne tous les clients avec un ordre déterministe.
     *
     * Le tri se fait par nom, prénom, puis identifiant.
     * L'identifiant départage les clients qui portent les mêmes noms.
     *
     * @return list<Client> Liste vide si aucun client n'est présent.
     *
     * @throws RuntimeException Si la lecture ou la conversion échoue.
     */
    public function findAll(): array
    {
        try {
            /*
             * Premier accès effectif à Db2.
             *
             * Db2Connection réutilise la connexion si elle est déjà
             * ouverte dans cette instance.
             */

            $pdo = $this->db2->getConnection();



            /*
             * Sélection explicite des colonnes pour maîtriser
             * les données attendues par hydrate().
             *
             * Cette requête ne reçoit aucune saisie utilisateur.
             * La recherche SQL paramétrée sera ajoutée dans l'issue 7.
             */
            $sql = '
    SELECT
        ID_CLIENT,
        HEX(NOM) AS NOM,
        HEX(PRENOM) AS PRENOM,
        HEX(EMAIL) AS EMAIL,
        HEX(TELEPHONE) AS TELEPHONE,
        HEX(ADRESSE) AS ADRESSE,
        HEX(CODE_POSTAL) AS CODE_POSTAL,
        HEX(VILLE) AS VILLE,
        CREE_LE,
        MODIFIE_LE
    FROM ' . $this->schema . '.CLIENTS AS C
    ORDER BY
        C.NOM ASC,
        C.PRENOM ASC,
        C.ID_CLIENT ASC
';


            $statement = $pdo->query($sql);

            /*
             * Db2Connection active le mode exception de PDO.
             * Ce contrôle couvre aussi un éventuel retour false.
             */
            if ($statement === false) {
                throw new RuntimeException(
                    'Lecture des clients indisponible.'
                );
            }

            try {
                /*
                 * Une ligne devient un tableau associatif :
                 * ['ID_CLIENT' => ..., 'NOM' => ..., ...].
                 */
                $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
            } finally {
                /*
                 * Libérer le curseur même si la récupération échoue.
                 * La connexion peut ensuite servir à une autre lecture.
                 */
                $statement->closeCursor();
            }


            $clients = [];

            foreach ($rows as $row) {
                $clients[] = $this->hydrate($row);
            }

            return $clients;
        } catch (PDOException $exception) {
            /*
             * Ne pas recopier le message brut du pilote :
             * il peut contenir des informations techniques privées.
             */
            error_log(
                'Lecture de la liste Db2 impossible.'
            );

            /*
             * Le contrôleur recevra un message générique.
             * Ne pas joindre l'exception PDO comme exception précédente.
             */
            throw new RuntimeException(
                'Lecture des clients indisponible.'
            );
        }
    }

    /**
     * Transforme une ligne SQL en objet Client.
     *
     * Les noms SQL sont adaptés aux propriétés du modèle :
     * ID_CLIENT devient id, CODE_POSTAL devient codePostal, etc.
     *
     * @param array<string, mixed> $row Ligne renvoyée par PDO.
     *
     * @throws RuntimeException Si l'identifiant ou les dates sont invalides.
     */
    private function hydrate(array $row): Client
    {
        /*
         * Normaliser les clés pour tolérer les différences de casse
         * dans les noms de colonnes retournés par le pilote.
         */
        $row = array_change_key_case($row, CASE_UPPER);

        $rawId = $row['ID_CLIENT'] ?? null;

        /*
         * ODBC peut retourner un BIGINT sous forme de chaîne.
         * Refuser un autre type évite une conversion par flottant.
         */
        if (!is_int($rawId) && !is_string($rawId)) {
            throw new RuntimeException(
                'Identifiant client Db2 invalide.'
            );
        }

        /*
         * FILTER_VALIDATE_INT vérifie aussi la plage supportée
         * par PHP. L'environnement du projet doit rester en 64 bits.
         */
        $id = filter_var(
            $rawId,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1,
                ],
            ]
        );

        if ($id === false) {
            throw new RuntimeException(
                'Identifiant client Db2 hors plage.'
            );
        }



        $nom = $this->textFromDatabase($row['NOM']);

        if ($nom === null) {
            throw new RuntimeException(
                'Nom du client absent dans les données Db2.'
            );
        }

        return new Client(
            id: $id,
            nom: $nom,
            prenom: $this->textFromDatabase($row['PRENOM']),
            email: $this->textFromDatabase($row['EMAIL']),
            telephone: $this->textFromDatabase($row['TELEPHONE']),
            adresse: $this->textFromDatabase($row['ADRESSE']),
            codePostal: $this->textFromDatabase($row['CODE_POSTAL']),
            ville: $this->textFromDatabase($row['VILLE']),
            creeLe: $this->dateFromDatabase(
                (string) $row['CREE_LE']
            ),
            modifieLe: $this->dateFromDatabase(
                (string) $row['MODIFIE_LE']
            ),
        );
    }

    /**
     * Reconstruit un texte UTF-8 depuis le résultat de HEX(colonne).
     *
     * NULL reste NULL. Une chaîne hexadécimale vide donne un texte vide.
     *
     * Aucun échappement HTML n'est effectué ici :
     * Twig reste responsable de l'échappement à l'affichage.
     *
     * @throws RuntimeException Si la valeur hexadécimale ou l'UTF-8 est invalide.
     */
    private function textFromDatabase(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        /*
     * HEX renvoie du texte ASCII.
     * Retirer uniquement les éventuels espaces de remplissage
     * autour de sa représentation hexadécimale.
     */
        $hex = trim((string) $value);

        /*
     * Chaque octet doit être représenté par deux caractères
     * compris entre 0 et 9 ou entre A et F.
     */
        if (
            strlen($hex) % 2 !== 0
            || preg_match('/\A[0-9A-Fa-f]*\z/', $hex) !== 1
        ) {
            throw new RuntimeException(
                'Représentation textuelle Db2 invalide.'
            );
        }

        /*
     * Restaurer exactement les octets stockés en base.
     * Par exemple : C3A8 redevient le caractère UTF-8 è.
     */
        $text = hex2bin($hex);

        if ($text === false) {
            throw new RuntimeException(
                'Décodage du texte Db2 impossible.'
            );
        }

        /*
     * Vérifier le contrat des colonnes CCSID 1208 :
     * les octets reconstruits doivent former du texte UTF-8 valide.
     */
        if (preg_match('//u', $text) !== 1) {
            throw new RuntimeException(
                'Texte Db2 non conforme au format UTF-8.'
            );
        }

        return $text;
    }

    /**
     * Convertit un TIMESTAMP Db2 en date PHP immuable.
     *
     * Convention du projet : les timestamps stockés représentent l'UTC.
     * Le fuseau Europe/Paris est appliqué lors de l'affichage dans Twig.
     *
     * Formats attendus du pilote :
     * - 2026-10-30 10:15:00
     * - 2026-10-30 10:15:00.123456
     *
     * @throws RuntimeException Si le timestamp est mal formé.
     */
    private function dateFromDatabase(
        string $value
    ): DateTimeImmutable {
        $value = trim($value);

        $format = str_contains($value, '.')
            ? '!Y-m-d H:i:s.u'
            : '!Y-m-d H:i:s';

        $date = DateTimeImmutable::createFromFormat(
            $format,
            $value,
            new DateTimeZone('UTC')
        );

        $errors = DateTimeImmutable::getLastErrors();

        /*
         * getLastErrors() retourne false lorsqu'aucune erreur
         * ni aucun avertissement n'a été rencontré.
         */
        if (
            $date === false
            || (
                $errors !== false
                && (
                    $errors['warning_count'] > 0
                    || $errors['error_count'] > 0
                )
            )
        ) {
            throw new RuntimeException(
                'Date client Db2 invalide.'
            );
        }

        return $date;
    }
}
