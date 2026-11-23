<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

/*
 * 登录的第二步、无密码登录、安全页面和登录日志。
 */
return [
    'module' => '两步验证登录',
    'back_to_login' => '返回登录',

    'guards' => [
        'admin' => '管理面板',
        'account' => '个人账户',
    ],

    'factors' => [
        'totp' => '验证器应用',
        'passkey' => '通行密钥',
        'email' => '邮件验证码',
        'sms' => '短信验证码',
    ],

    'methods' => [
        'password' => '密码',
        'code' => '邮件验证码',
        'passkey' => '通行密钥',
        'register' => '注册',
        'totp' => '验证器应用',
        'email' => '邮件验证码',
        'sms' => '短信验证码',
        'recovery' => '恢复码',
    ],

    'login' => [
        'passkey' => '使用通行密钥登录',
        'code' => '通过邮件获取验证码',
    ],

    'challenge' => [
        'title' => '确认登录',
        'sent_to' => '我们已将验证码发送至 :destination',
        'totp_hint' => '打开您的验证器应用，输入它为本网站显示的六位验证码。',
        'passkey_hint' => '使用此设备上的通行密钥或安全密钥：指纹、面容或 PIN 码。',
        'use_passkey' => '使用通行密钥',
        'code_label' => [
            'totp' => '应用中的验证码',
            'email' => '邮件中的验证码',
            'sms' => '短信中的验证码',
        ],
        'submit' => '确认',
        'resend' => '重新发送验证码',
        'resend_in' => ':seconds 秒后可重新发送',
        'resent' => '验证码已重新发送。',
        'resend_too_soon' => '验证码已发送。您可以在一分钟后重新获取。',
        'send_failed' => '无法发送验证码：邮件或短信服务不可用。请尝试其他方式，或联系管理员。',
        'code_required' => '请输入验证码。',
        'code_invalid' => '验证码错误或已过期。请检查验证码或重新获取。',
        'throttle' => '尝试次数过多。请在 :seconds 秒后重试。',
        'expired' => '登录已过期。请重新登录。',
        'trust' => '在此设备上 :days 天内不再询问',
        'other_ways' => '其他确认方式',
        'use_recovery' => '使用恢复码',
        'recovery_hint' => '输入您设置两步验证时保存的恢复码之一。每个恢复码只能使用一次。',
        'recovery_code' => '恢复码',
        'recovery_invalid' => '此恢复码错误或已被使用。',
        'recovery_left' => '您已使用恢复码登录。剩余恢复码：:count 个。请在安全页面生成新的恢复码。',
        'back_to_factor' => '返回',
        'cancel' => '以其他用户身份登录',
    ],

    'magic' => [
        'title' => '使用验证码登录',
        'hint' => '输入您的邮箱，我们会发送一个六位验证码。无需密码。',
        'email' => '邮箱',
        'send' => '发送验证码',
        'sent' => '如果存在使用该地址的账户，验证码已在路上。',
        'check_mail' => '如果 :email 属于某个账户，我们已向该邮箱发送了验证码。验证码只能使用一次，且几分钟内有效。',
        'other_email' => '使用其他邮箱',
        'email_required' => '请输入您的邮箱。',
        'email_invalid' => '请输入有效的邮箱地址。',
        'expired' => '验证码已过期。请重新获取。',
        'too_many' => '错误的验证码次数过多。请重新获取。',
    ],

    'passkey' => [
        'title' => '使用通行密钥登录',
        'hint' => '您的设备会要求验证指纹、面容或 PIN 码。无需密码。',
        'button' => '使用通行密钥继续',
        'failed' => '此通行密钥无法使用。请重试，或使用其他方式登录。',
        'unsupported' => '此浏览器不支持通行密钥。',
        'cancelled' => '通行密钥验证已取消或已超时。',
    ],

    'security' => [
        'title' => '登录安全',
        'required' => '您的账户需要第二步验证：请至少保留一种确认登录的方式。',
        'optional' => '添加第二步验证，即使有人知道了您的密码，也能保护您的账户。',
        'must_enrol' => '请先设置第二步验证再继续：您的账户必须启用它。',
        'factors' => '您的确认方式',
        'none' => '尚未设置任何方式。',
        'none_short' => '无',
        'added_at' => '添加于 :date',
        'used_at' => '上次使用于 :date',
        'remove' => '移除',
        'removed' => '已移除。',
        'added' => '已添加。登录时会要求使用它。',
        'last_factor' => '这是您最后一种确认登录的方式，而至少需要保留一种。请先添加其他方式。',
        'name' => '名称（可选）',
        'name_totp' => '手机',
        'name_passkey' => '笔记本电脑',
        'confirm' => '确认',
        'totp_hint' => 'Google Authenticator、Microsoft Authenticator、1Password、Aegis，或任何其他显示六位验证码的应用。',
        'totp_add' => '设置应用',
        'totp_another' => '添加另一个应用',
        'totp_manual' => '用应用扫描二维码，或输入此密钥：',
        'qr' => '验证器应用的二维码',
        'passkey_hint' => '使用此设备上的指纹、面容或 PIN 码，或安全密钥，无需输入验证码。',
        'passkey_hint_passwordless' => '使用此设备上的指纹、面容或 PIN 码，或安全密钥。通行密钥还能让您无需密码登录。',
        'passkey_add' => '添加通行密钥',
        'passkey_failed' => '通行密钥未能保存。请重试。',
        'phone' => '带国家区号的手机号码',
        'phone_invalid' => '请输入带国家区号的手机号码，例如 +1 555 0100。',
        'sms_add' => '发送验证码',
        'sms_sent' => '验证码已发送至 :phone',
        'email_hint' => '每次登录时发送到 :email 的验证码。',
        'email_add' => '使用邮件验证码',
        'recovery' => '恢复码',
        'recovery_left' => '还剩 :count 个未使用的恢复码。',
        'recovery_regenerate' => '生成新的恢复码',
        'recovery_new' => '您的恢复码',
        'recovery_once' => '请立即保存到安全的地方：它们只显示一次。如果您丢失手机或密钥，每个恢复码可让您登录一次。旧的恢复码将不再有效。',
        'devices' => '受信任的设备',
        'devices_none' => '没有可跳过第二步的设备。',
        'devices_revoke' => '忘记所有设备',
        'devices_revoked' => '所有设备将再次被要求完成第二步验证。',
        'until' => '至 :date',
        'events' => '最近活动',
    ],

    'events' => [
        'signed_in' => '已登录',
        'challenged' => '已要求第二步验证',
        'failed' => '回答错误',
        'locked' => '已暂时锁定',
        'trusted_device' => '受信任的设备，已跳过该步骤',
        'code_sent' => '验证码已发送',
        'factor_added' => '已添加确认方式',
        'factor_removed' => '已移除确认方式',
        'recovery_used' => '已使用恢复码',
        'recovery_generated' => '新的恢复码',
        'device_trusted' => '设备已受信任',
        'device_revoked' => '已忘记设备',
        'reset' => '已由管理员重置',
        'social_linked' => '已关联社交账号',
        'social_unlinked' => '已取消关联社交账号',
        'social_refused' => '社交账号登录被拒绝',
    ],

    'log' => [
        'when' => '时间',
        'who' => '用户',
        'what' => '事件',
        'how' => '方式',
        'where' => 'IP 地址',
        'guard' => '位置',
        'all' => '全部',
        'filter' => '显示',
        'empty' => '暂无记录。',
        'by_admin' => '管理员 #:id',
        'pages' => '页码',
        'newer' => '较新',
        'older' => '较早',
    ],

    'manage' => [
        'hint' => '如果有人丢失了手机或密钥，请重置其第二步验证：对方用密码登录后可重新设置。',
        'search' => '邮箱',
        'email' => '邮箱',
        'reset' => '重置',
        'confirm' => '要移除 :email 的所有确认登录方式、恢复码和受信任的设备吗？',
        'reset_done' => '已重置。对方将重新设置第二步验证。',
    ],

    'menu' => [
        'title' => '登录',
        'security' => '我的登录安全',
        'log' => '登录日志',
        'people' => '两步验证重置',
    ],

    'settings' => [
        'description' => '留空则沿用 config/atrium-2fa.php 中的值。',
        'from_config' => '留空：与配置文件一致。',
        'required' => ':who：必须完成第二步验证的人员',
        'modes' => [
            'all' => '所有人',
            'roles' => '超级管理员和所选角色',
            'optional' => '仅限已设置的人员',
        ],
        'roles' => ':who：必须完成它的角色',
        'factors' => ':who：允许的确认方式',
        'passwordless' => ':who：仅使用通行密钥登录',
        'magic_code' => ':who：使用邮件验证码登录',
        'trusted_days' => ':who：受信任的设备跳过该步骤的天数',
        'trusted_days_hint' => '0 表示关闭“信任此设备”。',
        'yes' => '是',
        'no' => '否',
    ],

    'sms' => ':code 是您登录 :site 的验证码。请勿告诉他人。',

    'mail' => [
        'subject' => ':code 是您登录 :site 的验证码',
        'title' => [
            'two_factor' => '登录确认验证码',
            'magic' => '登录验证码',
        ],
        'heading' => [
            'two_factor' => '确认登录 :site',
            'magic' => '登录 :site',
        ],
        'code_label' => '您的验证码：',
        'valid_for' => '验证码 :count 分钟内有效。',
        'not_you' => '如果您并未尝试登录，请勿将此验证码告诉任何人。如果它是作为第二步验证发来的，说明有人知道您的密码：请立即更改。',
    ],
];
