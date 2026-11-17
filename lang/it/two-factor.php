<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

/*
 * Il secondo passaggio dell'accesso, l'accesso senza password, la pagina di sicurezza
 * e il registro degli accessi.
 */
return [
    'module' => 'Accesso in due passaggi',
    'back_to_login' => 'Torna all\'accesso',
    'back_to_panel' => 'Torna al pannello',

    'guards' => [
        'admin' => 'Pannello di amministrazione',
        'account' => 'Account personale',
    ],

    'factors' => [
        'totp' => 'App di autenticazione',
        'passkey' => 'Passkey',
        'email' => 'Codice via email',
        'sms' => 'Codice via SMS',
    ],

    'methods' => [
        'password' => 'Password',
        'code' => 'Codice via email',
        'passkey' => 'Passkey',
        'register' => 'Registrazione',
        'totp' => 'App di autenticazione',
        'email' => 'Codice via email',
        'sms' => 'Codice via SMS',
        'recovery' => 'Codice di recupero',
    ],

    'login' => [
        'passkey' => 'Accedi con una passkey',
        'code' => 'Ricevi un codice via email',
    ],

    'challenge' => [
        'title' => 'Conferma l\'accesso',
        'sent_to' => 'Abbiamo inviato un codice a :destination',
        'totp_hint' => 'Apri la tua app di autenticazione e inserisci il codice di sei cifre che mostra per questo sito.',
        'passkey_hint' => 'Usa la passkey di questo dispositivo o una chiave di sicurezza: impronta digitale, volto o PIN.',
        'use_passkey' => 'Usa una passkey',
        'code_label' => [
            'totp' => 'Codice dell\'app',
            'email' => 'Codice ricevuto via email',
            'sms' => 'Codice ricevuto via SMS',
        ],
        'submit' => 'Conferma',
        'resend' => 'Invia di nuovo il codice',
        'resend_in' => 'Invia di nuovo tra :seconds s',
        'resent' => 'Il codice è stato inviato di nuovo.',
        'resend_too_soon' => 'Il codice è già stato inviato. Puoi richiederne uno nuovo tra un minuto.',
        'send_failed' => 'Impossibile inviare il codice: email o SMS non sono disponibili. Prova un altro metodo o contatta l\'amministratore.',
        'code_required' => 'Inserisci il codice.',
        'code_invalid' => 'Il codice non è corretto o è scaduto. Controllalo o richiedine uno nuovo.',
        'throttle' => 'Troppi tentativi. Riprova tra :seconds s.',
        'expired' => 'L\'accesso è scaduto. Accedi di nuovo.',
        'trust' => 'Non chiedere su questo dispositivo per :days giorno|Non chiedere su questo dispositivo per :days giorni',
        'other_ways' => 'Altri modi per confermare',
        'use_recovery' => 'Usa un codice di recupero',
        'recovery_hint' => 'Inserisci uno dei codici di recupero che hai salvato quando hai configurato l\'accesso in due passaggi. Ogni codice funziona una sola volta.',
        'recovery_code' => 'Codice di recupero',
        'recovery_invalid' => 'Questo codice di recupero non è corretto o è già stato usato.',
        'recovery_left' => 'Hai effettuato l\'accesso con un codice di recupero. Codici rimasti: :count. Creane di nuovi nella pagina di sicurezza.',
        'back_to_factor' => 'Indietro',
        'cancel' => 'Accedi con un altro utente',
    ],

    'magic' => [
        'title' => 'Accedi con un codice',
        'hint' => 'Inserisci la tua email e ti invieremo un codice di sei cifre. Non serve la password.',
        'email' => 'Email',
        'send' => 'Invia il codice',
        'sent' => 'Se esiste un account con questo indirizzo, un codice è in arrivo.',
        'check_mail' => 'Se :email appartiene a un account, abbiamo inviato lì un codice. Funziona una sola volta e per pochi minuti.',
        'other_email' => 'Usa un\'altra email',
        'email_required' => 'Inserisci la tua email.',
        'email_invalid' => 'Inserisci un\'email valida.',
        'expired' => 'Il codice è scaduto. Richiedine uno nuovo.',
        'too_many' => 'Troppi codici errati. Richiedine uno nuovo.',
    ],

    'passkey' => [
        'title' => 'Accedi con una passkey',
        'hint' => 'Il tuo dispositivo chiederà l\'impronta digitale, il volto o il PIN. Non serve la password.',
        'button' => 'Continua con una passkey',
        'failed' => 'Questa passkey non ha funzionato. Riprova o accedi in un altro modo.',
        'unsupported' => 'Questo browser non supporta le passkey.',
        'cancelled' => 'La verifica della passkey è stata annullata o è scaduta.',
    ],

    'security' => [
        'title' => 'Sicurezza dell\'accesso',
        'required' => 'Per il tuo account è richiesto un secondo passaggio: conserva almeno un modo per confermare l\'accesso.',
        'optional' => 'Aggiungi un secondo passaggio per proteggere il tuo account anche se qualcuno scopre la tua password.',
        'must_enrol' => 'Configura un secondo passaggio prima di continuare: è richiesto per il tuo account.',
        'factors' => 'I tuoi modi per confermare',
        'none' => 'Non è ancora stato configurato nulla.',
        'none_short' => 'nessuno',
        'added_at' => 'aggiunto il :date',
        'used_at' => 'ultimo utilizzo il :date',
        'remove' => 'Rimuovi',
        'removed' => 'Rimosso.',
        'added' => 'Aggiunto. Verrà richiesto quando accedi.',
        'last_factor' => 'È l\'ultimo modo per confermare l\'accesso e ne serve almeno uno. Aggiungine prima un altro.',
        'name' => 'Nome (facoltativo)',
        'name_totp' => 'Telefono',
        'name_passkey' => 'Portatile',
        'confirm' => 'Conferma',
        'totp_hint' => 'Google Authenticator, Microsoft Authenticator, 1Password, Aegis o qualsiasi altra app che mostri codici di sei cifre.',
        'totp_add' => 'Configura un\'app',
        'totp_another' => 'Aggiungi un\'altra app',
        'totp_manual' => 'Scansiona il codice QR con l\'app oppure digita questa chiave:',
        'qr' => 'Codice QR per l\'app di autenticazione',
        'passkey_hint' => 'Un\'impronta digitale, il volto o il PIN su questo dispositivo, oppure una chiave di sicurezza, al posto di un codice da digitare.',
        'passkey_hint_passwordless' => 'Un\'impronta digitale, il volto o il PIN su questo dispositivo, oppure una chiave di sicurezza. Una passkey ti permette anche di accedere senza password.',
        'passkey_add' => 'Aggiungi una passkey',
        'passkey_failed' => 'La passkey non è stata salvata. Riprova.',
        'phone' => 'Numero di telefono con il prefisso internazionale',
        'phone_invalid' => 'Inserisci il numero di telefono con il prefisso internazionale, ad esempio +1 555 0100.',
        'sms_add' => 'Invia un codice',
        'sms_sent' => 'Codice inviato al :phone',
        'email_hint' => 'Un codice inviato a :email a ogni accesso.',
        'email_add' => 'Usa i codici via email',
        'recovery' => 'Codici di recupero',
        'recovery_left' => 'Resta un codice non usato.|Restano :count codici non usati.',
        'recovery_regenerate' => 'Crea nuovi codici',
        'recovery_new' => 'I tuoi codici di recupero',
        'recovery_once' => 'Salvali subito in un posto sicuro: vengono mostrati una sola volta. Ogni codice ti fa entrare una volta se perdi il telefono o la chiave. I vecchi codici non funzionano più.',
        'devices' => 'Dispositivi attendibili',
        'devices_none' => 'Nessun dispositivo salta il secondo passaggio.',
        'devices_revoke' => 'Dimentica tutti i dispositivi',
        'devices_revoked' => 'A tutti i dispositivi verrà chiesto di nuovo il secondo passaggio.',
        'until' => 'fino al :date',
        'events' => 'Attività recente',
    ],

    'events' => [
        'signed_in' => 'Accesso effettuato',
        'challenged' => 'Secondo passaggio richiesto',
        'failed' => 'Risposta errata',
        'locked' => 'Bloccato per un po\'',
        'trusted_device' => 'Dispositivo attendibile, passaggio saltato',
        'code_sent' => 'Codice inviato',
        'factor_added' => 'Modo di conferma aggiunto',
        'factor_removed' => 'Modo di conferma rimosso',
        'recovery_used' => 'Codice di recupero usato',
        'recovery_generated' => 'Nuovi codici di recupero',
        'device_trusted' => 'Dispositivo reso attendibile',
        'device_revoked' => 'Dispositivi dimenticati',
        'reset' => 'Reimpostato da un amministratore',
        'social_linked' => 'Account social collegato',
        'social_unlinked' => 'Account social scollegato',
        'social_refused' => 'Accesso con account social rifiutato',
    ],

    'log' => [
        'when' => 'Quando',
        'who' => 'Chi',
        'what' => 'Evento',
        'how' => 'Metodo',
        'where' => 'Indirizzo IP',
        'guard' => 'Dove',
        'all' => 'Tutti',
        'filter' => 'Mostra',
        'empty' => 'Ancora niente.',
        'by_admin' => 'amministratore n. :id',
        'pages' => 'Pagine',
        'newer' => 'Più recenti',
        'older' => 'Meno recenti',
    ],

    'manage' => [
        'hint' => 'Se qualcuno ha perso il telefono o la chiave, reimposta il suo secondo passaggio: la persona accede con la password e lo configura di nuovo.',
        'search' => 'Email',
        'email' => 'Email',
        'reset' => 'Reimposta',
        'confirm' => 'Rimuovere tutti i modi per confermare l\'accesso, i codici di recupero e i dispositivi attendibili di :email?',
        'reset_done' => 'Reimpostato. La persona configurerà di nuovo il secondo passaggio.',
    ],

    'menu' => [
        'title' => 'Accesso',
        'security' => 'Sicurezza del mio accesso',
        'log' => 'Registro degli accessi',
        'people' => 'Reimpostazioni in due passaggi',
    ],

    'settings' => [
        'description' => 'Un campo vuoto mantiene il valore di config/atrium-2fa.php.',
        'from_config' => 'Vuoto: come nel file di configurazione.',
        'required' => ':who: chi deve superare un secondo passaggio',
        'modes' => [
            'all' => 'Tutti',
            'roles' => 'Superamministratori e i ruoli scelti',
            'optional' => 'Solo chi lo ha configurato',
        ],
        'roles' => ':who: ruoli che devono superarlo',
        'factors' => ':who: modi di conferma consentiti',
        'passwordless' => ':who: accesso con la sola passkey',
        'magic_code' => ':who: accesso con un codice via email',
        'trusted_days' => ':who: giorni in cui un dispositivo attendibile salta il passaggio',
        'trusted_days_hint' => '0 disattiva «considera attendibile questo dispositivo».',
        'yes' => 'Sì',
        'no' => 'No',
    ],

    'sms' => ':code è il tuo codice di accesso a :site. Non condividerlo.',

    'mail' => [
        'subject' => ':code è il tuo codice di accesso a :site',
        'title' => [
            'two_factor' => 'Codice di conferma dell\'accesso',
            'magic' => 'Codice di accesso',
        ],
        'heading' => [
            'two_factor' => 'Conferma dell\'accesso a :site',
            'magic' => 'Accesso a :site',
        ],
        'code_label' => 'Il tuo codice:',
        'valid_for' => 'Il codice è valido per :count minuto.|Il codice è valido per :count minuti.',
        'not_you' => 'Se non hai tentato di accedere, non condividere questo codice con nessuno. Se è arrivato come secondo passaggio, qualcuno conosce la tua password: cambiala.',
    ],
];
