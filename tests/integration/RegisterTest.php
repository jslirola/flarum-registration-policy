<?php

/*
 * This file is part of jslirola/flarum-registration-policy.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Jslirola\RegistrationPolicy\Tests\integration;

use Carbon\Carbon;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Jslirola\RegistrationPolicy\Forum\Controller\RegisterController;
use Jslirola\RegistrationPolicy\Setting;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;

class RegisterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('jslirola-registration-policy');
        $this->setting('allow_sign_up', true);
    }

    #[Test]
    public function an_unconfirmed_registrant_gets_no_session(): void
    {
        $response = $this->register(['username' => 'newbie', 'email' => 'newbie@machine.local', 'password' => 'too-obscure']);

        $this->assertEquals(201, $response->getStatusCode(), (string) $response->getBody());

        $user = User::query()->where('username', 'newbie')->firstOrFail();

        $this->assertFalse((bool) $user->is_email_confirmed);
        $this->assertSame(0, $this->tokensOf($user->id));
        $this->assertFalse($this->setsRememberCookie($response));

        $body = json_decode((string) $response->getBody(), true);
        $this->assertTrue($body['meta'][RegisterController::META_KEY]['confirmationRequired'] ?? false);

        // The confirmation email is still requested.
        $this->assertSame(1, $this->database()->table('email_tokens')->where('user_id', $user->id)->count());
    }

    #[Test]
    public function core_behaviour_returns_when_the_setting_is_off(): void
    {
        $this->setting(Setting::SKIP_LOGIN_UNCONFIRMED, false);

        $response = $this->register(['username' => 'newbie', 'email' => 'newbie@machine.local', 'password' => 'too-obscure']);

        $this->assertEquals(201, $response->getStatusCode(), (string) $response->getBody());

        $user = User::query()->where('username', 'newbie')->firstOrFail();

        $this->assertSame(1, $this->tokensOf($user->id));
        $this->assertTrue($this->setsRememberCookie($response));
    }

    #[Test]
    public function a_provider_verified_email_still_signs_in(): void
    {
        $this->prepareDatabase([
            'registration_tokens' => [
                [
                    'token' => 'provider-token',
                    'provider' => 'github',
                    'identifier' => '12345',
                    'user_attributes' => json_encode(['email' => 'verified@machine.local']),
                    'payload' => json_encode([]),
                    'created_at' => Carbon::now(),
                ],
            ],
        ]);

        $response = $this->register(['username' => 'verified', 'email' => 'verified@machine.local', 'token' => 'provider-token']);

        $this->assertEquals(201, $response->getStatusCode(), (string) $response->getBody());

        $user = User::query()->where('username', 'verified')->firstOrFail();

        $this->assertTrue((bool) $user->is_email_confirmed);
        $this->assertSame(1, $this->tokensOf($user->id));
        $this->assertTrue($this->setsRememberCookie($response));
    }

    #[Test]
    public function a_failed_sign_up_is_returned_untouched(): void
    {
        $response = $this->register(['username' => 'newbie', 'email' => 'not-an-email', 'password' => 'too-obscure']);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertSame(0, User::query()->where('username', 'newbie')->count());
    }

    #[Test]
    public function the_confirmation_link_signs_the_member_in(): void
    {
        $this->register(['username' => 'newbie', 'email' => 'newbie@machine.local', 'password' => 'too-obscure']);

        $user = User::query()->where('username', 'newbie')->firstOrFail();
        $token = $this->database()->table('email_tokens')->where('user_id', $user->id)->value('token');

        // The emailed link opens a page (GET); its button posts here.
        $response = $this->send(
            $this->request('POST', '/confirm/'.$token)->withAttribute('bypassCsrfToken', true)
        );

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue((bool) $user->fresh()->is_email_confirmed);
        $this->assertSame(1, $this->tokensOf($user->id));
    }

    private function register(array $attributes): ResponseInterface
    {
        // CSRF is core's business, not this extension's: skip it the way
        // requestAsUser() does for authenticated requests.
        return $this->send(
            $this->request('POST', '/register', ['json' => $attributes])
                ->withAttribute('bypassCsrfToken', true)
        );
    }

    private function tokensOf(int $userId): int
    {
        return $this->database()->table('access_tokens')->where('user_id', $userId)->count();
    }

    private function setsRememberCookie(ResponseInterface $response): bool
    {
        foreach ($response->getHeader('Set-Cookie') as $cookie) {
            if (str_contains($cookie, '_remember=') && ! str_contains($cookie, '_remember=;')) {
                return true;
            }
        }

        return false;
    }
}
