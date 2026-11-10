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
     * Recherche les clients dont le nom contient le texte demandé.
     *
     * Le filtre est transmis comme paramètre JSON ASCII, puis reconstruit
     * en UTF-8 par Db2. Cette méthode reprend le transport validé
     * dans le diagnostic des paramètres.
     *
     * La comparaison ignore la casse avec UPPER(), sans retirer les accents.
     * Les caractères %, _ et ! de la saisie sont traités littéralement.
     *
     * @return list<Client>
     *
     * @throws InvalidArgumentException Si la recherche est invalide.
     * @throws RuntimeException Si la lecture Db2 échoue.
     */
    public function findByName(string $search): array
    {
        /*
     * Une recherche vide conserve le comportement de la liste complète.
     * Le service normalise les espaces avant cet appel.
     */
        if ($search === '') {
            return $this->findAll();
        }

        /*
     * Protéger aussi l'entrée du repository si celui-ci est appelé
     * directement, sans passer par le service.
     */
        if (
            preg_match('//u', $search) !== 1
            || mb_strlen($search, 'UTF-8') > 100
        ) {
            throw new InvalidArgumentException(
                'Recherche invalide.'
            );
        }

        /*
     * Dans LIKE :
     * - % représente normalement plusieurs caractères ;
     * - _ représente normalement un caractère ;
     * - ! sera notre caractère d'échappement.
     *
     * strtr() applique ces remplacements sans rééchapper
     * les caractères qu'il vient d'ajouter.
     */
        $escapedSearch = strtr($search, [
            '!' => '!!',
            '%' => '!%',
            '_' => '!_',
        ]);

        /*
     * Seuls ces deux % ajoutés par l'application sont des jokers.
     * Ils permettent une recherche par sous-chaîne.
     */
        $pattern = '%' . $escapedSearch . '%';

        /*
     * Sans JSON_UNESCAPED_UNICODE, les caractères non ASCII
     * sont transmis sous forme d'échappements JSON.
     *
     * Le pilote ODBC reçoit donc uniquement du texte ASCII.
     */
        $payload = json_encode(
            ['q' => $pattern],
            JSON_THROW_ON_ERROR
        );

        try {
            $pdo = $this->db2->getConnection();

            /*
         * La CTE reconstruit le motif en texte UTF-8.
         *
         * Sa capacité couvre les caractères échappés et les deux
         * jokers ajoutés autour de la recherche.
         *
         * Les colonnes retournées utilisent toujours HEX()
         * pour conserver la lecture UTF-8 validée précédemment.
         */
            $sql = '
            WITH CLIENT_SEARCH_FILTER AS (
                SELECT
                    JSON_VALUE(
                        CAST(? AS VARCHAR(4096) CCSID 1208),
                        \'$.q\'
                        RETURNING VARCHAR(808) CCSID 1208
                        ERROR ON ERROR
                    ) AS SEARCH_PATTERN
                FROM SYSIBM.SYSDUMMY1
            )
            SELECT
                C.ID_CLIENT,
                HEX(C.NOM) AS NOM,
                HEX(C.PRENOM) AS PRENOM,
                HEX(C.EMAIL) AS EMAIL,
                HEX(C.TELEPHONE) AS TELEPHONE,
                HEX(C.ADRESSE) AS ADRESSE,
                HEX(C.CODE_POSTAL) AS CODE_POSTAL,
                HEX(C.VILLE) AS VILLE,
                C.CREE_LE,
                C.MODIFIE_LE
            FROM ' . $this->schema . '.CLIENTS AS C
            CROSS JOIN CLIENT_SEARCH_FILTER AS F
            WHERE UPPER(C.NOM)
                LIKE UPPER(F.SEARCH_PATTERN)
                ESCAPE \'!\'
            ORDER BY
                C.NOM ASC,
                C.PRENOM ASC,
                C.ID_CLIENT ASC
        ';

            $statement = $pdo->prepare($sql);

            if ($statement === false) {
                throw new RuntimeException(
                    'Recherche des clients indisponible.'
                );
            }

            try {
                /*
             * Le document JSON reste séparé du texte SQL.
             * Aucun fragment de saisie n'est concaténé dans la requête.
             */
                $statement->bindValue(
                    1,
                    $payload,
                    PDO::PARAM_STR
                );

                $statement->execute();

                $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
            } finally {
                // Libérer le curseur, même si la récupération échoue.
                $statement->closeCursor();
            }

            $clients = [];

            foreach ($rows as $row) {
                $clients[] = $this->hydrate($row);
            }

            return $clients;
        } catch (PDOException $exception) {
            /*
         * Conserver une erreur générique.
         * Ne pas afficher le message brut du pilote ou le paramètre.
         */
            error_log(
                'Recherche des clients Db2 impossible.'
            );

            throw new RuntimeException(
                'Recherche des clients indisponible.'
            );
        }
    }

    /**
     * Recherche un client à partir de son identifiant technique.
     *
     * L'identifiant reçu de l'URL reste une chaîne jusqu'à sa validation.
     * Une valeur invalide ou hors plage retourne null sans interroger Db2.
     *
     * La requête utilise un paramètre : aucune valeur de l'URL n'est
     * concaténée dans le texte SQL.
     *
     * @param string $id Identifiant décimal positif, sans zéro initial.
     *
     * @return Client|null Client trouvé, ou null si l'identifiant est
     *                     invalide ou si aucune ligne ne correspond.
     *
     * @throws RuntimeException Si la lecture Db2 échoue.
     */
    public function findById(string $id): ?Client
    {
        /*
     * Vérifier la forme avant toute conversion.
     *
     * Refuser notamment :
     * - les nombres négatifs ;
     * - zéro ;
     * - les espaces ;
     * - les décimaux ;
     * - les notations scientifiques ;
     * - les identifiants avec un zéro initial.
     */
        if (preg_match('/^[1-9][0-9]{0,18}$/D', $id) !== 1) {
            return null;
        }

        /*
     * Vérifier aussi la plage représentable par PHP.
     *
     * Le projet utilise un PHP 64 bits, compatible avec
     * la plage positive du BIGINT signé de Db2.
     *
     * Aucun passage par un flottant n'est effectué.
     */
        $validatedId = filter_var(
            $id,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1,
                ],
            ]
        );

        if ($validatedId === false) {
            return null;
        }

        try {
            $pdo = $this->db2->getConnection();

            /*
         * Reprendre le transport hexadécimal validé dans l'issue 5
         * pour préserver les octets UTF-8 avec ODBC sous Windows.
         *
         * hydrate() décode ces colonnes avec textFromDatabase().
         *
         * Le schéma est validé par le constructeur.
         * L'identifiant reste un paramètre SQL.
         */
            $sql = '
            SELECT
                C.ID_CLIENT,
                HEX(C.NOM) AS NOM,
                HEX(C.PRENOM) AS PRENOM,
                HEX(C.EMAIL) AS EMAIL,
                HEX(C.TELEPHONE) AS TELEPHONE,
                HEX(C.ADRESSE) AS ADRESSE,
                HEX(C.CODE_POSTAL) AS CODE_POSTAL,
                HEX(C.VILLE) AS VILLE,
                C.CREE_LE,
                C.MODIFIE_LE
            FROM ' . $this->schema . '.CLIENTS AS C
            WHERE C.ID_CLIENT = CAST(? AS BIGINT)
        ';

            $statement = $pdo->prepare($sql);

            if ($statement === false) {
                throw new RuntimeException(
                    'Lecture de la fiche client indisponible.'
                );
            }

            try {
                /*
             * Transmettre l'identifiant décimal comme une chaîne.
             *
             * Le CAST explicite demande à Db2 de le traiter comme
             * un BIGINT et évite de dépendre d'une liaison entière
             * ODBC potentiellement limitée à une autre largeur.
             */
                $statement->bindValue(
                    1,
                    (string) $validatedId,
                    PDO::PARAM_STR
                );

                $statement->execute();

                /*
             * La clé primaire garantit au maximum une ligne.
             * fetch() retourne false si aucun client ne correspond.
             */
                $row = $statement->fetch(PDO::FETCH_ASSOC);
            } finally {
                // Libérer le curseur, y compris après une erreur.
                $statement->closeCursor();
            }

            if ($row === false) {
                return null;
            }

            /*
         * Réutiliser la conversion déjà validée pour la liste :
         * identifiant, textes UTF-8, valeurs NULL et dates UTC.
         */
            return $this->hydrate($row);
        } catch (PDOException $exception) {
            /*
         * Ne pas exposer le message brut du pilote, la requête
         * ou les paramètres privés dans la réponse HTTP.
         */
            error_log(
                'Lecture de la fiche client Db2 impossible.'
            );

            throw new RuntimeException(
                'Lecture de la fiche client indisponible.'
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
