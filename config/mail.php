<?php

/**
 * FICHIER OBSOLÈTE
 *
 * Ce fichier n'est plus utilisé.
 * Les paramètres email SMTP sont maintenant dans la base de données.
 *
 * Pour configurer l'email :
 * 1. Se connecter en tant qu'administrateur
 * 2. Aller sur /settings
 * 3. Cliquer sur l'onglet "Email (SMTP)"
 * 4. Remplir les champs (host, port, username, password, etc.)
 * 5. Enregistrer
 *
 * Ce fichier est conservé uniquement pour éviter les erreurs
 * avec d'anciens scripts de test. Il peut être supprimé.
 */

return [
    'smtp' => [
        'host' => '',
        'port' => 587,
        'username' => '',
        'password' => '',
        'encryption' => 'tls',
        'from' => [
            'email' => '',
            'name' => 'Agora'
        ]
    ],

    'templates' => [
        'validation' => 'emails/validation.twig',
        'relance' => 'emails/relance.twig',
        'changement_statut' => 'emails/changement_statut.twig',
        'deadline' => 'emails/deadline.twig',
    ]
];
