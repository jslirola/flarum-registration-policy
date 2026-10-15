<?php

/*
 * This file is part of jslirola/flarum-registration-policy.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Jslirola\RegistrationPolicy\Listener;

use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\AccountActivationMailer;
use Flarum\User\EmailToken;
use Flarum\User\Event\Registered;
use Jslirola\RegistrationPolicy\RegistrationContext;
use Jslirola\RegistrationPolicy\Setting;

/**
 * Undoes maicol07/flarum-ext-sso's activation of form sign-ups.
 *
 * That extension listens to `Registered` and calls `$user->activate()` on
 * every new account. It is meant for accounts its SSO client creates — the
 * identity provider has already vouched for the address — but `Registered`
 * fires for the forum's own sign-up form too, so on a forum that has it nobody
 * ever has to confirm an email address, and RegisterController would sign them
 * all in.
 *
 * Registered only when that extension is enabled, and listed after it in
 * composer.json's optional-dependencies so this listener runs after its own.
 * It touches nothing outside the forum's sign-up form (RegistrationContext):
 * accounts created by the SSO client through the API, by its JWT endpoint, or
 * from a provider-verified registration token keep their activation.
 */
class RevertSsoActivation
{
    public function __construct(
        protected RegistrationContext $context,
        protected SettingsRepositoryInterface $settings,
        protected AccountActivationMailer $mailer
    ) {
    }

    public function handle(Registered $event): void
    {
        if (! $this->context->isFormSignUp() || $this->context->emailVerifiedByProvider()) {
            return;
        }

        if (! Setting::enabled($this->settings->get(Setting::OVERRIDE_SSO_ACTIVATION))) {
            return;
        }

        $user = $event->user;

        if (! $user->is_email_confirmed) {
            return;
        }

        $user->is_email_confirmed = false;
        $user->save();

        // Core's AccountActivationMailer listens to the same event and normally
        // runs first (core listeners are registered before any extension's), while
        // the account was still unconfirmed — so the email is already on its way.
        // Should that order ever change, it would have skipped a confirmed account:
        // send it here instead, once.
        $alreadySent = EmailToken::query()
            ->where('user_id', $user->id)
            ->where('email', $user->email)
            ->exists();

        if (! $alreadySent) {
            $this->mailer->handle($event);
        }
    }
}
