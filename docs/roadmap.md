# Feuille de route — interface d’abord

Statut : plan de réalisation. Aucune case n’est considérée terminée par la mise à jour de cette documentation.

## Référence et principe

La maquette fournie (« Annuaire clients — Maquette UI UX.pdf » et « figma_p2_annuaire-client.PNG ») guide les écrans, les composants et le comportement mobile. Ces références ont été fournies pendant la préparation ; ce document ne prétend pas qu’elles sont déjà versionnées dans le dépôt.

Le contrat des champs est fixé dans #1. Le socle PHP/Twig est créé dans #3, puis toute l’interface dans #15 avec des données fictives. Les issues de lecture et d’écriture remplacent ensuite la source des données et branchent les traitements sur les mêmes templates.

## Étapes et critères de passage

| Étape | Issues | Résultat attendu avant de poursuivre |
|---|---|---|
| 1. Contrat et socle | #1, #3 | Champs partagés, routes, rendu Twig et configuration locale sans connexion Db2 obligatoire |
| 2. Interface complète | #15 | Liste, recherche fictive, fiche, formulaires, erreurs et mobile validés visuellement |
| 3. Lectures réelles | #2, #4, #5, #6, #7 | Table et connexion vérifiées ; mêmes écrans alimentés par Db2 |
| 4. Écritures sécurisées | #14, #8, #9, #10, #11, #12 | Passerelle prouvée, sécurité contrôlée, ajout et modification confirmés sur IBM i |
| 5. Recette V1 | #13 | Installation reproductible et scénarios fonctionnels, sécurité et accessibilité vérifiés |
| 6. Évolution facultative | #16 | Décision motivée sur TypeScript après la V1 |

L’ordre décrit des livrables, pas une obligation d’attendre pour toutes les tâches techniques. #2 peut commencer après #1 ; #4 peut commencer après #2 et #3, en parallèle de #15. Une preuve ODBC et une preuve d’appel RPG tôt dans le projet réduisent les incertitudes d’intégration. #14 peut avancer après #3.

## Validation de l’interface dans #15

- Templates définitifs : liste, fiche, ajout, modification, formulaire partagé, pages 404 et 500.
- Assets Bootstrap locaux et styles proches de la maquette ; conserver les licences et les solutions de repli des polices.
- Liste normale et vide, recherche sans résultat, erreurs de formulaire et erreur technique présentée à l’utilisateur.
- Navigation mobile et clavier ; vérification aux largeurs 390, 768 et 1280 px.
- Routes GET utilisables sans Db2, compte PUB400 ou pilote IBM installé.
- Recherche fictive cohérente et compteurs calculés depuis les résultats : corriger l’écart entre le compteur et les lignes de l’exemple.
- Aucun texte d’explication technique du CSRF dans le parcours utilisateur.
- Aucun faux succès d’enregistrement. Les écritures métier ne sont activées qu’après #10 et #12.
- Chargement et soumission en cours uniquement lorsque ces états correspondent à une interaction réelle.

La démonstration doit être explicitement identifiée comme telle. Elle ne devient pas un second produit : on réutilise les mêmes templates lors de l’intégration.

## Dépendances et branches

Les relations natives GitHub font foi pour les blocages. Les références ci-dessous permettent de relire le plan dans le dépôt.

| Issue | Prérequis | Branche existante |
|---|---|---|
| #1 | Aucun | docs/roadmap |
| #3 | Aucun | docs/roadmap |
| #15 | #1, #3 | docs/roadmap |
| #2 | #1 | docs/roadmap |
| #4 | #2, #3 | docs/roadmap |
| #5 | #4, #15 | docs/roadmap |
| #6 | #5 | docs/roadmap |
| #7 | #5 | docs/roadmap |
| #14 | #3 | docs/roadmap |
| #8 | #3, #4 | docs/roadmap |
| #9 | #1, #2, #8 | docs/roadmap |
| #10 | #5, #6, #8, #9, #14 | docs/roadmap |
| #11 | #9 | docs/roadmap |
| #12 | #6, #10, #11, #14 | docs/roadmap |
| #13 | #7, #10, #12, #14, #15 | docs/roadmap |
| #16 | #13, #15 | docs/roadmap |

Les numéros et branches sont conservés pour préserver les liens Development et l’historique. Avant tout développement, actualiser la branche depuis main. Une PR référence son issue avec Closes #numéro.

## Recette finale

#13 vérifie deux parcours d’installation : interface de démonstration sans ODBC et intégration avec Db2/RPG. La seconde exige une preuve de persistance réelle pour l’ajout et la modification, ainsi que le traitement des refus, indisponibilités et erreurs.

Les diagrammes existants ne sont pas à refaire pour le seul changement d’ordre de développement. Leur cohérence avec l’implémentation finale sera vérifiée lors de la recette.
