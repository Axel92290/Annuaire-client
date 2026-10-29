<?php

/**
 * Emplacement réservé au futur dépôt de lecture des clients dans Db2.
 *
 * Aucune classe ni méthode n'est encore implémentée dans ce fichier.
 * Actuellement, ClientController charge directement fixtures/clients.php.
 * La connexion différée est disponible dans Infrastructure/Db2Connection.php,
 * mais elle n'est pas encore utilisée pour alimenter les écrans.
 *
 * Lors du raccordement : injecter la connexion, préparer les paramètres des
 * requêtes de lecture et convertir les lignes Db2 en objets Models/Client.
 * Les écritures métier seront confiées aux programmes RPG, pas à ce dépôt.
 */
