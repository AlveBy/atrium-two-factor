<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

/*
 * Girişin ikinci adımı, parolasız giriş, güvenlik sayfası ve giriş günlüğü.
 */
return [
    'module' => 'İki adımlı giriş',
    'back_to_login' => 'Girişe dön',

    'guards' => [
        'admin' => 'Yönetim paneli',
        'account' => 'Kişisel hesap',
    ],

    'factors' => [
        'totp' => 'Kimlik doğrulama uygulaması',
        'passkey' => 'Geçiş anahtarı',
        'email' => 'E-posta ile kod',
        'sms' => 'SMS ile kod',
    ],

    'methods' => [
        'password' => 'Parola',
        'code' => 'E-posta ile kod',
        'passkey' => 'Geçiş anahtarı',
        'register' => 'Kayıt',
        'totp' => 'Kimlik doğrulama uygulaması',
        'email' => 'E-posta ile kod',
        'sms' => 'SMS ile kod',
        'recovery' => 'Kurtarma kodu',
    ],

    'login' => [
        'passkey' => 'Geçiş anahtarıyla giriş yap',
        'code' => 'E-posta ile kod al',
    ],

    'challenge' => [
        'title' => 'Girişi onaylayın',
        'sent_to' => ':destination adresine bir kod gönderdik',
        'totp_hint' => 'Kimlik doğrulama uygulamanızı açın ve bu site için gösterdiği altı haneli kodu girin.',
        'passkey_hint' => 'Bu cihazdaki geçiş anahtarını ya da bir güvenlik anahtarını kullanın: parmak izi, yüz veya PIN.',
        'use_passkey' => 'Geçiş anahtarı kullan',
        'code_label' => [
            'totp' => 'Uygulamadaki kod',
            'email' => 'E-postadaki kod',
            'sms' => 'SMS\'teki kod',
        ],
        'submit' => 'Onayla',
        'resend' => 'Kodu yeniden gönder',
        'resend_in' => ':seconds sn sonra yeniden gönder',
        'resent' => 'Kod yeniden gönderildi.',
        'resend_too_soon' => 'Kod zaten gönderildi. Bir dakika sonra yenisini isteyebilirsiniz.',
        'send_failed' => 'Kod gönderilemedi: e-posta veya SMS kullanılamıyor. Başka bir yol deneyin ya da yöneticiyle iletişime geçin.',
        'code_required' => 'Kodu girin.',
        'code_invalid' => 'Kod yanlış veya süresi dolmuş. Kodu kontrol edin veya yenisini isteyin.',
        'throttle' => 'Çok fazla deneme. :seconds sn sonra tekrar deneyin.',
        'expired' => 'Girişin süresi doldu. Yeniden giriş yapın.',
        'trust' => 'Bu cihazda :days gün boyunca sorma',
        'other_ways' => 'Onaylamanın diğer yolları',
        'use_recovery' => 'Kurtarma kodu kullan',
        'recovery_hint' => 'İki adımlı girişi kurarken kaydettiğiniz kurtarma kodlarından birini girin. Her kod yalnızca bir kez çalışır.',
        'recovery_code' => 'Kurtarma kodu',
        'recovery_invalid' => 'Bu kurtarma kodu yanlış veya daha önce kullanılmış.',
        'recovery_left' => 'Kurtarma koduyla giriş yaptınız. Kalan kod sayısı: :count. Güvenlik sayfasında yenilerini oluşturun.',
        'back_to_factor' => 'Geri',
        'cancel' => 'Başka bir kullanıcı olarak giriş yap',
    ],

    'magic' => [
        'title' => 'Kodla giriş yap',
        'hint' => 'E-posta adresinizi girin, size altı haneli bir kod gönderelim. Parola gerekmez.',
        'email' => 'E-posta',
        'send' => 'Kodu gönder',
        'sent' => 'Bu adrese ait bir hesap varsa, kod yolda.',
        'check_mail' => ':email bir hesaba aitse oraya bir kod gönderdik. Kod yalnızca bir kez ve birkaç dakika geçerlidir.',
        'other_email' => 'Başka bir e-posta kullan',
        'email_required' => 'E-posta adresinizi girin.',
        'email_invalid' => 'Geçerli bir e-posta adresi girin.',
        'expired' => 'Kodun süresi doldu. Yenisini isteyin.',
        'too_many' => 'Çok fazla yanlış kod. Yenisini isteyin.',
    ],

    'passkey' => [
        'title' => 'Geçiş anahtarıyla giriş yap',
        'hint' => 'Cihazınız parmak izinizi, yüzünüzü veya PIN\'inizi isteyecek. Parola gerekmez.',
        'button' => 'Geçiş anahtarıyla devam et',
        'failed' => 'Bu geçiş anahtarı çalışmadı. Tekrar deneyin veya başka bir yolla giriş yapın.',
        'unsupported' => 'Bu tarayıcı geçiş anahtarlarını desteklemiyor.',
        'cancelled' => 'Geçiş anahtarı denetimi iptal edildi veya zaman aşımına uğradı.',
    ],

    'security' => [
        'title' => 'Giriş güvenliği',
        'demo_locked' => 'Demoda kullanılamaz. Burada kimse oturum açmayı doğrulama yöntemi ekleyemez, kaldıramaz veya sıfırlayamaz; böylece sonraki ziyaretçi her zaman giriş yapabilir.',
        'required' => 'Hesabınız için ikinci bir adım gerekiyor: girişi onaylamanın en az bir yolunu koruyun.',
        'optional' => 'Biri parolanızı öğrense bile hesabınızı korumak için ikinci bir adım ekleyin.',
        'must_enrol' => 'Devam etmeden önce ikinci bir adım kurun: hesabınız için zorunludur.',
        'factors' => 'Onaylama yollarınız',
        'none' => 'Henüz hiçbir şey kurulmadı.',
        'none_short' => 'yok',
        'added_at' => ':date tarihinde eklendi',
        'used_at' => 'son kullanım: :date',
        'remove' => 'Kaldır',
        'removed' => 'Kaldırıldı.',
        'added' => 'Eklendi. Giriş yaparken sorulacak.',
        'last_factor' => 'Bu, girişi onaylamanın son yolu ve en az biri zorunlu. Önce başka bir yol ekleyin.',
        'name' => 'Ad (isteğe bağlı)',
        'name_totp' => 'Telefon',
        'name_passkey' => 'Dizüstü',
        'confirm' => 'Onayla',
        'totp_hint' => 'Google Authenticator, Microsoft Authenticator, 1Password, Aegis veya altı haneli kodlar gösteren herhangi bir uygulama.',
        'totp_add' => 'Uygulama kur',
        'totp_another' => 'Başka bir uygulama ekle',
        'totp_manual' => 'QR kodunu uygulamayla tarayın veya şu anahtarı yazın:',
        'qr' => 'Kimlik doğrulama uygulaması için QR kodu',
        'passkey_hint' => 'Kod yazmak yerine bu cihazdaki parmak izi, yüz veya PIN ya da bir güvenlik anahtarı.',
        'passkey_hint_passwordless' => 'Bu cihazdaki parmak izi, yüz veya PIN ya da bir güvenlik anahtarı. Geçiş anahtarıyla parolasız da giriş yapabilirsiniz.',
        'passkey_add' => 'Geçiş anahtarı ekle',
        'passkey_failed' => 'Geçiş anahtarı kaydedilmedi. Tekrar deneyin.',
        'phone' => 'Ülke kodlu telefon numarası',
        'phone_invalid' => 'Telefon numarasını ülke koduyla girin, örneğin +1 555 0100.',
        'sms_add' => 'Kod gönder',
        'sms_sent' => 'Kod :phone numarasına gönderildi',
        'email_hint' => 'Her girişte :email adresine gönderilen bir kod.',
        'email_add' => 'E-posta ile kod kullan',
        'recovery' => 'Kurtarma kodları',
        'recovery_left' => 'Kullanılmamış :count kod kaldı.',
        'recovery_regenerate' => 'Yeni kodlar oluştur',
        'recovery_new' => 'Kurtarma kodlarınız',
        'recovery_once' => 'Şimdi güvenli bir yere kaydedin: yalnızca bir kez gösterilirler. Telefonunuzu veya anahtarınızı kaybederseniz her kod sizi bir kez içeri alır. Eski kodlar artık çalışmaz.',
        'devices' => 'Güvenilen cihazlar',
        'devices_none' => 'İkinci adımı atlayan cihaz yok.',
        'devices_revoke' => 'Tüm cihazları unut',
        'devices_revoked' => 'Tüm cihazlardan ikinci adım yeniden istenecek.',
        'until' => ':date tarihine kadar',
        'events' => 'Son etkinlik',
    ],

    'events' => [
        'signed_in' => 'Giriş yapıldı',
        'challenged' => 'İkinci adım istendi',
        'failed' => 'Yanlış yanıt',
        'locked' => 'Bir süreliğine kilitlendi',
        'trusted_device' => 'Güvenilen cihaz, adım atlandı',
        'code_sent' => 'Kod gönderildi',
        'factor_added' => 'Onaylama yolu eklendi',
        'factor_removed' => 'Onaylama yolu kaldırıldı',
        'recovery_used' => 'Kurtarma kodu kullanıldı',
        'recovery_generated' => 'Yeni kurtarma kodları',
        'device_trusted' => 'Cihaza güvenildi',
        'device_revoked' => 'Cihazlar unutuldu',
        'reset' => 'Bir yönetici tarafından sıfırlandı',
        'social_linked' => 'Sosyal hesap bağlandı',
        'social_unlinked' => 'Sosyal hesabın bağlantısı kaldırıldı',
        'social_refused' => 'Sosyal hesapla giriş reddedildi',
    ],

    'log' => [
        'when' => 'Ne zaman',
        'who' => 'Kim',
        'what' => 'Olay',
        'how' => 'Yöntem',
        'where' => 'IP adresi',
        'guard' => 'Nerede',
        'all' => 'Tümü',
        'filter' => 'Göster',
        'empty' => 'Henüz bir şey yok.',
        'by_admin' => 'yönetici no. :id',
        'pages' => 'Sayfalar',
        'newer' => 'Daha yeni',
        'older' => 'Daha eski',
    ],

    'manage' => [
        'hint' => 'Biri telefonunu veya anahtarını kaybettiyse ikinci adımını sıfırlayın: parolasıyla giriş yapar ve yeniden kurar.',
        'search' => 'E-posta',
        'email' => 'E-posta',
        'reset' => 'Sıfırla',
        'confirm' => ':email için tüm onaylama yolları, kurtarma kodları ve güvenilen cihazlar kaldırılsın mı?',
        'reset_done' => 'Sıfırlandı. Kişi ikinci adımı yeniden kuracak.',
    ],

    'menu' => [
        'title' => 'Giriş',
        'security' => 'Giriş güvenliğim',
        'log' => 'Giriş günlüğü',
        'people' => 'İki adımlı giriş sıfırlama',
    ],

    'settings' => [
        'description' => 'Boş bırakılan alan config/atrium-2fa.php içindeki değeri korur.',
        'from_config' => 'Boş: yapılandırma dosyasındaki gibi.',
        'required' => ':who: ikinci adımdan kimler geçmeli',
        'modes' => [
            'all' => 'Herkes',
            'roles' => 'Süper yöneticiler ve seçilen roller',
            'optional' => 'Yalnızca kuranlar',
        ],
        'roles' => ':who: ikinci adımdan geçmesi gereken roller',
        'factors' => ':who: izin verilen onaylama yolları',
        'passwordless' => ':who: yalnızca geçiş anahtarıyla giriş',
        'magic_code' => ':who: e-posta ile kodla giriş',
        'trusted_days' => ':who: güvenilen cihazın adımı atladığı gün sayısı',
        'trusted_days_hint' => '0, "bu cihaza güven" seçeneğini kapatır.',
        'yes' => 'Evet',
        'no' => 'Hayır',
    ],

    'sms' => ':site giriş kodunuz: :code. Kimseyle paylaşmayın.',

    'mail' => [
        'subject' => ':site giriş kodunuz: :code',
        'title' => [
            'two_factor' => 'Giriş onay kodu',
            'magic' => 'Giriş kodu',
        ],
        'heading' => [
            'two_factor' => ':site girişini onaylama',
            'magic' => ':site girişi',
        ],
        'code_label' => 'Kodunuz:',
        'valid_for' => 'Kod :count dakika geçerlidir.',
        'not_you' => 'Giriş yapmayı siz denemediyseniz bu kodu kimseyle paylaşmayın. İkinci adım olarak geldiyse parolanızı başka biri biliyor: parolanızı değiştirin.',
    ],
];
