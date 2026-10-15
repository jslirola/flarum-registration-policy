# Changelog

## 1.0.0

First release, for Flarum 2.0.

- New members are not signed in at sign-up; the next page tells them to check their inbox.
- Clicking the confirmation link signs them in with a "remember me" session.
- Sign-ups with a provider-verified email (fof/oauth) still sign in straight away.
- Optional: refuse the right password while the email is unconfirmed (log in form, `POST /api/token`).
- Optional, with maicol07/flarum-ext-sso: accounts created through the forum's sign-up form must
  still confirm their email.
- `php flarum registration-policy:revoke-unconfirmed` ends the sessions unconfirmed accounts
  already hold.
- English and Spanish translations.
