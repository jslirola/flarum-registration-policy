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
| Sign up through the forum's form | Signed in at once (5-year token) | No session; the modal says "check your email" |
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

Requires Flarum 2.0.

| Extension | Status | Notes |
|---|---|---|
| none (core only) | Tested | |
| [maicol07/flarum-ext-sso](https://github.com/maicol07/flarum-ext-sso) | Tested | See below |
| [fof/oauth](https://github.com/FriendsOfFlarum/oauth) | Covered through core | Its sign-ups use a core registration token; one carrying an email is trusted and signs in as before |
| fof/disposable-emails, fof/recaptcha, fof/terms | Not affected by design | They validate the API request (`POST /users`), which this extension still makes exactly as core does |
| flarum/approval | Not affected by design | Works on posts, not on sessions |

"Tested" means the integration suite in this repository enables that extension and asserts the
behaviour. "Not affected by design" means the reasoning above, not a test — reports welcome.

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
composer test:setup   # once; SQLite by default, DB_DRIVER=mysql + DB_* for MySQL
composer test

cd js && npm install && npm run build
```

`js/dist` is committed, as Flarum loads it straight from the package; CI fails if it is stale.

## License

MIT
