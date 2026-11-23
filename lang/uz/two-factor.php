<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

/*
 * Kirishning ikkinchi qadami, parolsiz kirish, xavfsizlik sahifasi va kirishlar jurnali.
 */
return [
    'module' => 'Ikki bosqichli kirish',
    'back_to_login' => 'Kirishga qaytish',

    'guards' => [
        'admin' => 'Administrator paneli',
        'account' => 'Shaxsiy kabinet',
    ],

    'factors' => [
        'totp' => 'Autentifikator ilovasi',
        'passkey' => 'Kirish kaliti',
        'email' => 'Pochtadagi kod',
        'sms' => 'SMS dagi kod',
    ],

    'methods' => [
        'password' => 'Parol',
        'code' => 'Pochtadagi kod',
        'passkey' => 'Kirish kaliti',
        'register' => 'Roʻyxatdan oʻtish',
        'totp' => 'Autentifikator ilovasi',
        'email' => 'Pochtadagi kod',
        'sms' => 'SMS dagi kod',
        'recovery' => 'Zaxira kodi',
    ],

    'login' => [
        'passkey' => 'Kirish kaliti bilan kirish',
        'code' => 'Kodni pochta orqali olish',
    ],

    'challenge' => [
        'title' => 'Kirishni tasdiqlash',
        'sent_to' => 'Kodni :destination manziliga yubordik',
        'totp_hint' => 'Autentifikator ilovasini oching va u shu sayt uchun koʻrsatayotgan olti xonali kodni kiriting.',
        'passkey_hint' => 'Shu qurilmadagi kirish kalitidan yoki xavfsizlik kalitidan foydalaning: barmoq izi, yuz yoki PIN-kod.',
        'use_passkey' => 'Kirish kalitidan foydalanish',
        'code_label' => [
            'totp' => 'Ilovadagi kod',
            'email' => 'Xatdagi kod',
            'sms' => 'SMS dagi kod',
        ],
        'submit' => 'Tasdiqlash',
        'resend' => 'Kodni qayta yuborish',
        'resend_in' => ':seconds soniyadan keyin qayta yuborish',
        'resent' => 'Kod qayta yuborildi.',
        'resend_too_soon' => 'Kod allaqachon yuborilgan. Yangisini bir daqiqadan keyin soʻrashingiz mumkin.',
        'send_failed' => 'Kodni yuborib boʻlmadi: pochta yoki SMS ishlamayapti. Boshqa usulni sinab koʻring yoki administratorga murojaat qiling.',
        'code_required' => 'Kodni kiriting.',
        'code_invalid' => 'Kod notoʻgʻri yoki muddati oʻtgan. Uni tekshiring yoki yangisini soʻrang.',
        'throttle' => 'Urinishlar juda koʻp. :seconds soniyadan keyin qayta urinib koʻring.',
        'expired' => 'Kirish muddati tugadi. Qaytadan kiring.',
        'trust' => 'Shu qurilmada :days kun davomida soʻramaslik',
        'other_ways' => 'Tasdiqlashning boshqa usullari',
        'use_recovery' => 'Zaxira kodidan foydalanish',
        'recovery_hint' => 'Ikki bosqichli kirishni sozlaganingizda saqlagan zaxira kodlaridan birini kiriting. Har bir kod bir marta ishlaydi.',
        'recovery_code' => 'Zaxira kodi',
        'recovery_invalid' => 'Bu zaxira kodi notoʻgʻri yoki allaqachon ishlatilgan.',
        'recovery_left' => 'Siz zaxira kodi bilan kirdingiz. Qolgan kodlar: :count. Yangilarini xavfsizlik sahifasida yarating.',
        'back_to_factor' => 'Orqaga',
        'cancel' => 'Boshqa foydalanuvchi sifatida kirish',
    ],

    'magic' => [
        'title' => 'Kod bilan kirish',
        'hint' => 'Pochtangizni kiriting, biz olti xonali kod yuboramiz. Parol kerak emas.',
        'email' => 'Pochta',
        'send' => 'Kodni yuborish',
        'sent' => 'Bunday manzilli akkaunt mavjud boʻlsa, kod yoʻlda.',
        'check_mail' => 'Agar :email biror akkauntga tegishli boʻlsa, biz u yerga kod yubordik. U bir marta va bir necha daqiqa amal qiladi.',
        'other_email' => 'Boshqa pochtadan foydalanish',
        'email_required' => 'Pochtangizni kiriting.',
        'email_invalid' => 'Toʻgʻri pochta manzilini kiriting.',
        'expired' => 'Kodning muddati tugagan. Yangisini soʻrang.',
        'too_many' => 'Notoʻgʻri kodlar juda koʻp. Yangisini soʻrang.',
    ],

    'passkey' => [
        'title' => 'Kirish kaliti bilan kirish',
        'hint' => 'Qurilma barmoq izi, yuz yoki PIN-kodni soʻraydi. Parol kerak emas.',
        'button' => 'Kirish kaliti bilan davom etish',
        'failed' => 'Bu kirish kaliti ishlamadi. Qayta urinib koʻring yoki boshqa usulda kiring.',
        'unsupported' => 'Bu brauzer kirish kalitlarini qoʻllab-quvvatlamaydi.',
        'cancelled' => 'Kirish kalitini tekshirish bekor qilindi yoki vaqti tugadi.',
    ],

    'security' => [
        'title' => 'Kirish xavfsizligi',
        'required' => 'Akkauntingiz uchun ikkinchi qadam talab qilinadi: kirishni tasdiqlashning kamida bitta usulini qoldiring.',
        'optional' => 'Birov parolingizni bilib olsa ham akkauntingizni himoya qilish uchun ikkinchi qadamni qoʻshing.',
        'must_enrol' => 'Davom etishdan oldin ikkinchi qadamni sozlang: u akkauntingiz uchun majburiy.',
        'factors' => 'Tasdiqlash usullaringiz',
        'none' => 'Hozircha hech narsa sozlanmagan.',
        'none_short' => 'yoʻq',
        'added_at' => ':date kuni qoʻshilgan',
        'used_at' => 'oxirgi marta :date kuni ishlatilgan',
        'remove' => 'Oʻchirish',
        'removed' => 'Oʻchirildi.',
        'added' => 'Qoʻshildi. Kirishda shu soʻraladi.',
        'last_factor' => 'Bu kirishni tasdiqlashning oxirgi usuli, kamida bittasi majburiy. Avval boshqasini qoʻshing.',
        'name' => 'Nomi (ixtiyoriy)',
        'name_totp' => 'Telefon',
        'name_passkey' => 'Noutbuk',
        'confirm' => 'Tasdiqlash',
        'totp_hint' => 'Google Authenticator, Microsoft Authenticator, 1Password, Aegis yoki olti xonali kodlarni koʻrsatadigan har qanday boshqa ilova.',
        'totp_add' => 'Ilovani sozlash',
        'totp_another' => 'Yana bitta ilova qoʻshish',
        'totp_manual' => 'QR kodni ilova bilan skanerlang yoki mana bu kalitni kiriting:',
        'qr' => 'Autentifikator ilovasi uchun QR kod',
        'passkey_hint' => 'Kod terish oʻrniga shu qurilmadagi barmoq izi, yuz yoki PIN-kod, yoki xavfsizlik kaliti.',
        'passkey_hint_passwordless' => 'Shu qurilmadagi barmoq izi, yuz yoki PIN-kod, yoki xavfsizlik kaliti. Kirish kaliti sizni parolsiz ham kiritadi.',
        'passkey_add' => 'Kirish kalitini qoʻshish',
        'passkey_failed' => 'Kirish kaliti saqlanmadi. Qayta urinib koʻring.',
        'phone' => 'Davlat kodi bilan telefon raqami',
        'phone_invalid' => 'Telefon raqamini davlat kodi bilan kiriting, masalan +1 555 0100.',
        'sms_add' => 'Kodni yuborish',
        'sms_sent' => 'Kod :phone raqamiga yuborildi',
        'email_hint' => 'Har safar kirganingizda :email manziliga yuboriladigan kod.',
        'email_add' => 'Pochtadagi kodlardan foydalanish',
        'recovery' => 'Zaxira kodlari',
        'recovery_left' => 'Ishlatilmagan :count ta kod qoldi.',
        'recovery_regenerate' => 'Yangi kodlar yaratish',
        'recovery_new' => 'Zaxira kodlaringiz',
        'recovery_once' => 'Ularni hoziroq xavfsiz joyga saqlang: ular faqat bir marta koʻrsatiladi. Telefon yoki kalitingizni yoʻqotsangiz, har bir kod sizni bir marta kiritadi. Eski kodlar endi ishlamaydi.',
        'devices' => 'Ishonchli qurilmalar',
        'devices_none' => 'Ikkinchi qadam oʻtkazib yuboriladigan qurilmalar yoʻq.',
        'devices_revoke' => 'Barcha qurilmalarni unutish',
        'devices_revoked' => 'Barcha qurilmalarda ikkinchi qadam yana soʻraladi.',
        'until' => ':date gacha',
        'events' => 'Soʻnggi harakatlar',
    ],

    'events' => [
        'signed_in' => 'Kirdi',
        'challenged' => 'Ikkinchi qadam soʻraldi',
        'failed' => 'Notoʻgʻri javob',
        'locked' => 'Bir muddatga bloklandi',
        'trusted_device' => 'Ishonchli qurilma, qadam oʻtkazib yuborildi',
        'code_sent' => 'Kod yuborildi',
        'factor_added' => 'Tasdiqlash usuli qoʻshildi',
        'factor_removed' => 'Tasdiqlash usuli oʻchirildi',
        'recovery_used' => 'Zaxira kodi ishlatildi',
        'recovery_generated' => 'Yangi zaxira kodlari',
        'device_trusted' => 'Qurilmaga ishonildi',
        'device_revoked' => 'Qurilmalar unutildi',
        'reset' => 'Administrator tomonidan tiklandi',
        'social_linked' => 'Ijtimoiy tarmoq ulandi',
        'social_unlinked' => 'Ijtimoiy tarmoq uzildi',
        'social_refused' => 'Ijtimoiy tarmoq orqali kirish rad etildi',
    ],

    'log' => [
        'when' => 'Qachon',
        'who' => 'Kim',
        'what' => 'Voqea',
        'how' => 'Usul',
        'where' => 'IP manzil',
        'guard' => 'Qayerda',
        'all' => 'Hammasi',
        'filter' => 'Koʻrsatish',
        'empty' => 'Hozircha hech narsa yoʻq.',
        'by_admin' => 'administrator №:id',
        'pages' => 'Sahifalar',
        'newer' => 'Yangiroq',
        'older' => 'Eskiroq',
    ],

    'manage' => [
        'hint' => 'Agar odam telefoni yoki kalitini yoʻqotgan boʻlsa, uning ikkinchi qadamini tiklang: u parol bilan kirib, uni qaytadan sozlaydi.',
        'search' => 'Pochta',
        'email' => 'Pochta',
        'reset' => 'Tiklash',
        'confirm' => ':email uchun kirishni tasdiqlashning barcha usullari, zaxira kodlari va ishonchli qurilmalar oʻchirilsinmi?',
        'reset_done' => 'Tiklandi. Odam ikkinchi qadamni qaytadan sozlaydi.',
    ],

    'menu' => [
        'title' => 'Kirish',
        'security' => 'Mening kirish xavfsizligim',
        'log' => 'Kirishlar jurnali',
        'people' => 'Ikki bosqichli kirishni tiklash',
    ],

    'settings' => [
        'description' => 'Boʻsh maydon config/atrium-2fa.php dagi qiymatni qoldiradi.',
        'from_config' => 'Boʻsh: konfiguratsiya faylidagidek.',
        'required' => ':who: kimlar ikkinchi qadamdan oʻtishi kerak',
        'modes' => [
            'all' => 'Hamma',
            'roles' => 'Superadministratorlar va tanlangan rollar',
            'optional' => 'Faqat uni sozlaganlar',
        ],
        'roles' => ':who: undan oʻtishi kerak boʻlgan rollar',
        'factors' => ':who: ruxsat etilgan tasdiqlash usullari',
        'passwordless' => ':who: faqat kirish kaliti bilan kirish',
        'magic_code' => ':who: pochtadagi kod bilan kirish',
        'trusted_days' => ':who: ishonchli qurilma qadamni necha kun oʻtkazib yuboradi',
        'trusted_days_hint' => '0 «bu qurilmaga ishonish»ni oʻchiradi.',
        'yes' => 'Ha',
        'no' => 'Yoʻq',
    ],

    'sms' => ':code — :site saytiga kirish kodi. Uni hech kimga bermang.',

    'mail' => [
        'subject' => ':code — :site saytiga kirish kodi',
        'title' => [
            'two_factor' => 'Kirishni tasdiqlash kodi',
            'magic' => 'Kirish kodi',
        ],
        'heading' => [
            'two_factor' => ':site saytiga kirishni tasdiqlash',
            'magic' => ':site saytiga kirish',
        ],
        'code_label' => 'Sizning kodingiz:',
        'valid_for' => 'Kod :count daqiqa amal qiladi.',
        'not_you' => 'Agar siz kirishga urinmagan boʻlsangiz, bu kodni hech kimga bermang. Agar u ikkinchi qadam sifatida kelgan boʻlsa, parolingizni kimdir biladi: uni oʻzgartiring.',
    ],
];
