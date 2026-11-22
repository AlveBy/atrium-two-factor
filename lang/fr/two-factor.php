<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

/*
 * La seconde étape de connexion, la connexion sans mot de passe, la page de sécurité
 * et le journal des connexions.
 */
return [
    'module' => 'Connexion en deux étapes',
    'back_to_login' => 'Retour à la connexion',

    'guards' => [
        'admin' => 'Administration',
        'account' => 'Compte personnel',
    ],

    'factors' => [
        'totp' => 'Application d\'authentification',
        'passkey' => 'Clé d\'accès',
        'email' => 'Code par e-mail',
        'sms' => 'Code par SMS',
    ],

    'methods' => [
        'password' => 'Mot de passe',
        'code' => 'Code par e-mail',
        'passkey' => 'Clé d\'accès',
        'register' => 'Inscription',
        'totp' => 'Application d\'authentification',
        'email' => 'Code par e-mail',
        'sms' => 'Code par SMS',
        'recovery' => 'Code de récupération',
    ],

    'login' => [
        'passkey' => 'Se connecter avec une clé d\'accès',
        'code' => 'Recevoir un code par e-mail',
    ],

    'challenge' => [
        'title' => 'Confirmer la connexion',
        'sent_to' => 'Nous avons envoyé un code à :destination',
        'totp_hint' => 'Ouvrez votre application d\'authentification et saisissez le code à six chiffres qu\'elle affiche pour ce site.',
        'passkey_hint' => 'Utilisez la clé d\'accès de cet appareil ou une clé de sécurité : empreinte digitale, visage ou code PIN.',
        'use_passkey' => 'Utiliser une clé d\'accès',
        'code_label' => [
            'totp' => 'Code de l\'application',
            'email' => 'Code reçu par e-mail',
            'sms' => 'Code reçu par SMS',
        ],
        'submit' => 'Confirmer',
        'resend' => 'Renvoyer le code',
        'resend_in' => 'Renvoyer dans :seconds s',
        'resent' => 'Le code a été renvoyé.',
        'resend_too_soon' => 'Le code a déjà été envoyé. Vous pourrez en demander un nouveau dans une minute.',
        'send_failed' => 'Impossible d\'envoyer le code : la messagerie ou les SMS sont indisponibles. Essayez un autre moyen ou contactez l\'administrateur.',
        'code_required' => 'Saisissez le code.',
        'code_invalid' => 'Le code est incorrect ou a expiré. Vérifiez-le ou demandez-en un nouveau.',
        'throttle' => 'Trop de tentatives. Réessayez dans :seconds s.',
        'expired' => 'La connexion a expiré. Reconnectez-vous.',
        'trust' => 'Ne pas redemander sur cet appareil pendant :days jour|Ne pas redemander sur cet appareil pendant :days jours',
        'other_ways' => 'Autres moyens de confirmer',
        'use_recovery' => 'Utiliser un code de récupération',
        'recovery_hint' => 'Saisissez l\'un des codes de récupération enregistrés lors de la configuration de la connexion en deux étapes. Chaque code ne fonctionne qu\'une fois.',
        'recovery_code' => 'Code de récupération',
        'recovery_invalid' => 'Ce code de récupération est incorrect ou a déjà été utilisé.',
        'recovery_left' => 'Vous vous êtes connecté avec un code de récupération. Codes restants : :count. Créez-en de nouveaux sur la page de sécurité.',
        'back_to_factor' => 'Retour',
        'cancel' => 'Se connecter avec un autre compte',
    ],

    'magic' => [
        'title' => 'Se connecter avec un code',
        'hint' => 'Saisissez votre adresse e-mail et nous vous enverrons un code à six chiffres. Aucun mot de passe n\'est nécessaire.',
        'email' => 'E-mail',
        'send' => 'Envoyer le code',
        'sent' => 'Si un compte existe avec cette adresse, un code est en route.',
        'check_mail' => 'Si :email correspond à un compte, nous y avons envoyé un code. Il ne fonctionne qu\'une fois et pendant quelques minutes.',
        'other_email' => 'Utiliser une autre adresse e-mail',
        'email_required' => 'Saisissez votre adresse e-mail.',
        'email_invalid' => 'Saisissez une adresse e-mail valide.',
        'expired' => 'Le code a expiré. Demandez-en un nouveau.',
        'too_many' => 'Trop de codes incorrects. Demandez-en un nouveau.',
    ],

    'passkey' => [
        'title' => 'Se connecter avec une clé d\'accès',
        'hint' => 'Votre appareil vous demandera votre empreinte digitale, votre visage ou votre code PIN. Aucun mot de passe n\'est nécessaire.',
        'button' => 'Continuer avec une clé d\'accès',
        'failed' => 'Cette clé d\'accès n\'a pas fonctionné. Réessayez ou connectez-vous autrement.',
        'unsupported' => 'Ce navigateur ne prend pas en charge les clés d\'accès.',
        'cancelled' => 'La vérification de la clé d\'accès a été annulée ou a expiré.',
    ],

    'security' => [
        'title' => 'Sécurité de la connexion',
        'demo_locked' => 'Indisponible dans la démo. Personne ne peut y ajouter, retirer ou réinitialiser un moyen de confirmer la connexion, afin que le prochain visiteur puisse toujours se connecter.',
        'required' => 'Une seconde étape est requise pour votre compte : gardez au moins un moyen de confirmer la connexion.',
        'optional' => 'Ajoutez une seconde étape pour protéger votre compte même si quelqu\'un découvre votre mot de passe.',
        'must_enrol' => 'Configurez une seconde étape avant de continuer : elle est requise pour votre compte.',
        'factors' => 'Vos moyens de confirmation',
        'none' => 'Rien n\'est encore configuré.',
        'none_short' => 'aucun',
        'added_at' => 'ajouté le :date',
        'used_at' => 'dernière utilisation le :date',
        'remove' => 'Supprimer',
        'removed' => 'Supprimé.',
        'added' => 'Ajouté. Il sera demandé lors de la connexion.',
        'last_factor' => 'C\'est votre dernier moyen de confirmer la connexion, et il en faut au moins un. Ajoutez-en d\'abord un autre.',
        'name' => 'Nom (facultatif)',
        'name_totp' => 'Téléphone',
        'name_passkey' => 'Ordinateur portable',
        'confirm' => 'Confirmer',
        'totp_hint' => 'Google Authenticator, Microsoft Authenticator, 1Password, Aegis ou toute autre application qui affiche des codes à six chiffres.',
        'totp_add' => 'Configurer une application',
        'totp_another' => 'Ajouter une autre application',
        'totp_manual' => 'Scannez le code QR avec l\'application ou saisissez cette clé :',
        'qr' => 'Code QR pour l\'application d\'authentification',
        'passkey_hint' => 'Une empreinte digitale, un visage ou un code PIN sur cet appareil, ou une clé de sécurité, à la place d\'un code à saisir.',
        'passkey_hint_passwordless' => 'Une empreinte digitale, un visage ou un code PIN sur cet appareil, ou une clé de sécurité. Une clé d\'accès vous connecte aussi sans mot de passe.',
        'passkey_add' => 'Ajouter une clé d\'accès',
        'passkey_failed' => 'La clé d\'accès n\'a pas été enregistrée. Réessayez.',
        'phone' => 'Numéro de téléphone avec l\'indicatif du pays',
        'phone_invalid' => 'Saisissez le numéro de téléphone avec l\'indicatif du pays, par exemple +1 555 0100.',
        'sms_add' => 'Envoyer un code',
        'sms_sent' => 'Code envoyé au :phone',
        'email_hint' => 'Un code envoyé à :email à chaque connexion.',
        'email_add' => 'Utiliser des codes par e-mail',
        'recovery' => 'Codes de récupération',
        'recovery_left' => 'Il reste un code inutilisé.|Il reste :count codes inutilisés.',
        'recovery_regenerate' => 'Créer de nouveaux codes',
        'recovery_new' => 'Vos codes de récupération',
        'recovery_once' => 'Enregistrez-les dès maintenant dans un endroit sûr : ils ne sont affichés qu\'une seule fois. Chaque code vous permet d\'entrer une fois si vous perdez votre téléphone ou votre clé. Les anciens codes ne fonctionnent plus.',
        'devices' => 'Appareils de confiance',
        'devices_none' => 'Aucun appareil ne passe la seconde étape.',
        'devices_revoke' => 'Oublier tous les appareils',
        'devices_revoked' => 'La seconde étape sera de nouveau demandée sur tous les appareils.',
        'until' => 'jusqu\'au :date',
        'events' => 'Activité récente',
    ],

    'events' => [
        'signed_in' => 'Connexion réussie',
        'challenged' => 'Seconde étape demandée',
        'failed' => 'Mauvaise réponse',
        'locked' => 'Bloqué temporairement',
        'trusted_device' => 'Appareil de confiance, étape ignorée',
        'code_sent' => 'Code envoyé',
        'factor_added' => 'Moyen de confirmation ajouté',
        'factor_removed' => 'Moyen de confirmation supprimé',
        'recovery_used' => 'Code de récupération utilisé',
        'recovery_generated' => 'Nouveaux codes de récupération',
        'device_trusted' => 'Appareil de confiance ajouté',
        'device_revoked' => 'Appareils oubliés',
        'reset' => 'Réinitialisé par un administrateur',
        'social_linked' => 'Compte social associé',
        'social_unlinked' => 'Compte social dissocié',
        'social_refused' => 'Connexion via un compte social refusée',
    ],

    'log' => [
        'when' => 'Quand',
        'who' => 'Qui',
        'what' => 'Événement',
        'how' => 'Méthode',
        'where' => 'Adresse IP',
        'guard' => 'Où',
        'all' => 'Tous',
        'filter' => 'Afficher',
        'empty' => 'Rien pour le moment.',
        'by_admin' => 'administrateur n° :id',
        'pages' => 'Pages',
        'newer' => 'Plus récents',
        'older' => 'Plus anciens',
    ],

    'manage' => [
        'hint' => 'Si quelqu\'un a perdu son téléphone ou sa clé, réinitialisez sa seconde étape : la personne se connecte avec son mot de passe et la configure de nouveau.',
        'search' => 'E-mail',
        'email' => 'E-mail',
        'reset' => 'Réinitialiser',
        'confirm' => 'Supprimer tous les moyens de confirmation, les codes de récupération et les appareils de confiance de :email ?',
        'reset_done' => 'Réinitialisé. La personne configurera de nouveau la seconde étape.',
    ],

    'menu' => [
        'title' => 'Connexion',
        'security' => 'La sécurité de ma connexion',
        'log' => 'Journal des connexions',
        'people' => 'Réinitialisations en deux étapes',
    ],

    'settings' => [
        'description' => 'Un champ vide conserve la valeur de config/atrium-2fa.php.',
        'from_config' => 'Vide : comme dans le fichier de configuration.',
        'required' => ':who : qui doit passer une seconde étape',
        'modes' => [
            'all' => 'Tout le monde',
            'roles' => 'Les super-administrateurs et les rôles choisis',
            'optional' => 'Seulement ceux qui l\'ont configurée',
        ],
        'roles' => ':who : rôles qui doivent la passer',
        'factors' => ':who : moyens de confirmation autorisés',
        'passwordless' => ':who : connexion avec une clé d\'accès seule',
        'magic_code' => ':who : connexion avec un code par e-mail',
        'trusted_days' => ':who : jours pendant lesquels un appareil de confiance passe l\'étape',
        'trusted_days_hint' => '0 désactive « faire confiance à cet appareil ».',
        'yes' => 'Oui',
        'no' => 'Non',
    ],

    'sms' => ':code est votre code de connexion à :site. Ne le communiquez à personne.',

    'mail' => [
        'subject' => ':code est votre code de connexion à :site',
        'title' => [
            'two_factor' => 'Code de confirmation de connexion',
            'magic' => 'Code de connexion',
        ],
        'heading' => [
            'two_factor' => 'Confirmation de la connexion à :site',
            'magic' => 'Connexion à :site',
        ],
        'code_label' => 'Votre code :',
        'valid_for' => 'Le code est valable :count minute.|Le code est valable :count minutes.',
        'not_you' => 'Si vous n\'avez pas essayé de vous connecter, ne communiquez ce code à personne. S\'il est arrivé comme seconde étape, quelqu\'un connaît votre mot de passe : changez-le.',
    ],
];
