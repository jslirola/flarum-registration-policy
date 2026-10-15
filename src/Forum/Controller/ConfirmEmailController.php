<?php

/*
 * This file is part of jslirola/flarum-registration-policy.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Jslirola\RegistrationPolicy\Forum\Controller;

use Flarum\Forum\Controller\ConfirmEmailController as CoreConfirmEmailController;
use Flarum\Http\RememberAccessToken;
use Flarum\Http\Rememberer;
use Flarum\Http\SessionAuthenticator;
use Flarum\Http\UrlGenerator;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Command\ConfirmEmail;
use Flarum\User\EmailToken;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Arr;
use Jslirola\RegistrationPolicy\Setting;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Replaces core's POST /confirm/{token} for account activations.
 *
 * Core signs the member in with a session token that dies with the browser
 * session, because the sign-up had already handed out a five-year remember
 * token. Now that the sign-up hands out nothing, this click is the member's
 * first sign-in, and it gets the remember token core would have given them at
 * sign-up — so they stay signed in, and anything that shares the remember
 * cookie (an SSO site) sees them too.
 *
 * An email *change* by an account that is already active goes to core's
 * controller untouched: that member chose their own sign-in when they logged in.
 */
class ConfirmEmailController implements RequestHandlerInterface
{
    public function __construct(
        protected CoreConfirmEmailController $core,
        protected Dispatcher $bus,
        protected UrlGenerator $url,
        protected SessionAuthenticator $authenticator,
        protected Rememberer $rememberer,
        protected SettingsRepositoryInterface $settings
    ) {
    }

    public function handle(Request $request): ResponseInterface
    {
        $token = (string) Arr::get($request->getQueryParams(), 'token');

        if (! Setting::enabled($this->settings->get(Setting::SKIP_LOGIN_UNCONFIRMED)) || ! $this->activatesAnAccount($token)) {
            return $this->core->handle($request);
        }

        $user = $this->bus->dispatch(new ConfirmEmail($token));

        $accessToken = RememberAccessToken::generate($user->id);

        $this->authenticator->logIn($request->getAttribute('session'), $accessToken);

        return $this->rememberer->remember(
            new RedirectResponse($this->url->to('forum')->base()),
            $accessToken
        );
    }

    /**
     * Whether this link confirms a brand-new account rather than a new address
     * for an active one. An invalid or expired token goes to core, which
     * answers it the way it always has.
     */
    protected function activatesAnAccount(string $token): bool
    {
        $emailToken = EmailToken::query()->find($token);

        return $emailToken !== null
            && $emailToken->user !== null
            && ! $emailToken->user->is_email_confirmed;
    }
}
