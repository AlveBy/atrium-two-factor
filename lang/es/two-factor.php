<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

/*
 * El segundo paso del inicio de sesión, el acceso sin contraseña, la página de seguridad
 * y el registro de accesos.
 */
return [
    'module' => 'Inicio de sesión en dos pasos',
    'back_to_login' => 'Volver al inicio de sesión',

    'guards' => [
        'admin' => 'Panel de administración',
        'account' => 'Cuenta personal',
    ],

    'factors' => [
        'totp' => 'Aplicación de autenticación',
        'passkey' => 'Llave de acceso',
        'email' => 'Código por correo',
        'sms' => 'Código por SMS',
    ],

    'methods' => [
        'password' => 'Contraseña',
        'code' => 'Código por correo',
        'passkey' => 'Llave de acceso',
        'register' => 'Registro',
        'totp' => 'Aplicación de autenticación',
        'email' => 'Código por correo',
        'sms' => 'Código por SMS',
        'recovery' => 'Código de recuperación',
    ],

    'login' => [
        'passkey' => 'Iniciar sesión con una llave de acceso',
        'code' => 'Recibir un código por correo',
    ],

    'challenge' => [
        'title' => 'Confirmar inicio de sesión',
        'sent_to' => 'Hemos enviado un código a :destination',
        'totp_hint' => 'Abre tu aplicación de autenticación e introduce el código de seis dígitos que muestra para este sitio.',
        'passkey_hint' => 'Usa la llave de acceso de este dispositivo o una llave de seguridad: huella digital, rostro o PIN.',
        'use_passkey' => 'Usar una llave de acceso',
        'code_label' => [
            'totp' => 'Código de la aplicación',
            'email' => 'Código del correo',
            'sms' => 'Código del SMS',
        ],
        'submit' => 'Confirmar',
        'resend' => 'Volver a enviar el código',
        'resend_in' => 'Volver a enviar en :seconds s',
        'resent' => 'El código se ha vuelto a enviar.',
        'resend_too_soon' => 'El código ya se ha enviado. Podrás pedir uno nuevo dentro de un minuto.',
        'send_failed' => 'No se pudo enviar el código: el correo o los SMS no están disponibles. Prueba otra forma o ponte en contacto con el administrador.',
        'code_required' => 'Introduce el código.',
        'code_invalid' => 'El código no es correcto o ha caducado. Compruébalo o solicita uno nuevo.',
        'throttle' => 'Demasiados intentos. Vuelve a intentarlo en :seconds s.',
        'expired' => 'El inicio de sesión ha caducado. Vuelve a iniciar sesión.',
        'trust' => 'No volver a preguntar en este dispositivo durante :days día|No volver a preguntar en este dispositivo durante :days días',
        'other_ways' => 'Otras formas de confirmar',
        'use_recovery' => 'Usar un código de recuperación',
        'recovery_hint' => 'Introduce uno de los códigos de recuperación que guardaste al configurar el inicio de sesión en dos pasos. Cada código funciona una sola vez.',
        'recovery_code' => 'Código de recuperación',
        'recovery_invalid' => 'Este código de recuperación no es correcto o ya se ha usado.',
        'recovery_left' => 'Has iniciado sesión con un código de recuperación. Códigos restantes: :count. Crea otros nuevos en la página de seguridad.',
        'back_to_factor' => 'Atrás',
        'cancel' => 'Iniciar sesión con otro usuario',
    ],

    'magic' => [
        'title' => 'Iniciar sesión con un código',
        'hint' => 'Introduce tu correo y te enviaremos un código de seis dígitos. No necesitas contraseña.',
        'email' => 'Correo electrónico',
        'send' => 'Enviar el código',
        'sent' => 'Si existe una cuenta con esta dirección, el código está en camino.',
        'check_mail' => 'Si :email pertenece a una cuenta, le hemos enviado un código. Funciona una sola vez y durante unos minutos.',
        'other_email' => 'Usar otro correo',
        'email_required' => 'Introduce tu correo.',
        'email_invalid' => 'Introduce un correo válido.',
        'expired' => 'El código ha caducado. Solicita uno nuevo.',
        'too_many' => 'Demasiados códigos incorrectos. Solicita uno nuevo.',
    ],

    'passkey' => [
        'title' => 'Iniciar sesión con una llave de acceso',
        'hint' => 'Tu dispositivo te pedirá la huella digital, el rostro o el PIN. No necesitas contraseña.',
        'button' => 'Continuar con una llave de acceso',
        'failed' => 'Esta llave de acceso no ha funcionado. Inténtalo de nuevo o inicia sesión de otra forma.',
        'unsupported' => 'Este navegador no admite llaves de acceso.',
        'cancelled' => 'La comprobación de la llave de acceso se canceló o agotó el tiempo.',
    ],

    'security' => [
        'title' => 'Seguridad del inicio de sesión',
        'required' => 'Tu cuenta requiere un segundo paso: conserva al menos una forma de confirmar el inicio de sesión.',
        'optional' => 'Añade un segundo paso para proteger tu cuenta aunque alguien descubra tu contraseña.',
        'must_enrol' => 'Configura un segundo paso antes de continuar: es obligatorio para tu cuenta.',
        'factors' => 'Tus formas de confirmar',
        'none' => 'Todavía no hay nada configurado.',
        'none_short' => 'ninguna',
        'added_at' => 'añadido el :date',
        'used_at' => 'último uso el :date',
        'remove' => 'Quitar',
        'removed' => 'Quitado.',
        'added' => 'Añadido. Se pedirá al iniciar sesión.',
        'last_factor' => 'Es tu última forma de confirmar el inicio de sesión y se necesita al menos una. Añade primero otra.',
        'name' => 'Nombre (opcional)',
        'name_totp' => 'Teléfono',
        'name_passkey' => 'Portátil',
        'confirm' => 'Confirmar',
        'totp_hint' => 'Google Authenticator, Microsoft Authenticator, 1Password, Aegis o cualquier otra aplicación que muestre códigos de seis dígitos.',
        'totp_add' => 'Configurar una aplicación',
        'totp_another' => 'Añadir otra aplicación',
        'totp_manual' => 'Escanea el código QR con la aplicación o escribe esta clave:',
        'qr' => 'Código QR para la aplicación de autenticación',
        'passkey_hint' => 'Una huella digital, el rostro o el PIN de este dispositivo, o una llave de seguridad, en lugar de escribir un código.',
        'passkey_hint_passwordless' => 'Una huella digital, el rostro o el PIN de este dispositivo, o una llave de seguridad. Una llave de acceso también te permite entrar sin contraseña.',
        'passkey_add' => 'Añadir una llave de acceso',
        'passkey_failed' => 'No se guardó la llave de acceso. Inténtalo de nuevo.',
        'phone' => 'Número de teléfono con el prefijo del país',
        'phone_invalid' => 'Introduce el número de teléfono con el prefijo del país, por ejemplo +1 555 0100.',
        'sms_add' => 'Enviar un código',
        'sms_sent' => 'Código enviado al :phone',
        'email_hint' => 'Un código enviado a :email cada vez que inicies sesión.',
        'email_add' => 'Usar códigos por correo',
        'recovery' => 'Códigos de recuperación',
        'recovery_left' => 'Queda un código sin usar.|Quedan :count códigos sin usar.',
        'recovery_regenerate' => 'Crear códigos nuevos',
        'recovery_new' => 'Tus códigos de recuperación',
        'recovery_once' => 'Guárdalos ahora en un lugar seguro: solo se muestran una vez. Cada código te deja entrar una vez si pierdes el teléfono o la llave. Los códigos anteriores dejan de funcionar.',
        'devices' => 'Dispositivos de confianza',
        'devices_none' => 'Ningún dispositivo se salta el segundo paso.',
        'devices_revoke' => 'Olvidar todos los dispositivos',
        'devices_revoked' => 'A todos los dispositivos se les volverá a pedir el segundo paso.',
        'until' => 'hasta el :date',
        'events' => 'Actividad reciente',
    ],

    'events' => [
        'signed_in' => 'Sesión iniciada',
        'challenged' => 'Segundo paso solicitado',
        'failed' => 'Respuesta incorrecta',
        'locked' => 'Bloqueado temporalmente',
        'trusted_device' => 'Dispositivo de confianza, paso omitido',
        'code_sent' => 'Código enviado',
        'factor_added' => 'Forma de confirmar añadida',
        'factor_removed' => 'Forma de confirmar quitada',
        'recovery_used' => 'Código de recuperación usado',
        'recovery_generated' => 'Nuevos códigos de recuperación',
        'device_trusted' => 'Dispositivo de confianza',
        'device_revoked' => 'Dispositivos olvidados',
        'reset' => 'Restablecido por un administrador',
        'social_linked' => 'Cuenta social vinculada',
        'social_unlinked' => 'Cuenta social desvinculada',
        'social_refused' => 'Inicio de sesión social rechazado',
    ],

    'log' => [
        'when' => 'Cuándo',
        'who' => 'Quién',
        'what' => 'Evento',
        'how' => 'Método',
        'where' => 'Dirección IP',
        'guard' => 'Dónde',
        'all' => 'Todos',
        'filter' => 'Mostrar',
        'empty' => 'Todavía no hay nada.',
        'by_admin' => 'administrador n.º :id',
        'pages' => 'Páginas',
        'newer' => 'Más recientes',
        'older' => 'Más antiguos',
    ],

    'manage' => [
        'hint' => 'Si alguien perdió el teléfono o la llave, restablece su segundo paso: la persona inicia sesión con la contraseña y lo configura de nuevo.',
        'search' => 'Correo electrónico',
        'email' => 'Correo electrónico',
        'reset' => 'Restablecer',
        'confirm' => '¿Quitar todas las formas de confirmar el inicio de sesión, los códigos de recuperación y los dispositivos de confianza de :email?',
        'reset_done' => 'Restablecido. La persona configurará el segundo paso de nuevo.',
    ],

    'menu' => [
        'title' => 'Inicio de sesión',
        'security' => 'Seguridad de mi inicio de sesión',
        'log' => 'Registro de accesos',
        'people' => 'Restablecimientos en dos pasos',
    ],

    'settings' => [
        'description' => 'Un campo vacío conserva el valor de config/atrium-2fa.php.',
        'from_config' => 'Vacío: como en el archivo de configuración.',
        'required' => ':who: quién debe pasar un segundo paso',
        'modes' => [
            'all' => 'Todos',
            'roles' => 'Superadministradores y los roles elegidos',
            'optional' => 'Solo quienes lo hayan configurado',
        ],
        'roles' => ':who: roles que deben pasarlo',
        'factors' => ':who: formas de confirmar permitidas',
        'passwordless' => ':who: iniciar sesión solo con una llave de acceso',
        'magic_code' => ':who: iniciar sesión con un código por correo',
        'trusted_days' => ':who: días que un dispositivo de confianza se salta el paso',
        'trusted_days_hint' => '0 desactiva «confiar en este dispositivo».',
        'yes' => 'Sí',
        'no' => 'No',
    ],

    'sms' => ':code es tu código de acceso a :site. No lo compartas.',

    'mail' => [
        'subject' => ':code es tu código de acceso a :site',
        'title' => [
            'two_factor' => 'Código de confirmación de acceso',
            'magic' => 'Código de acceso',
        ],
        'heading' => [
            'two_factor' => 'Confirmación del acceso a :site',
            'magic' => 'Acceso a :site',
        ],
        'code_label' => 'Tu código:',
        'valid_for' => 'El código es válido durante :count minuto.|El código es válido durante :count minutos.',
        'not_you' => 'Si no has intentado iniciar sesión, no compartas este código con nadie. Si llegó como segundo paso, alguien conoce tu contraseña: cámbiala.',
    ],
];
