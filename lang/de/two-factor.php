<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

/*
 * Der zweite Schritt der Anmeldung, die Anmeldung ohne Passwort, die Sicherheitsseite
 * und das Anmeldeprotokoll.
 */
return [
    'module' => 'Zwei-Faktor-Anmeldung',
    'back_to_login' => 'Zurück zur Anmeldung',

    'guards' => [
        'admin' => 'Admin-Bereich',
        'account' => 'Persönliches Konto',
    ],

    'factors' => [
        'totp' => 'Authenticator-App',
        'passkey' => 'Passkey',
        'email' => 'Code per E-Mail',
        'sms' => 'Code per SMS',
    ],

    'methods' => [
        'password' => 'Passwort',
        'code' => 'Code per E-Mail',
        'passkey' => 'Passkey',
        'register' => 'Registrierung',
        'totp' => 'Authenticator-App',
        'email' => 'Code per E-Mail',
        'sms' => 'Code per SMS',
        'recovery' => 'Wiederherstellungscode',
    ],

    'login' => [
        'passkey' => 'Mit einem Passkey anmelden',
        'code' => 'Code per E-Mail erhalten',
    ],

    'challenge' => [
        'title' => 'Anmeldung bestätigen',
        'sent_to' => 'Wir haben einen Code an :destination gesendet',
        'totp_hint' => 'Öffnen Sie Ihre Authenticator-App und geben Sie den sechsstelligen Code ein, den sie für diese Website anzeigt.',
        'passkey_hint' => 'Verwenden Sie den Passkey auf diesem Gerät oder einen Sicherheitsschlüssel: Fingerabdruck, Gesicht oder PIN.',
        'use_passkey' => 'Passkey verwenden',
        'code_label' => [
            'totp' => 'Code aus der App',
            'email' => 'Code aus der E-Mail',
            'sms' => 'Code aus der SMS',
        ],
        'submit' => 'Bestätigen',
        'resend' => 'Code erneut senden',
        'resend_in' => 'Erneut senden in :seconds s',
        'resent' => 'Der Code wurde erneut gesendet.',
        'resend_too_soon' => 'Der Code wurde bereits gesendet. In einer Minute können Sie einen neuen anfordern.',
        'send_failed' => 'Der Code konnte nicht gesendet werden: E-Mail oder SMS sind nicht verfügbar. Versuchen Sie eine andere Möglichkeit oder wenden Sie sich an den Administrator.',
        'code_required' => 'Geben Sie den Code ein.',
        'code_invalid' => 'Der Code ist falsch oder abgelaufen. Prüfen Sie ihn oder fordern Sie einen neuen an.',
        'throttle' => 'Zu viele Versuche. Versuchen Sie es in :seconds s erneut.',
        'expired' => 'Die Anmeldung ist abgelaufen. Melden Sie sich erneut an.',
        'trust' => 'Auf diesem Gerät :days Tag lang nicht nachfragen|Auf diesem Gerät :days Tage lang nicht nachfragen',
        'other_ways' => 'Andere Möglichkeiten zur Bestätigung',
        'use_recovery' => 'Wiederherstellungscode verwenden',
        'recovery_hint' => 'Geben Sie einen der Wiederherstellungscodes ein, die Sie beim Einrichten der Zwei-Faktor-Anmeldung gespeichert haben. Jeder Code funktioniert nur einmal.',
        'recovery_code' => 'Wiederherstellungscode',
        'recovery_invalid' => 'Dieser Wiederherstellungscode ist falsch oder wurde bereits verwendet.',
        'recovery_left' => 'Sie haben sich mit einem Wiederherstellungscode angemeldet. Verbleibende Codes: :count. Erstellen Sie neue auf der Sicherheitsseite.',
        'back_to_factor' => 'Zurück',
        'cancel' => 'Als anderer Benutzer anmelden',
    ],

    'magic' => [
        'title' => 'Mit einem Code anmelden',
        'hint' => 'Geben Sie Ihre E-Mail-Adresse ein, dann senden wir Ihnen einen sechsstelligen Code. Ein Passwort ist nicht nötig.',
        'email' => 'E-Mail',
        'send' => 'Code senden',
        'sent' => 'Falls ein Konto mit dieser Adresse existiert, ist ein Code unterwegs.',
        'check_mail' => 'Falls :email zu einem Konto gehört, haben wir dorthin einen Code gesendet. Er funktioniert nur einmal und wenige Minuten lang.',
        'other_email' => 'Andere E-Mail-Adresse verwenden',
        'email_required' => 'Geben Sie Ihre E-Mail-Adresse ein.',
        'email_invalid' => 'Geben Sie eine gültige E-Mail-Adresse ein.',
        'expired' => 'Der Code ist abgelaufen. Fordern Sie einen neuen an.',
        'too_many' => 'Zu viele falsche Codes. Fordern Sie einen neuen an.',
    ],

    'passkey' => [
        'title' => 'Mit einem Passkey anmelden',
        'hint' => 'Ihr Gerät fragt nach Fingerabdruck, Gesicht oder PIN. Ein Passwort ist nicht nötig.',
        'button' => 'Mit einem Passkey fortfahren',
        'failed' => 'Dieser Passkey hat nicht funktioniert. Versuchen Sie es erneut oder melden Sie sich anders an.',
        'unsupported' => 'Dieser Browser unterstützt keine Passkeys.',
        'cancelled' => 'Die Passkey-Prüfung wurde abgebrochen oder ist abgelaufen.',
    ],

    'security' => [
        'title' => 'Anmeldesicherheit',
        'demo_locked' => 'In der Demo nicht verfügbar. Hier kann niemand eine Möglichkeit zur Bestätigung der Anmeldung hinzufügen, entfernen oder zurücksetzen, damit der nächste Besucher sich immer anmelden kann.',
        'required' => 'Für Ihr Konto ist ein zweiter Schritt erforderlich: Behalten Sie mindestens eine Möglichkeit, die Anmeldung zu bestätigen.',
        'optional' => 'Fügen Sie einen zweiten Schritt hinzu, um Ihr Konto auch dann zu schützen, wenn jemand Ihr Passwort erfährt.',
        'must_enrol' => 'Richten Sie einen zweiten Schritt ein, bevor Sie fortfahren: Er ist für Ihr Konto erforderlich.',
        'factors' => 'Ihre Möglichkeiten zur Bestätigung',
        'none' => 'Noch nichts eingerichtet.',
        'none_short' => 'keine',
        'added_at' => 'hinzugefügt am :date',
        'used_at' => 'zuletzt verwendet am :date',
        'remove' => 'Entfernen',
        'removed' => 'Entfernt.',
        'added' => 'Hinzugefügt. Er wird bei der Anmeldung abgefragt.',
        'last_factor' => 'Das ist Ihre letzte Möglichkeit, die Anmeldung zu bestätigen, und mindestens eine ist erforderlich. Fügen Sie zuerst eine weitere hinzu.',
        'name' => 'Name (optional)',
        'name_totp' => 'Smartphone',
        'name_passkey' => 'Laptop',
        'confirm' => 'Bestätigen',
        'totp_hint' => 'Google Authenticator, Microsoft Authenticator, 1Password, Aegis oder eine andere App, die sechsstellige Codes anzeigt.',
        'totp_add' => 'App einrichten',
        'totp_another' => 'Weitere App hinzufügen',
        'totp_manual' => 'Scannen Sie den QR-Code mit der App oder geben Sie diesen Schlüssel ein:',
        'qr' => 'QR-Code für die Authenticator-App',
        'passkey_hint' => 'Fingerabdruck, Gesicht oder PIN auf diesem Gerät oder ein Sicherheitsschlüssel statt der Eingabe eines Codes.',
        'passkey_hint_passwordless' => 'Fingerabdruck, Gesicht oder PIN auf diesem Gerät oder ein Sicherheitsschlüssel. Mit einem Passkey melden Sie sich auch ohne Passwort an.',
        'passkey_add' => 'Passkey hinzufügen',
        'passkey_failed' => 'Der Passkey wurde nicht gespeichert. Versuchen Sie es erneut.',
        'phone' => 'Telefonnummer mit Ländervorwahl',
        'phone_invalid' => 'Geben Sie die Telefonnummer mit Ländervorwahl ein, zum Beispiel +1 555 0100.',
        'sms_add' => 'Code senden',
        'sms_sent' => 'Code an :phone gesendet',
        'email_hint' => 'Ein Code, der bei jeder Anmeldung an :email gesendet wird.',
        'email_add' => 'Codes per E-Mail verwenden',
        'recovery' => 'Wiederherstellungscodes',
        'recovery_left' => 'Noch ein unbenutzter Code übrig.|Noch :count unbenutzte Codes übrig.',
        'recovery_regenerate' => 'Neue Codes erstellen',
        'recovery_new' => 'Ihre Wiederherstellungscodes',
        'recovery_once' => 'Speichern Sie sie jetzt an einem sicheren Ort: Sie werden nur einmal angezeigt. Jeder Code lässt Sie einmal herein, falls Sie Ihr Smartphone oder Ihren Schlüssel verlieren. Alte Codes funktionieren nicht mehr.',
        'devices' => 'Vertrauenswürdige Geräte',
        'devices_none' => 'Kein Gerät überspringt den zweiten Schritt.',
        'devices_revoke' => 'Alle Geräte vergessen',
        'devices_revoked' => 'Alle Geräte werden wieder nach dem zweiten Schritt gefragt.',
        'until' => 'bis :date',
        'events' => 'Letzte Aktivität',
    ],

    'events' => [
        'signed_in' => 'Angemeldet',
        'challenged' => 'Zweiter Schritt abgefragt',
        'failed' => 'Falsche Antwort',
        'locked' => 'Vorübergehend gesperrt',
        'trusted_device' => 'Vertrauenswürdiges Gerät, Schritt übersprungen',
        'code_sent' => 'Code gesendet',
        'factor_added' => 'Möglichkeit zur Bestätigung hinzugefügt',
        'factor_removed' => 'Möglichkeit zur Bestätigung entfernt',
        'recovery_used' => 'Wiederherstellungscode verwendet',
        'recovery_generated' => 'Neue Wiederherstellungscodes',
        'device_trusted' => 'Gerät als vertrauenswürdig markiert',
        'device_revoked' => 'Geräte vergessen',
        'reset' => 'Von einem Administrator zurückgesetzt',
        'social_linked' => 'Soziales Konto verknüpft',
        'social_unlinked' => 'Soziales Konto getrennt',
        'social_refused' => 'Anmeldung über soziales Konto abgelehnt',
    ],

    'log' => [
        'when' => 'Wann',
        'who' => 'Wer',
        'what' => 'Ereignis',
        'how' => 'Methode',
        'where' => 'IP-Adresse',
        'guard' => 'Wo',
        'all' => 'Alle',
        'filter' => 'Anzeigen',
        'empty' => 'Noch nichts vorhanden.',
        'by_admin' => 'Administrator Nr. :id',
        'pages' => 'Seiten',
        'newer' => 'Neuer',
        'older' => 'Älter',
    ],

    'manage' => [
        'hint' => 'Wenn jemand sein Smartphone oder seinen Schlüssel verloren hat, setzen Sie den zweiten Schritt zurück: Die Person meldet sich mit dem Passwort an und richtet ihn neu ein.',
        'search' => 'E-Mail',
        'email' => 'E-Mail',
        'reset' => 'Zurücksetzen',
        'confirm' => 'Alle Möglichkeiten zur Bestätigung, die Wiederherstellungscodes und die vertrauenswürdigen Geräte von :email entfernen?',
        'reset_done' => 'Zurückgesetzt. Die Person richtet den zweiten Schritt neu ein.',
    ],

    'menu' => [
        'title' => 'Anmeldung',
        'security' => 'Meine Anmeldesicherheit',
        'log' => 'Anmeldeprotokoll',
        'people' => 'Zwei-Faktor-Zurücksetzungen',
    ],

    'settings' => [
        'description' => 'Ein leeres Feld behält den Wert aus config/atrium-2fa.php.',
        'from_config' => 'Leer: wie in der Konfigurationsdatei.',
        'required' => ':who: wer einen zweiten Schritt durchlaufen muss',
        'modes' => [
            'all' => 'Alle',
            'roles' => 'Superadmins und die gewählten Rollen',
            'optional' => 'Nur wer ihn eingerichtet hat',
        ],
        'roles' => ':who: Rollen, die ihn durchlaufen müssen',
        'factors' => ':who: erlaubte Möglichkeiten zur Bestätigung',
        'passwordless' => ':who: Anmeldung nur mit einem Passkey',
        'magic_code' => ':who: Anmeldung mit einem Code per E-Mail',
        'trusted_days' => ':who: Tage, an denen ein vertrauenswürdiges Gerät den Schritt überspringt',
        'trusted_days_hint' => '0 schaltet „Diesem Gerät vertrauen“ aus.',
        'yes' => 'Ja',
        'no' => 'Nein',
    ],

    'sms' => ':code ist Ihr Anmeldecode für :site. Geben Sie ihn nicht weiter.',

    'mail' => [
        'subject' => ':code ist Ihr Anmeldecode für :site',
        'title' => [
            'two_factor' => 'Bestätigungscode für die Anmeldung',
            'magic' => 'Anmeldecode',
        ],
        'heading' => [
            'two_factor' => 'Anmeldung bei :site bestätigen',
            'magic' => 'Anmeldung bei :site',
        ],
        'code_label' => 'Ihr Code:',
        'valid_for' => 'Der Code ist :count Minute gültig.|Der Code ist :count Minuten gültig.',
        'not_you' => 'Falls Sie nicht versucht haben, sich anzumelden, geben Sie diesen Code an niemanden weiter. Falls er als zweiter Schritt kam, kennt jemand Ihr Passwort: Ändern Sie es.',
    ],
];
