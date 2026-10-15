<?php

/*
 * This file is part of jslirola/flarum-registration-policy.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Jslirola\RegistrationPolicy;

/**
 * Remembers, for the duration of one POST /register, that the account being
 * created comes from the forum's own sign-up form.
 *
 * A `Registered` listener has no way to tell that sign-up apart from an account
 * created through the API by an SSO client or an administrator, and only the
 * former must be held back. The controller opens the context around its API
 * call and always closes it, so nothing leaks into a later request served by
 * the same process.
 */
final class RegistrationContext
{
    private bool $active = false;

    private bool $emailVerifiedByProvider = false;

    public function begin(bool $emailVerifiedByProvider): void
    {
        $this->active = true;
        $this->emailVerifiedByProvider = $emailVerifiedByProvider;
    }

    public function end(): void
    {
        $this->active = false;
        $this->emailVerifiedByProvider = false;
    }

    /** Whether the current request is a sign-up through the forum's form. */
    public function isFormSignUp(): bool
    {
        return $this->active;
    }

    /**
     * Whether a login provider (a registration token carrying an email, as
     * fof/oauth issues) vouched for the address. Core activates those accounts
     * itself, and that activation is legitimate.
     */
    public function emailVerifiedByProvider(): bool
    {
        return $this->emailVerifiedByProvider;
    }
}
