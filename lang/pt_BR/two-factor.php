<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

/*
 * A segunda etapa do login, o login sem senha, a página de segurança
 * e o registro de acessos.
 */
return [
    'module' => 'Login em duas etapas',
    'back_to_login' => 'Voltar ao login',
    'back_to_panel' => 'Voltar ao painel',

    'guards' => [
        'admin' => 'Painel de administração',
        'account' => 'Conta pessoal',
    ],

    'factors' => [
        'totp' => 'Aplicativo autenticador',
        'passkey' => 'Chave de acesso',
        'email' => 'Código por e-mail',
        'sms' => 'Código por SMS',
    ],

    'methods' => [
        'password' => 'Senha',
        'code' => 'Código por e-mail',
        'passkey' => 'Chave de acesso',
        'register' => 'Cadastro',
        'totp' => 'Aplicativo autenticador',
        'email' => 'Código por e-mail',
        'sms' => 'Código por SMS',
        'recovery' => 'Código de recuperação',
    ],

    'login' => [
        'passkey' => 'Entrar com uma chave de acesso',
        'code' => 'Receber um código por e-mail',
    ],

    'challenge' => [
        'title' => 'Confirmar login',
        'sent_to' => 'Enviamos um código para :destination',
        'totp_hint' => 'Abra seu aplicativo autenticador e digite o código de seis dígitos que ele mostra para este site.',
        'passkey_hint' => 'Use a chave de acesso deste dispositivo ou uma chave de segurança: impressão digital, rosto ou PIN.',
        'use_passkey' => 'Usar uma chave de acesso',
        'code_label' => [
            'totp' => 'Código do aplicativo',
            'email' => 'Código do e-mail',
            'sms' => 'Código do SMS',
        ],
        'submit' => 'Confirmar',
        'resend' => 'Reenviar código',
        'resend_in' => 'Reenviar em :seconds s',
        'resent' => 'O código foi reenviado.',
        'resend_too_soon' => 'O código já foi enviado. Você pode solicitar um novo em um minuto.',
        'send_failed' => 'Não foi possível enviar o código: o e-mail ou o SMS está indisponível. Tente outra forma ou fale com o administrador.',
        'code_required' => 'Digite o código.',
        'code_invalid' => 'O código está incorreto ou expirou. Verifique-o ou solicite um novo.',
        'throttle' => 'Muitas tentativas. Tente novamente em :seconds s.',
        'expired' => 'O login expirou. Entre novamente.',
        'trust' => 'Não perguntar neste dispositivo por :days dia|Não perguntar neste dispositivo por :days dias',
        'other_ways' => 'Outras formas de confirmar',
        'use_recovery' => 'Usar um código de recuperação',
        'recovery_hint' => 'Digite um dos códigos de recuperação que você salvou ao configurar o login em duas etapas. Cada código funciona uma única vez.',
        'recovery_code' => 'Código de recuperação',
        'recovery_invalid' => 'Este código de recuperação está incorreto ou já foi usado.',
        'recovery_left' => 'Você entrou com um código de recuperação. Códigos restantes: :count. Crie novos na página de segurança.',
        'back_to_factor' => 'Voltar',
        'cancel' => 'Entrar com outro usuário',
    ],

    'magic' => [
        'title' => 'Entrar com um código',
        'hint' => 'Digite seu e-mail e enviaremos um código de seis dígitos. Não é preciso senha.',
        'email' => 'E-mail',
        'send' => 'Enviar o código',
        'sent' => 'Se existir uma conta com este endereço, um código está a caminho.',
        'check_mail' => 'Se :email pertencer a uma conta, enviamos um código para lá. Ele funciona uma única vez e por poucos minutos.',
        'other_email' => 'Usar outro e-mail',
        'email_required' => 'Digite seu e-mail.',
        'email_invalid' => 'Digite um e-mail válido.',
        'expired' => 'O código expirou. Solicite um novo.',
        'too_many' => 'Muitos códigos incorretos. Solicite um novo.',
    ],

    'passkey' => [
        'title' => 'Entrar com uma chave de acesso',
        'hint' => 'Seu dispositivo pedirá a impressão digital, o rosto ou o PIN. Não é preciso senha.',
        'button' => 'Continuar com uma chave de acesso',
        'failed' => 'Esta chave de acesso não funcionou. Tente novamente ou entre de outra forma.',
        'unsupported' => 'Este navegador não é compatível com chaves de acesso.',
        'cancelled' => 'A verificação da chave de acesso foi cancelada ou o tempo esgotou.',
    ],

    'security' => [
        'title' => 'Segurança do login',
        'required' => 'Sua conta exige uma segunda etapa: mantenha pelo menos uma forma de confirmar o login.',
        'optional' => 'Adicione uma segunda etapa para proteger sua conta mesmo que alguém descubra sua senha.',
        'must_enrol' => 'Configure uma segunda etapa antes de continuar: ela é obrigatória para sua conta.',
        'factors' => 'Suas formas de confirmar',
        'none' => 'Nada configurado ainda.',
        'none_short' => 'nenhuma',
        'added_at' => 'adicionado em :date',
        'used_at' => 'último uso em :date',
        'remove' => 'Remover',
        'removed' => 'Removido.',
        'added' => 'Adicionado. Ele será pedido no login.',
        'last_factor' => 'Esta é sua última forma de confirmar o login, e pelo menos uma é obrigatória. Adicione outra primeiro.',
        'name' => 'Nome (opcional)',
        'name_totp' => 'Celular',
        'name_passkey' => 'Notebook',
        'confirm' => 'Confirmar',
        'totp_hint' => 'Google Authenticator, Microsoft Authenticator, 1Password, Aegis ou qualquer outro aplicativo que mostre códigos de seis dígitos.',
        'totp_add' => 'Configurar um aplicativo',
        'totp_another' => 'Adicionar outro aplicativo',
        'totp_manual' => 'Escaneie o QR code com o aplicativo ou digite esta chave:',
        'qr' => 'QR code para o aplicativo autenticador',
        'passkey_hint' => 'Impressão digital, rosto ou PIN neste dispositivo, ou uma chave de segurança, em vez de digitar um código.',
        'passkey_hint_passwordless' => 'Impressão digital, rosto ou PIN neste dispositivo, ou uma chave de segurança. Uma chave de acesso também permite entrar sem senha.',
        'passkey_add' => 'Adicionar uma chave de acesso',
        'passkey_failed' => 'A chave de acesso não foi salva. Tente novamente.',
        'phone' => 'Número de telefone com o código do país',
        'phone_invalid' => 'Digite o número de telefone com o código do país, por exemplo +1 555 0100.',
        'sms_add' => 'Enviar um código',
        'sms_sent' => 'Código enviado para :phone',
        'email_hint' => 'Um código enviado para :email a cada login.',
        'email_add' => 'Usar códigos por e-mail',
        'recovery' => 'Códigos de recuperação',
        'recovery_left' => 'Resta um código não usado.|Restam :count códigos não usados.',
        'recovery_regenerate' => 'Criar novos códigos',
        'recovery_new' => 'Seus códigos de recuperação',
        'recovery_once' => 'Guarde-os agora em um lugar seguro: eles são mostrados apenas uma vez. Cada código deixa você entrar uma vez se perder o celular ou a chave. Os códigos antigos deixam de funcionar.',
        'devices' => 'Dispositivos confiáveis',
        'devices_none' => 'Nenhum dispositivo pula a segunda etapa.',
        'devices_revoke' => 'Esquecer todos os dispositivos',
        'devices_revoked' => 'Todos os dispositivos voltarão a receber o pedido da segunda etapa.',
        'until' => 'até :date',
        'events' => 'Atividade recente',
    ],

    'events' => [
        'signed_in' => 'Login realizado',
        'challenged' => 'Segunda etapa solicitada',
        'failed' => 'Resposta incorreta',
        'locked' => 'Bloqueado por um tempo',
        'trusted_device' => 'Dispositivo confiável, etapa ignorada',
        'code_sent' => 'Código enviado',
        'factor_added' => 'Forma de confirmar adicionada',
        'factor_removed' => 'Forma de confirmar removida',
        'recovery_used' => 'Código de recuperação usado',
        'recovery_generated' => 'Novos códigos de recuperação',
        'device_trusted' => 'Dispositivo marcado como confiável',
        'device_revoked' => 'Dispositivos esquecidos',
        'reset' => 'Redefinido por um administrador',
        'social_linked' => 'Conta social vinculada',
        'social_unlinked' => 'Conta social desvinculada',
        'social_refused' => 'Login social recusado',
    ],

    'log' => [
        'when' => 'Quando',
        'who' => 'Quem',
        'what' => 'Evento',
        'how' => 'Método',
        'where' => 'Endereço IP',
        'guard' => 'Onde',
        'all' => 'Todos',
        'filter' => 'Mostrar',
        'empty' => 'Nada ainda.',
        'by_admin' => 'administrador nº :id',
        'pages' => 'Páginas',
        'newer' => 'Mais recentes',
        'older' => 'Mais antigos',
    ],

    'manage' => [
        'hint' => 'Se alguém perdeu o celular ou a chave, redefina a segunda etapa dessa pessoa: ela entra com a senha e a configura de novo.',
        'search' => 'E-mail',
        'email' => 'E-mail',
        'reset' => 'Redefinir',
        'confirm' => 'Remover todas as formas de confirmar o login, os códigos de recuperação e os dispositivos confiáveis de :email?',
        'reset_done' => 'Redefinido. A pessoa vai configurar a segunda etapa de novo.',
    ],

    'menu' => [
        'title' => 'Login',
        'security' => 'Segurança do meu login',
        'log' => 'Registro de acessos',
        'people' => 'Redefinições em duas etapas',
    ],

    'settings' => [
        'description' => 'Um campo vazio mantém o valor de config/atrium-2fa.php.',
        'from_config' => 'Vazio: como no arquivo de configuração.',
        'required' => ':who: quem deve passar pela segunda etapa',
        'modes' => [
            'all' => 'Todos',
            'roles' => 'Superadministradores e os papéis escolhidos',
            'optional' => 'Somente quem a configurou',
        ],
        'roles' => ':who: papéis que devem passar por ela',
        'factors' => ':who: formas de confirmar permitidas',
        'passwordless' => ':who: entrar somente com uma chave de acesso',
        'magic_code' => ':who: entrar com um código por e-mail',
        'trusted_days' => ':who: dias em que um dispositivo confiável pula a etapa',
        'trusted_days_hint' => '0 desativa «confiar neste dispositivo».',
        'yes' => 'Sim',
        'no' => 'Não',
    ],

    'sms' => ':code é seu código de acesso a :site. Não o compartilhe.',

    'mail' => [
        'subject' => ':code é seu código de acesso a :site',
        'title' => [
            'two_factor' => 'Código de confirmação de acesso',
            'magic' => 'Código de acesso',
        ],
        'heading' => [
            'two_factor' => 'Confirmando o acesso a :site',
            'magic' => 'Acessando :site',
        ],
        'code_label' => 'Seu código:',
        'valid_for' => 'O código é válido por :count minuto.|O código é válido por :count minutos.',
        'not_you' => 'Se você não tentou entrar, não compartilhe este código com ninguém. Se ele chegou como segunda etapa, alguém conhece sua senha: altere-a.',
    ],
];
