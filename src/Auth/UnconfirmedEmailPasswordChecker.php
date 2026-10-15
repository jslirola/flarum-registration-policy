<?php

/*
 * This file is part of jslirola/flarum-registration-policy.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Jslirola\RegistrationPolicy\Auth;

use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Illuminate\Contracts\Hashing\Hasher;
use Jslirola\RegistrationPolicy\Setting;

/**
 * Refuses to authenticate an account whose email address is unconfirmed.
 *
 * This is what closes POST /api/token and the forum's /login for those
 * accounts — the path an SSO client (maicol07/flarum-sso-plugin) and any
 * other integration use to obtain a session.
 *
 * It only says "no" when the password is right. A wrong password is left to
 * core's standard checker, so the answer to a wrong guess is the same whether
 * the account is confirmed or not, and a failed attempt does not reveal that
 * an unconfirmed account exists.
 */
class UnconfirmedEmailPasswordChecker
{
    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected Hasher $hasher
    ) {
    }

    /**
     * @return false|null `false` halts every other checker; `null` lets them decide.
     */
    public function __invoke(User $user, #[\SensitiveParameter] string $password): ?bool
    {
        if ($user->is_email_confirmed) {
            return null;
        }

        if (! Setting::enabled($this->settings->get(Setting::BLOCK_TOKEN_UNCONFIRMED))) {
            return null;
        }

        if ($user->password === null || ! $this->hasher->check($password, $user->password)) {
            return null;
        }

        return false;
    }
}
