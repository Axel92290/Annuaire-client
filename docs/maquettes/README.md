# Référence UI/UX — annuaire clients

Référence adoptée le 25 octobre 2026. Cette version remplace les précédentes consignes visuelles (Inter, bleu et cartes). Le périmètre fonctionnel et l’architecture PHP/Twig, Bootstrap, Db2 et RPG sont conservés.

## Fichiers de référence

- [Maquette complète, 5 pages](maquette-ui-ux.pdf) : fondations, liste/recherche, fiche, formulaire en erreur et liste mobile.
- [Notes de parcours et d’accessibilité](parcours-accessibilite.png).

Les fichiers sont les originaux fournis, renommés pour disposer de liens stables dans GitHub. Ils documentent la cible visuelle ; leur ajout ne signifie pas que les pages web sont déjà implémentées.

## Direction visuelle

Une interface sobre et éditoriale : typographie contrastée, espaces généreux, filets fins, boutons rectangulaires, sans ombres ni arrondis. Le rouge est réservé aux erreurs et alertes. Bootstrap sert de base de mise en page et de comportement ; son apparence par défaut doit être adaptée dans app.css, chargé après Bootstrap.

### Typographie

| Usage | Police / règle |
|---|---|
| Titres et noms mis en avant | Libre Caslon Display ; repli Georgia, serif |
| Corps, navigation, champs et libellés | Hanken Grotesk ; repli Arial, sans-serif |
| Corps | 15 px / 24 px |
| Champs | 17 px / 24 px |
| Libellés | 12 px / 16 px, capitales, espacement 0,12 em |

Les tailles de titres s’adaptent à l’écran en conservant la hiérarchie de la maquette. Héberger les polices localement, fournir leurs licences et utiliser font-display: swap. Charger uniquement les graisses nécessaires et garder le formulaire utilisable si les polices ne chargent pas.

### Couleurs

| Rôle | Valeur |
|---|---|
| Encre | #111111 |
| Texte secondaire | #55554F |
| Filet | #D9D9D4 |
| Fond neutre | #F3F2EE |
| Papier | #FFFFFF |
| Alerte / erreur | #8F1D2C |

### Mise en page et composants

- Grille de 12 colonnes ; repères desktop : marges 48 px, gouttières 24 px, largeur utile 1184 px.
- Largeurs de vérification : 390, 768 et 1280 px ; marges et colonnes adaptées sur petit écran, sans débordement horizontal.
- Espacements de référence : 8, 16, 24, 40, 56 et 96 px.
- Angles droits ; pas d’ombres. Séparateurs de 1 px, soulignement d’erreur de 2 px.
- Bouton principal noir à texte clair ; bouton secondaire à contour ; liens de texte soulignés.
- Champs à soulignement, avec libellés persistants ; le focus et l’erreur ne doivent pas dépendre de la seule couleur.
- Liste en tableau sur desktop, avec en-têtes et lien « Consulter » ; liste verticale séparée par des filets sur mobile.
- Fiche : identité à gauche, coordonnées et suivi à droite ; empilement sur mobile.
- Formulaire : introduction à gauche, saisie à droite ; nom/prénom puis code postal/ville groupés sur desktop et empilés si nécessaire.
- Le formulaire de modification, les pages d’erreur et les états absents du PDF reprennent ces mêmes règles.

## Parcours et données affichées

Conserver les routes GET /clients, /clients/new, /clients/{id} et /clients/{id}/edit, ainsi que les pages 404 et 500. La suppression reste hors V1.

La référence affichée est numérique sur six chiffres, par exemple 000128, sans préfixe CLT-. Ce format relève uniquement de la présentation : l’identifiant technique en base et la valeur utilisée dans les routes restent inchangés.

Le compteur est calculé depuis les résultats : la nouvelle maquette présente quatre résultats pour « Mar ». Un succès d’enregistrement n’apparaît qu’après confirmation d’une écriture réelle ; un aperçu de ce message reste un scénario de démonstration explicitement identifié dans #15.

Les mentions [MAISON] et [UTILISATEUR] sont des emplacements de contenu. Elles ne justifient pas à elles seules de nouvelles fonctionnalités. L’utilisateur et la déconnexion ne doivent pas être simulés comme une authentification réelle ; leur activation suit le contrôle d’accès défini dans #14.

## États et accessibilité

- Liste vide, aucun résultat, indisponibilité Db2, client absent et erreur technique.
- Formulaires au repos, au focus, en erreur et en cours d’envoi ; conserver les saisies après un refus.
- Résumé des erreurs annoncé et relié aux champs, messages par champ et focus orienté vers la correction.
- Contraste du texte d’au moins 4,5:1, focus visible de 2 px et cibles interactives de 48 px.
- Libellé relié à chaque champ, tableaux avec en-têtes, liens de consultation accessibles au clavier.
- Le champ facultatif « ville » accepte un texte long sans casser la mise en page.
- Parcours fonctionnel sans JavaScript, y compris recherche, consultation et formulaires métier.
- Sur mobile, prévoir une soumission de recherche identifiable et accessible : l’absence de bouton visible dans l’aperçu n’annule pas ce besoin.
- Pages 404/500 et messages d’erreur sans détails techniques ni secrets.

## Suivi

L’intégration est suivie dans [#15](https://github.com/Axel92290/Annuaire-client/issues/15), sur la branche existante feat/15-interface-bootstrap. Comparer les pages réalisées au PDF aux trois largeurs de référence, puis vérifier clavier, erreurs et textes longs. Les lectures et écritures sont raccordées ensuite aux mêmes templates ; voir [la feuille de route](../roadmap.md).
