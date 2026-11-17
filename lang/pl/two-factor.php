<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

/*
 * Drugi krok logowania, logowanie bez hasła, strona bezpieczeństwa
 * i dziennik logowań.
 */
return [
    'module' => 'Logowanie dwuetapowe',
    'back_to_login' => 'Wróć do logowania',
    'back_to_panel' => 'Wróć do panelu',

    'guards' => [
        'admin' => 'Panel administracyjny',
        'account' => 'Konto osobiste',
    ],

    'factors' => [
        'totp' => 'Aplikacja uwierzytelniająca',
        'passkey' => 'Klucz dostępu',
        'email' => 'Kod w e-mailu',
        'sms' => 'Kod w SMS-ie',
    ],

    'methods' => [
        'password' => 'Hasło',
        'code' => 'Kod w e-mailu',
        'passkey' => 'Klucz dostępu',
        'register' => 'Rejestracja',
        'totp' => 'Aplikacja uwierzytelniająca',
        'email' => 'Kod w e-mailu',
        'sms' => 'Kod w SMS-ie',
        'recovery' => 'Kod odzyskiwania',
    ],

    'login' => [
        'passkey' => 'Zaloguj się kluczem dostępu',
        'code' => 'Odbierz kod e-mailem',
    ],

    'challenge' => [
        'title' => 'Potwierdź logowanie',
        'sent_to' => 'Wysłaliśmy kod na :destination',
        'totp_hint' => 'Otwórz aplikację uwierzytelniającą i wpisz sześciocyfrowy kod, który pokazuje dla tej witryny.',
        'passkey_hint' => 'Użyj klucza dostępu na tym urządzeniu lub klucza bezpieczeństwa: odcisku palca, twarzy albo PIN-u.',
        'use_passkey' => 'Użyj klucza dostępu',
        'code_label' => [
            'totp' => 'Kod z aplikacji',
            'email' => 'Kod z e-maila',
            'sms' => 'Kod z SMS-a',
        ],
        'submit' => 'Potwierdź',
        'resend' => 'Wyślij kod ponownie',
        'resend_in' => 'Wyślij ponownie za :seconds s',
        'resent' => 'Kod został wysłany ponownie.',
        'resend_too_soon' => 'Kod został już wysłany. Nowy możesz zamówić za minutę.',
        'send_failed' => 'Nie udało się wysłać kodu: poczta lub SMS są niedostępne. Spróbuj innego sposobu albo skontaktuj się z administratorem.',
        'code_required' => 'Wpisz kod.',
        'code_invalid' => 'Kod jest nieprawidłowy lub wygasł. Sprawdź go albo poproś o nowy.',
        'throttle' => 'Zbyt wiele prób. Spróbuj ponownie za :seconds s.',
        'expired' => 'Logowanie wygasło. Zaloguj się ponownie.',
        'trust' => 'Nie pytaj na tym urządzeniu przez :days dzień|Nie pytaj na tym urządzeniu przez :days dni|Nie pytaj na tym urządzeniu przez :days dni',
        'other_ways' => 'Inne sposoby potwierdzenia',
        'use_recovery' => 'Użyj kodu odzyskiwania',
        'recovery_hint' => 'Wpisz jeden z kodów odzyskiwania zapisanych podczas konfiguracji logowania dwuetapowego. Każdy kod działa tylko raz.',
        'recovery_code' => 'Kod odzyskiwania',
        'recovery_invalid' => 'Ten kod odzyskiwania jest nieprawidłowy lub został już użyty.',
        'recovery_left' => 'Zalogowano kodem odzyskiwania. Pozostało kodów: :count. Utwórz nowe na stronie bezpieczeństwa.',
        'back_to_factor' => 'Wstecz',
        'cancel' => 'Zaloguj się jako inny użytkownik',
    ],

    'magic' => [
        'title' => 'Zaloguj się kodem',
        'hint' => 'Wpisz swój adres e-mail, a wyślemy Ci sześciocyfrowy kod. Hasło nie jest potrzebne.',
        'email' => 'E-mail',
        'send' => 'Wyślij kod',
        'sent' => 'Jeśli konto z tym adresem istnieje, kod jest w drodze.',
        'check_mail' => 'Jeśli :email należy do konta, wysłaliśmy tam kod. Działa tylko raz i przez kilka minut.',
        'other_email' => 'Użyj innego adresu e-mail',
        'email_required' => 'Wpisz swój adres e-mail.',
        'email_invalid' => 'Wpisz prawidłowy adres e-mail.',
        'expired' => 'Kod wygasł. Poproś o nowy.',
        'too_many' => 'Zbyt wiele błędnych kodów. Poproś o nowy.',
    ],

    'passkey' => [
        'title' => 'Zaloguj się kluczem dostępu',
        'hint' => 'Urządzenie poprosi o odcisk palca, twarz lub PIN. Hasło nie jest potrzebne.',
        'button' => 'Kontynuuj z kluczem dostępu',
        'failed' => 'Ten klucz dostępu nie zadziałał. Spróbuj ponownie lub zaloguj się inaczej.',
        'unsupported' => 'Ta przeglądarka nie obsługuje kluczy dostępu.',
        'cancelled' => 'Sprawdzanie klucza dostępu zostało anulowane lub upłynął czas.',
    ],

    'security' => [
        'title' => 'Bezpieczeństwo logowania',
        'required' => 'Twoje konto wymaga drugiego kroku: zachowaj co najmniej jeden sposób potwierdzania logowania.',
        'optional' => 'Dodaj drugi krok, aby chronić konto nawet wtedy, gdy ktoś pozna Twoje hasło.',
        'must_enrol' => 'Skonfiguruj drugi krok, zanim przejdziesz dalej: jest wymagany dla Twojego konta.',
        'factors' => 'Twoje sposoby potwierdzania',
        'none' => 'Nic jeszcze nie skonfigurowano.',
        'none_short' => 'brak',
        'added_at' => 'dodano :date',
        'used_at' => 'ostatnio użyto :date',
        'remove' => 'Usuń',
        'removed' => 'Usunięto.',
        'added' => 'Dodano. Będzie wymagany przy logowaniu.',
        'last_factor' => 'To Twój ostatni sposób potwierdzania logowania, a co najmniej jeden jest wymagany. Najpierw dodaj inny.',
        'name' => 'Nazwa (opcjonalnie)',
        'name_totp' => 'Telefon',
        'name_passkey' => 'Laptop',
        'confirm' => 'Potwierdź',
        'totp_hint' => 'Google Authenticator, Microsoft Authenticator, 1Password, Aegis lub dowolna inna aplikacja pokazująca sześciocyfrowe kody.',
        'totp_add' => 'Skonfiguruj aplikację',
        'totp_another' => 'Dodaj kolejną aplikację',
        'totp_manual' => 'Zeskanuj kod QR w aplikacji lub wpisz ten klucz:',
        'qr' => 'Kod QR dla aplikacji uwierzytelniającej',
        'passkey_hint' => 'Odcisk palca, twarz lub PIN na tym urządzeniu albo klucz bezpieczeństwa zamiast przepisywania kodu.',
        'passkey_hint_passwordless' => 'Odcisk palca, twarz lub PIN na tym urządzeniu albo klucz bezpieczeństwa. Klucz dostępu pozwala też logować się bez hasła.',
        'passkey_add' => 'Dodaj klucz dostępu',
        'passkey_failed' => 'Nie zapisano klucza dostępu. Spróbuj ponownie.',
        'phone' => 'Numer telefonu z numerem kierunkowym kraju',
        'phone_invalid' => 'Wpisz numer telefonu z numerem kierunkowym kraju, na przykład +1 555 0100.',
        'sms_add' => 'Wyślij kod',
        'sms_sent' => 'Kod wysłano na :phone',
        'email_hint' => 'Kod wysyłany na :email przy każdym logowaniu.',
        'email_add' => 'Używaj kodów w e-mailu',
        'recovery' => 'Kody odzyskiwania',
        'recovery_left' => 'Został jeden niewykorzystany kod.|Zostały :count niewykorzystane kody.|Zostało :count niewykorzystanych kodów.',
        'recovery_regenerate' => 'Utwórz nowe kody',
        'recovery_new' => 'Twoje kody odzyskiwania',
        'recovery_once' => 'Zapisz je teraz w bezpiecznym miejscu: są pokazywane tylko raz. Każdy kod wpuści Cię jednorazowo, jeśli zgubisz telefon lub klucz. Stare kody przestaną działać.',
        'devices' => 'Zaufane urządzenia',
        'devices_none' => 'Żadne urządzenie nie pomija drugiego kroku.',
        'devices_revoke' => 'Zapomnij wszystkie urządzenia',
        'devices_revoked' => 'Na wszystkich urządzeniach drugi krok będzie znów wymagany.',
        'until' => 'do :date',
        'events' => 'Ostatnia aktywność',
    ],

    'events' => [
        'signed_in' => 'Zalogowano',
        'challenged' => 'Poproszono o drugi krok',
        'failed' => 'Błędna odpowiedź',
        'locked' => 'Zablokowano na pewien czas',
        'trusted_device' => 'Zaufane urządzenie, krok pominięto',
        'code_sent' => 'Wysłano kod',
        'factor_added' => 'Dodano sposób potwierdzania',
        'factor_removed' => 'Usunięto sposób potwierdzania',
        'recovery_used' => 'Użyto kodu odzyskiwania',
        'recovery_generated' => 'Nowe kody odzyskiwania',
        'device_trusted' => 'Urządzenie oznaczono jako zaufane',
        'device_revoked' => 'Urządzenia zapomniane',
        'reset' => 'Zresetowano przez administratora',
        'social_linked' => 'Połączono konto społecznościowe',
        'social_unlinked' => 'Odłączono konto społecznościowe',
        'social_refused' => 'Odrzucono logowanie kontem społecznościowym',
    ],

    'log' => [
        'when' => 'Kiedy',
        'who' => 'Kto',
        'what' => 'Zdarzenie',
        'how' => 'Metoda',
        'where' => 'Adres IP',
        'guard' => 'Gdzie',
        'all' => 'Wszystkie',
        'filter' => 'Pokaż',
        'empty' => 'Na razie nic nie ma.',
        'by_admin' => 'administrator nr :id',
        'pages' => 'Strony',
        'newer' => 'Nowsze',
        'older' => 'Starsze',
    ],

    'manage' => [
        'hint' => 'Jeśli ktoś zgubił telefon lub klucz, zresetuj mu drugi krok: zaloguje się hasłem i skonfiguruje go od nowa.',
        'search' => 'E-mail',
        'email' => 'E-mail',
        'reset' => 'Zresetuj',
        'confirm' => 'Usunąć wszystkie sposoby potwierdzania logowania, kody odzyskiwania i zaufane urządzenia konta :email?',
        'reset_done' => 'Zresetowano. Ta osoba skonfiguruje drugi krok od nowa.',
    ],

    'menu' => [
        'title' => 'Logowanie',
        'security' => 'Bezpieczeństwo mojego logowania',
        'log' => 'Dziennik logowań',
        'people' => 'Resetowanie logowania dwuetapowego',
    ],

    'settings' => [
        'description' => 'Puste pole zachowuje wartość z config/atrium-2fa.php.',
        'from_config' => 'Puste: jak w pliku konfiguracji.',
        'required' => ':who: kto musi przejść drugi krok',
        'modes' => [
            'all' => 'Wszyscy',
            'roles' => 'Superadministratorzy i wybrane role',
            'optional' => 'Tylko ci, którzy go skonfigurowali',
        ],
        'roles' => ':who: role, które muszą go przejść',
        'factors' => ':who: dozwolone sposoby potwierdzania',
        'passwordless' => ':who: logowanie samym kluczem dostępu',
        'magic_code' => ':who: logowanie kodem z e-maila',
        'trusted_days' => ':who: liczba dni, przez które zaufane urządzenie pomija krok',
        'trusted_days_hint' => '0 wyłącza „Ufaj temu urządzeniu”.',
        'yes' => 'Tak',
        'no' => 'Nie',
    ],

    'sms' => ':code to Twój kod logowania do :site. Nikomu go nie podawaj.',

    'mail' => [
        'subject' => ':code to Twój kod logowania do :site',
        'title' => [
            'two_factor' => 'Kod potwierdzenia logowania',
            'magic' => 'Kod logowania',
        ],
        'heading' => [
            'two_factor' => 'Potwierdzenie logowania do :site',
            'magic' => 'Logowanie do :site',
        ],
        'code_label' => 'Twój kod:',
        'valid_for' => 'Kod jest ważny przez :count minutę.|Kod jest ważny przez :count minuty.|Kod jest ważny przez :count minut.',
        'not_you' => 'Jeśli to nie Ty się teraz logujesz, nikomu nie udostępniaj tego kodu. Jeśli przyszedł jako drugi krok, ktoś zna Twoje hasło: zmień je.',
    ],
];
