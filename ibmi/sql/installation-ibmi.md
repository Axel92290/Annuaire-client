| PRENOM | 100 caractères | 400 octets |
| EMAIL | 254 caractères | 1016 octets |
| TELEPHONE | 30 caractères | 120 octets |
| ADRESSE | 255 caractères | 1020 octets |
| CODE_POSTAL | 20 caractères | 80 octets |
| VILLE | 100 caractères | 400 octets |

Le nom est obligatoire et non vide après trim. Les champs facultatifs utilisent
`NULL` pour une absence ; les valeurs constituées uniquement d'espaces sont
refusées par les contraintes. PHP et RPG assureront la normalisation des saisies
et la validation complète du format email avant les futures écritures.

`CODE_POSTAL` possède le nom système court `CODEPOSTAL` pour l'intégration RPG.
Les valeurs sont stockées en texte simple, sans pré-encodage HTML.

## Identifiants et dates

`ID_CLIENT` est une clé primaire positive générée par Db2. Les références sur
six chiffres restent un format de présentation ; elles ne sont pas stockées.
Les identifiants des fixtures Db2 peuvent différer de ceux des fixtures PHP.
Des trous dans la séquence après une insertion refusée sont normaux.

Les insertions renseignent `CREE_LE` et `MODIFIE_LE` au même instant UTC avec :

```sql
CURRENT_TIMESTAMP - CURRENT_TIMEZONE
```

Les deux colonnes sont obligatoires et ne possèdent pas de valeur par défaut.
La contrainte de dates interdit une modification antérieure à la création.
Les futurs programmes RPG conserveront `CREE_LE` et actualiseront `MODIFIE_LE`
lors des modifications réussies. La table ne réalise pas cette actualisation
automatiquement. PHP/Twig présentera les dates en `Europe/Paris`.

## Contrôles de lecture

```sql
SELECT ID_CLIENT, NOM, PRENOM, TELEPHONE, CODE_POSTAL, VILLE
FROM AXEL921.CLIENTS
ORDER BY ID_CLIENT;

SELECT ID_CLIENT, NOM, PRENOM
FROM AXEL921.CLIENTS
WHERE UPPER(NOM) LIKE '%MAR%'
ORDER BY NOM;

SELECT ID_CLIENT, CREE_LE, MODIFIE_LE
FROM AXEL921.CLIENTS
ORDER BY ID_CLIENT;
```

Vérifier les accents, les champs absents, les zéros initiaux et quatre résultats
pour `Mar`. Dans le jeu initial, les deux dates de chaque fiche sont identiques.

## Contrôles des refus

Les instructions ci-dessous sont des tests négatifs : les exécuter séparément
dans STRSQL et relever leurs messages. Chacune doit être refusée.

```sql
-- Nom constitué uniquement d'espaces : contrainte CLT_NOM_CK.
INSERT INTO AXEL921.CLIENTS (NOM, CREE_LE, MODIFIE_LE)
VALUES ('   ', CURRENT_TIMESTAMP - CURRENT_TIMEZONE,
        CURRENT_TIMESTAMP - CURRENT_TIMEZONE);

-- Nom absent : violation NOT NULL.
INSERT INTO AXEL921.CLIENTS (NOM, CREE_LE, MODIFIE_LE)
VALUES (NULL, CURRENT_TIMESTAMP - CURRENT_TIMEZONE,
        CURRENT_TIMESTAMP - CURRENT_TIMEZONE);

-- Nom de 101 caractères : contrainte CLT_NOM_CK.
INSERT INTO AXEL921.CLIENTS (NOM, CREE_LE, MODIFIE_LE)
VALUES (REPEAT('A', 101), CURRENT_TIMESTAMP - CURRENT_TIMEZONE,
        CURRENT_TIMESTAMP - CURRENT_TIMEZONE);

-- Dates incohérentes : contrainte CLT_DAT_CK.
INSERT INTO AXEL921.CLIENTS (NOM, CREE_LE, MODIFIE_LE)
VALUES ('Test dates invalides', TIMESTAMP('2026-10-26-10.00.00'),
        TIMESTAMP('2026-10-25-10.00.00'));
```

Après ces refus, vérifier que le compteur reste à cinq :

```sql
SELECT COUNT(*) AS NB_CLIENTS
FROM AXEL921.CLIENTS;
```

## État de validation

Ce tableau distingue les opérations confirmées des contrôles dont le résultat
reste à consigner. Remplacer les mentions « à confirmer » après exécution réelle.

| Contrôle | État |
|---|---|
| Création et accès à la table | Confirmés par l'utilisateur |
| Insertion du jeu fictif | Exécutée ; une double exécution a été signalée |
| Nettoyage des doublons | Confirmé par l'utilisateur |
| Lecture et accents | À confirmer |
| Téléphone et code postal conservés en texte | Confirmé par l'utilisateur |
| Quatre résultats pour `Mar` | Confirmé par l'utilisateur |
| Dates initiales identiques |Confirmé par l'utilisateur |
| Refus du nom vide | Confirmé par l'utilisateur |
| Refus du nom NULL | Confirmé par l'utilisateur |
| Refus d'un nom de 101 caractères | Confirmé par l'utilisateur |
| Refus de dates incohérentes | Confirmé par l'utilisateur |
| Compteur final de cinq clients | Confirmé par l'utilisateur |

La connexion du front à Db2 n'est pas réalisée dans cette issue. Elle relève
de #4 ; les programmes d'écriture RPG relèvent des issues suivantes.
