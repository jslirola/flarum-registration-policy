# Registration Policy

![License](https://img.shields.io/badge/license-MIT-blue.svg) [![CI](https://github.com/jslirola/flarum-registration-policy/actions/workflows/ci.yml/badge.svg)](https://github.com/jslirola/flarum-registration-policy/actions/workflows/ci.yml)

A [Flarum](https://flarum.org) extension. New members sign in only after they confirm their email address.

Out of the box, Flarum signs people in the moment they submit the sign-up form, with a "remember me" token that lasts five years — whether or not the address is real. With this extension the sign-up creates the account and sends the confirmation email, and the next page tells the member to check their inbox. Clicking the link signs them in and keeps them signed in.

Members who sign up with a login provider that verified their address (for example through [fof/oauth](https://github.com/FriendsOfFlarum/oauth)) still sign in straight away.

Right after signing up, the member sees where to look:

![Check your email dialog](https://raw.githubusercontent.com/jslirola/flarum-registration-policy/main/docs/sign-up-modal.png)

![Notice at the top of the forum](https://raw.githubusercontent.com/jslirola/flarum-registration-policy/main/docs/sign-up-notice.png)

The link in the email opens Flarum's own confirmation page, and that button is what signs them in:

![Confirm email page](https://raw.githubusercontent.com/jslirola/flarum-registration-policy/main/docs/confirm-email.png)

It pairs well with [fof/disposable-emails](https://github.com/FriendsOfFlarum/disposable-emails): that one stops throwaway addresses at the door, this one makes sure an address that slips through cannot do anything until it is proven real.

### Installation

```sh
composer require jslirola/flarum-registration-policy:"*"
php flarum extension:enable jslirola-registration-policy
```

### Updating

```sh
composer update jslirola/flarum-registration-policy
php flarum cache:clear
```

## Configuration

Nothing to set up: once enabled, new members sign in when they confirm their email. Two options under Extensions → Registration Policy, both on by default:

![Settings](https://raw.githubusercontent.com/jslirola/flarum-registration-policy/main/docs/settings.png)

- **Refuse sign-in while the email is unconfirmed**: the log in form and `POST /api/token` refuse the right password until the address is confirmed. A wrong password gets the usual answer, so nobody learns which accounts exist.
- **Require confirmation even with maicol07/flarum-ext-sso**: only shown when that extension is enabled. See [SSO](#single-sign-on) below.

### Members who signed up before

Accounts created before you installed the extension may still hold the session Flarum gave them. Count them, then end those sessions:

```sh
php flarum registration-policy:revoke-unconfirmed
php flarum registration-policy:revoke-unconfirmed --execute
```

Only sign-in sessions of unconfirmed accounts are removed. Developer API tokens are left alone.

### Single sign-on

[maicol07/flarum-ext-sso](https://github.com/maicol07/flarum-ext-sso) confirms every new account automatically. That is right for accounts its SSO client creates, but it also applies to the forum's own sign-up form. With that extension enabled, accounts created through the forum's form must still confirm their email, and accounts created by your SSO client keep their automatic confirmation.

If your site signs members in through `POST /api/token` and wants to tell "wrong password" from "email not confirmed", check `users.is_email_confirmed` after a refusal and verify the password against the stored hash.

### Compatibility

Requires Flarum 2.0. Tested on PHP 8.3 and 8.4 with SQLite, MySQL and PostgreSQL, alone and together with:

- [maicol07/flarum-ext-sso](https://github.com/maicol07/flarum-ext-sso)
- [fof/oauth](https://github.com/FriendsOfFlarum/oauth)
- [fof/terms](https://github.com/FriendsOfFlarum/terms)
- [fof/recaptcha](https://github.com/FriendsOfFlarum/recaptcha)
- [fof/disposable-emails](https://github.com/FriendsOfFlarum/disposable-emails)
- [flarum/approval](https://github.com/flarum/framework/tree/2.x/extensions/approval)

### Customizing Translations

The extension ships with English and Spanish. These are the keys shown to members:

```yaml
jslirola-registration-policy:
  forum:
    pending_confirmation:
      title: Check your email
      text: "Your account has been created, but you are not signed in yet. We have sent a confirmation link to {email}: open it to activate your account and sign in."
      spam_hint: If it does not arrive within a few minutes, check your spam folder.
      close_button: Got it
      notice: "Confirm your email address to sign in: we have sent a link to {email}."
```

Edit them from the admin panel with [FoF Linguist](https://github.com/FriendsOfFlarum/linguist), or send them to your language pack.

### Development

```sh
composer install
composer test:setup   # once; SQLite by default, DB_DRIVER=mysql or pgsql plus DB_* for the others
composer test

cd js && npm install && npm run build
```

`js/dist` is committed, as Flarum loads it straight from the package.

### Links

- [GitHub](https://github.com/jslirola/flarum-registration-policy)
- [Discussion on discuss.flarum.org](https://discuss.flarum.org/d/39787)

An extension by [jslirola](https://github.com/jslirola).
