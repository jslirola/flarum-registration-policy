<?php

/*
 * This file is part of jslirola/flarum-registration-policy.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Extend;
use Flarum\User\Event\Registered;
use Jslirola\RegistrationPolicy\Auth\UnconfirmedEmailPasswordChecker;
use Jslirola\RegistrationPolicy\Console\RevokeUnconfirmedSessionsCommand;
use Jslirola\RegistrationPolicy\Forum\Content\PendingConfirmationNotice;
use Jslirola\RegistrationPolicy\Forum\Controller\RegisterController;
use Jslirola\RegistrationPolicy\Listener\RevertSsoActivation;
use Jslirola\RegistrationPolicy\RegistrationPolicyServiceProvider;
use Jslirola\RegistrationPolicy\Setting;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->content(PendingConfirmationNotice::class),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\ServiceProvider())
        ->register(RegistrationPolicyServiceProvider::class),

    (new Extend\Routes('forum'))
        ->remove('register')
        ->post('/register', 'register', RegisterController::class),

    (new Extend\Auth())
        ->addPasswordChecker('jslirola-registration-policy', UnconfirmedEmailPasswordChecker::class),

    (new Extend\Settings())
        ->default(Setting::SKIP_LOGIN_UNCONFIRMED, true)
        ->default(Setting::BLOCK_TOKEN_UNCONFIRMED, true)
        ->default(Setting::OVERRIDE_SSO_ACTIVATION, true),

    (new Extend\Console())
        ->command(RevokeUnconfirmedSessionsCommand::class),

    // Only meaningful next to maicol07/flarum-ext-sso, which activates every
    // new account; see RevertSsoActivation.
    (new Extend\Conditional())
        ->whenExtensionEnabled('maicol07-sso', fn () => [
            (new Extend\Event())
                ->listen(Registered::class, RevertSsoActivation::class),
        ]),
];
