# Registration Policy for Flarum

Do not sign new members in until they confirm their email address.

Flarum signs every registrant in the moment the sign-up form is submitted — with a five-year
"remember me" token — whether or not the email address has been confirmed. On a forum that is
the community layer of another site (SSO, a shared cookie), that means an address nobody ever
confirmed, often a disposable one, gets a session everywhere. This extension moves the sign-in to
where it belongs: the moment the member clicks the link in the confirmation email, which Flarum
already handles.

Background and design discussion: <https://discuss.flarum.org/d/39787>.

## What it does

| | Core Flarum | With this extension |
|---|---|---|
| Sign up through the forum's form | Signed in at once (5-year token) | No session; the page that loads next shows a "check your email" dialog and notice with the address |
| Sign up with a provider-verified email (login provider registration token) | Signed in at once | Signed in at once — unchanged |
| Click the confirmation link | Signed in | Signed in — unchanged |
| Log in / `POST /api/token` with an unconfirmed email, right password | Allowed (guest-level permissions) | Refused (401) |
| Same, wrong password | Refused | Refused — same answer, so nothing leaks |

Each behaviour has its own setting in the admin panel, all on by default.

It does **not** reject disposable addresses itself: use
[fof/disposable-emails](https://github.com/FriendsOfFlarum/disposable-emails) for that. The two are
complementary — that one stops the address at the door, this one makes sure an address that got
through cannot do anything until it is proven real.

## Installation

```sh
composer require jslirola/flarum-registration-policy
php flarum extension:enable jslirola-registration-policy
php flarum cache:clear
```

Accounts that registered **before** you installed it still hold the session core gave them.
End those once (dry run first):

```sh
php flarum registration-policy:revoke-unconfirmed
php flarum registration-policy:revoke-unconfirmed --execute
```

Only `session` and `session_remember` tokens of unconfirmed accounts are deleted; developer API
tokens are left alone.

## Compatibility

Requires Flarum 2.0 (PHP 8.3+). CI runs the suite on PHP 8.3 and 8.4 against SQLite, MySQL 8.0 and
PostgreSQL 16, and fails on any warning or deprecation raised by this extension's code.

| Extension | What is tested |
|---|---|
| none (core only) | Sign-up gets no session |
| [maicol07/flarum-ext-sso](https://github.com/maicol07/flarum-ext-sso) | Form sign-ups must confirm; API-created accounts keep its activation. See below |
| [fof/oauth](https://github.com/FriendsOfFlarum/oauth) | A sign-up from a provider-verified email signs in and is linked to the provider |
| [fof/terms](https://github.com/FriendsOfFlarum/terms) | Not accepting is still rejected; accepting is recorded |
| [fof/recaptcha](https://github.com/FriendsOfFlarum/recaptcha) | Once configured, a sign-up without a token is still rejected |
| [fof/disposable-emails](https://github.com/FriendsOfFlarum/disposable-emails) | A disposable address is still rejected |
| [flarum/approval](https://github.com/flarum/framework/tree/2.x/extensions/approval) | Sign-up unaffected |

`tests/integration/CompatibilityMatrixTest.php` runs the same sign-up with each of them enabled
alone, with all of them at once and with none, and checks every time that the account is created
unconfirmed, holds no token, gets the confirmation email and that the next page shows the notice
once. Accepting a reCAPTCHA token is not tested: that would mean calling Google.

The "check your email" dialog does not touch the sign-up modal: core reloads the page after a
successful sign-up and other extensions (fof/recaptcha) wrap its submit, so the server hands the
address to the next page load instead (session, read once).

### maicol07/flarum-ext-sso

That extension confirms **every** account as it is created (`ActivateUser` on `Registered`). That
is right for accounts its SSO client creates — your identity provider vouched for the address — but
it also applies to the forum's own sign-up form, where nobody vouched for anything. So, when it is
enabled:

- Accounts created through the forum's sign-up form go back to unconfirmed and must confirm their
  email like anywhere else (setting *Require confirmation even with maicol07/flarum-ext-sso*).
- Accounts created by its PHP client (`POST /api/users` with a master key) or its JWT endpoint keep
  their automatic confirmation.
- Its client sign-in (`POST /api/token`) is refused for unconfirmed accounts while the password
  checker setting is on. If your site needs to tell "wrong password" from "unconfirmed email", check
  `users.is_email_confirmed` after a refusal and verify the password against the hash yourself.
- In provider mode, client sites are signed in when the member confirms their email — the moment
  Flarum itself signs them in.
- If its sign-up button points to your own site, the forum's sign-up form is never used and only
  the password checker matters.

## Development

```sh
composer install
composer test:setup   # once; SQLite by default, DB_DRIVER=mysql or pgsql + DB_* for the others
composer test

cd js && npm install && npm run build
```

`js/dist` is committed, as Flarum loads it straight from the package; CI fails if it is stale.

## License

MIT
