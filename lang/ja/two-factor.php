<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

/*
 * ログインの2段階目、パスワードなしのログイン、セキュリティページ、ログイン履歴。
 */
return [
    'module' => '2段階認証ログイン',
    'back_to_login' => 'ログインに戻る',

    'guards' => [
        'admin' => '管理パネル',
        'account' => '個人アカウント',
    ],

    'factors' => [
        'totp' => '認証アプリ',
        'passkey' => 'パスキー',
        'email' => 'メールで届くコード',
        'sms' => 'SMSで届くコード',
    ],

    'methods' => [
        'password' => 'パスワード',
        'code' => 'メールで届くコード',
        'passkey' => 'パスキー',
        'register' => '登録',
        'totp' => '認証アプリ',
        'email' => 'メールで届くコード',
        'sms' => 'SMSで届くコード',
        'recovery' => 'リカバリーコード',
    ],

    'login' => [
        'passkey' => 'パスキーでログイン',
        'code' => 'メールでコードを受け取る',
    ],

    'challenge' => [
        'title' => 'ログインの確認',
        'sent_to' => ':destination にコードを送信しました',
        'totp_hint' => '認証アプリを開き、このサイト用に表示されている6桁のコードを入力してください。',
        'passkey_hint' => 'このデバイスのパスキーまたはセキュリティキーを使います。指紋、顔、PINのいずれかです。',
        'use_passkey' => 'パスキーを使う',
        'code_label' => [
            'totp' => 'アプリのコード',
            'email' => 'メールのコード',
            'sms' => 'SMSのコード',
        ],
        'submit' => '確認',
        'resend' => 'コードを再送信',
        'resend_in' => ':seconds 秒後に再送信できます',
        'resent' => 'コードを再送信しました。',
        'resend_too_soon' => 'コードは既に送信されています。1分後に新しいコードをリクエストできます。',
        'send_failed' => 'コードを送信できませんでした。メールまたはSMSを利用できません。別の方法をお試しいただくか、管理者にお問い合わせください。',
        'code_required' => 'コードを入力してください。',
        'code_invalid' => 'コードが正しくないか、有効期限が切れています。コードを確認するか、新しいコードをリクエストしてください。',
        'throttle' => '試行回数が多すぎます。:seconds 秒後にもう一度お試しください。',
        'expired' => 'ログインの有効期限が切れました。もう一度ログインしてください。',
        'trust' => 'このデバイスでは :days 日間確認しない',
        'other_ways' => '他の確認方法',
        'use_recovery' => 'リカバリーコードを使う',
        'recovery_hint' => '2段階認証の設定時に保存したリカバリーコードを1つ入力してください。各コードは1回だけ使えます。',
        'recovery_code' => 'リカバリーコード',
        'recovery_invalid' => 'このリカバリーコードは正しくないか、既に使用されています。',
        'recovery_left' => 'リカバリーコードでログインしました。残りのコード: :count 個。セキュリティページで新しいコードを作成してください。',
        'back_to_factor' => '戻る',
        'cancel' => '別のユーザーでログイン',
    ],

    'magic' => [
        'title' => 'コードでログイン',
        'hint' => 'メールアドレスを入力すると、6桁のコードをお送りします。パスワードは不要です。',
        'email' => 'メールアドレス',
        'send' => 'コードを送信',
        'sent' => 'このアドレスのアカウントが存在する場合、コードを送信しました。',
        'check_mail' => ':email がアカウントに登録されている場合、コードを送信しました。コードは1回のみ、数分間有効です。',
        'other_email' => '別のメールアドレスを使う',
        'email_required' => 'メールアドレスを入力してください。',
        'email_invalid' => '有効なメールアドレスを入力してください。',
        'expired' => 'コードの有効期限が切れました。新しいコードをリクエストしてください。',
        'too_many' => '誤ったコードの入力が多すぎます。新しいコードをリクエストしてください。',
    ],

    'passkey' => [
        'title' => 'パスキーでログイン',
        'hint' => 'デバイスが指紋、顔、またはPINを求めます。パスワードは不要です。',
        'button' => 'パスキーで続ける',
        'failed' => 'このパスキーは使用できませんでした。もう一度お試しいただくか、別の方法でログインしてください。',
        'unsupported' => 'このブラウザはパスキーに対応していません。',
        'cancelled' => 'パスキーの確認がキャンセルされたか、タイムアウトしました。',
    ],

    'security' => [
        'title' => 'ログインのセキュリティ',
        'required' => 'お使いのアカウントでは2段階目の確認が必要です。ログインを確認する方法を少なくとも1つは残してください。',
        'optional' => 'パスワードが他人に知られてもアカウントを守れるよう、2段階目の確認を追加しましょう。',
        'must_enrol' => '続ける前に2段階目の確認を設定してください。お使いのアカウントでは必須です。',
        'factors' => '確認方法',
        'none' => 'まだ何も設定されていません。',
        'none_short' => 'なし',
        'added_at' => ':date に追加',
        'used_at' => '最終使用: :date',
        'remove' => '削除',
        'removed' => '削除しました。',
        'added' => '追加しました。ログイン時に求められます。',
        'last_factor' => 'これはログインを確認する最後の方法であり、少なくとも1つは必須です。先に別の方法を追加してください。',
        'name' => '名前（任意）',
        'name_totp' => 'スマートフォン',
        'name_passkey' => 'ノートパソコン',
        'confirm' => '確認',
        'totp_hint' => 'Google Authenticator、Microsoft Authenticator、1Password、Aegis など、6桁のコードを表示するアプリならどれでも使えます。',
        'totp_add' => 'アプリを設定',
        'totp_another' => '別のアプリを追加',
        'totp_manual' => 'アプリでQRコードをスキャンするか、次のキーを入力してください:',
        'qr' => '認証アプリ用のQRコード',
        'passkey_hint' => 'コードを入力する代わりに、このデバイスの指紋・顔・PIN、またはセキュリティキーを使います。',
        'passkey_hint_passwordless' => 'このデバイスの指紋・顔・PIN、またはセキュリティキーを使います。パスキーがあれば、パスワードなしでもログインできます。',
        'passkey_add' => 'パスキーを追加',
        'passkey_failed' => 'パスキーを保存できませんでした。もう一度お試しください。',
        'phone' => '国番号付きの電話番号',
        'phone_invalid' => '国番号付きの電話番号を入力してください。例: +1 555 0100',
        'sms_add' => 'コードを送信',
        'sms_sent' => ':phone にコードを送信しました',
        'email_hint' => 'ログインのたびに :email に送信されるコードです。',
        'email_add' => 'メールのコードを使う',
        'recovery' => 'リカバリーコード',
        'recovery_left' => '未使用のコードが :count 個残っています。',
        'recovery_regenerate' => '新しいコードを作成',
        'recovery_new' => 'リカバリーコード',
        'recovery_once' => '今すぐ安全な場所に保存してください。表示されるのは1回だけです。スマートフォンやキーを紛失した場合、各コードで1回ログインできます。古いコードは使えなくなります。',
        'devices' => '信頼済みデバイス',
        'devices_none' => '2段階目をスキップするデバイスはありません。',
        'devices_revoke' => 'すべてのデバイスを忘れる',
        'devices_revoked' => 'すべてのデバイスで、再び2段階目の確認が求められます。',
        'until' => ':date まで',
        'events' => '最近のアクティビティ',
    ],

    'events' => [
        'signed_in' => 'ログインしました',
        'challenged' => '2段階目を要求',
        'failed' => '回答が正しくありません',
        'locked' => '一時的にロック',
        'trusted_device' => '信頼済みデバイスのためスキップ',
        'code_sent' => 'コードを送信',
        'factor_added' => '確認方法を追加',
        'factor_removed' => '確認方法を削除',
        'recovery_used' => 'リカバリーコードを使用',
        'recovery_generated' => '新しいリカバリーコード',
        'device_trusted' => 'デバイスを信頼',
        'device_revoked' => 'デバイスを忘れた',
        'reset' => '管理者がリセット',
        'social_linked' => 'ソーシャルアカウントを連携',
        'social_unlinked' => 'ソーシャルアカウントの連携を解除',
        'social_refused' => 'ソーシャルログインを拒否',
    ],

    'log' => [
        'when' => '日時',
        'who' => 'ユーザー',
        'what' => 'イベント',
        'how' => '方法',
        'where' => 'IPアドレス',
        'guard' => '対象',
        'all' => 'すべて',
        'filter' => '表示',
        'empty' => 'まだ何もありません。',
        'by_admin' => '管理者 #:id',
        'pages' => 'ページ',
        'newer' => '新しい',
        'older' => '古い',
    ],

    'manage' => [
        'hint' => 'スマートフォンやキーを紛失した人の2段階目をリセットします。その人はパスワードでログインし、設定し直します。',
        'search' => 'メールアドレス',
        'email' => 'メールアドレス',
        'reset' => 'リセット',
        'confirm' => ':email のすべての確認方法、リカバリーコード、信頼済みデバイスを削除しますか？',
        'reset_done' => 'リセットしました。本人が2段階目を設定し直します。',
    ],

    'menu' => [
        'title' => 'ログイン',
        'security' => 'ログインのセキュリティ',
        'log' => 'ログイン履歴',
        'people' => '2段階認証のリセット',
    ],

    'settings' => [
        'description' => '空欄の場合は config/atrium-2fa.php の値が使われます。',
        'from_config' => '空欄: 設定ファイルと同じ。',
        'required' => ':who: 2段階目が必要な人',
        'modes' => [
            'all' => '全員',
            'roles' => 'スーパー管理者と選択したロール',
            'optional' => '設定した人のみ',
        ],
        'roles' => ':who: 2段階目が必要なロール',
        'factors' => ':who: 許可する確認方法',
        'passwordless' => ':who: パスキーのみでログイン',
        'magic_code' => ':who: メールのコードでログイン',
        'trusted_days' => ':who: 信頼済みデバイスが2段階目をスキップする日数',
        'trusted_days_hint' => '0 にすると「このデバイスを信頼する」が無効になります。',
        'yes' => 'はい',
        'no' => 'いいえ',
    ],

    'sms' => ':code は :site のログインコードです。他人に教えないでください。',

    'mail' => [
        'subject' => ':site のログインコード: :code',
        'title' => [
            'two_factor' => 'ログイン確認コード',
            'magic' => 'ログインコード',
        ],
        'heading' => [
            'two_factor' => ':site へのログインの確認',
            'magic' => ':site へのログイン',
        ],
        'code_label' => 'コード:',
        'valid_for' => 'このコードの有効期間は :count 分です。',
        'not_you' => 'ログインに心当たりがない場合は、このコードを誰にも教えないでください。2段階目として届いた場合は、パスワードが他人に知られています。パスワードを変更してください。',
    ],
];
