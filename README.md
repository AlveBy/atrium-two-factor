# Atrium two-factor sign-in

A second sign-in step for [Atrium](https://github.com/AlveBy/atrium), for the admin panel and the personal account, and ways in without a password.

- Authenticator apps (TOTP, RFC 6238) with a QR code, passkeys (WebAuthn), a code by e-mail, a code by SMS through a driver of your own.
- Required for everybody, for chosen roles, or optional per person; set per guard in the config and in the panel.
- Recovery codes shown once and stored hashed; "trust this device" for N days.
- Rate limits and lockout; a sign-in log of every guard; an admin can reset a person's factors, and the reset is logged.
- Signing in with a six-digit e-mail code or a passkey alone, without telling whether an address has an account.
- Secrets encrypted at rest. Pages in 15 languages, readable in all five admin themes.

## Install

```bash
composer require alveby/atrium-2fa
php artisan atrium:module:install alveby/atrium-2fa
```

Then **Modules → Two-factor sign-in** in the panel. Everything is described in [docs/two-factor.md](https://github.com/AlveBy/atrium/blob/main/docs/two-factor.md) ([по-русски](https://github.com/AlveBy/atrium/blob/main/docs/two-factor.ru.md)).

## License

MIT. This package is split from the [Atrium monorepo](https://github.com/AlveBy/atrium); issues and pull requests go there.
