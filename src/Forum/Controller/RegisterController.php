<?php

/*
 * This file is part of jslirola/flarum-registration-policy.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Jslirola\RegistrationPolicy\Forum\Controller;

use Flarum\Api\Client;
use Flarum\Forum\Controller\RegisterController as CoreRegisterController;
use Flarum\Http\RememberAccessToken;
use Flarum\Http\Rememberer;
use Flarum\Http\SessionAuthenticator;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\RegistrationToken;
use Flarum\User\User;
use Jslirola\RegistrationPolicy\RegistrationContext;
use Jslirola\RegistrationPolicy\Setting;
use Laminas\Diactoros\StreamFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Replaces core's POST /register.
 *
 * Core signs every registrant in straight away with a five-year remember
 * token, whether or not the email address has been confirmed. This one creates
 * the account the same way (an API call to POST /users, so validation, fof/terms,
 * fof/recaptcha, fof/disposable-emails and friends all still run) and signs the
 * member in only when the account is already confirmed — a provider-verified
 * address, typically. Everyone else signs in when they click the link in the
 * confirmation email, which core's ConfirmEmailController already does.
 */
class RegisterController implements RequestHandlerInterface
{
    /**
     * Added to the response's `meta` when no session was created, so the
     * sign-up modal can say "check your inbox" instead of reloading.
     */
    public const META_KEY = 'jslirolaRegistrationPolicy';

    public function __construct(
        protected CoreRegisterController $core,
        protected Client $api,
        protected SessionAuthenticator $authenticator,
        protected Rememberer $rememberer,
        protected SettingsRepositoryInterface $settings,
        protected RegistrationContext $context
    ) {
    }

    public function handle(Request $request): ResponseInterface
    {
        $attributes = $request->getParsedBody() ?? [];

        $this->context->begin($this->emailVerifiedByProvider($attributes));

        try {
            if (! Setting::enabled($this->settings->get(Setting::SKIP_LOGIN_UNCONFIRMED))) {
                return $this->core->handle($request);
            }

            $params = ['data' => ['type' => 'users', 'attributes' => $attributes]];

            $response = $this->api->withParentRequest($request)->withBody($params)->post('/users');
        } finally {
            $this->context->end();
        }

        $body = json_decode((string) $response->getBody(), associative: false);

        if (! isset($body->data)) {
            return $response;
        }

        // Read the account back: listeners (core's own token activation, the SSO
        // extension, this extension's revert) may have changed it after the API
        // serialised its response, and the guest actor cannot see the attribute anyway.
        $user = User::query()->find($body->data->id);

        if ($user !== null && $user->is_email_confirmed) {
            $token = RememberAccessToken::generate($user->id);

            $this->authenticator->logIn($request->getAttribute('session'), $token);

            return $this->rememberer->remember($response, $token);
        }

        $body->meta = (object) array_merge(
            (array) ($body->meta ?? []),
            [self::META_KEY => ['confirmationRequired' => true]]
        );

        return $response
            ->withoutHeader('Content-Length')
            ->withBody((new StreamFactory())->createStream((string) json_encode($body)));
    }

    /**
     * Core activates an account created from a registration token that carries
     * an email address (RegistrationToken::user_attributes['email']) — the
     * provider verified it. That is the one case where an activation during
     * sign-up is trustworthy.
     */
    protected function emailVerifiedByProvider(array $attributes): bool
    {
        $token = $attributes['token'] ?? null;

        if (! is_string($token) || $token === '') {
            return false;
        }

        $registrationToken = RegistrationToken::query()->find($token);

        return $registrationToken !== null
            && ! empty($registrationToken->user_attributes['email'] ?? null);
    }
}
