<?php

/*
 * This file is part of jslirola/flarum-registration-policy.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Jslirola\RegistrationPolicy;

/**
 * Setting keys, in one place so PHP and the defaults in extend.php cannot drift.
 */
final class Setting
{
    /** Refuse a correct password while the account's email address is unconfirmed. */
    public const BLOCK_TOKEN_UNCONFIRMED = 'jslirola-registration-policy.block_token_unconfirmed';

    /**
     * Undo the activation maicol07/flarum-ext-sso applies to every new account,
     * for accounts created through the forum's own sign-up form.
     */
    public const OVERRIDE_SSO_ACTIVATION = 'jslirola-registration-policy.override_sso_activation';

    public static function enabled(?string $value): bool
    {
        return (bool) $value;
    }
}
