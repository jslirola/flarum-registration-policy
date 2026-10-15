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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;

/**
 * The extensions the README calls compatible, enabled one at a time, all
 * together and not at all. A sign-up through the forum's form must end the
 * same way in every case: account created, unconfirmed, no session, the
 * confirmation email requested and the next page told about it.
 *
 * fof/recaptcha is enabled but left unconfigured here (no keys, which is how
 * it behaves until an admin sets it up); its enforcement when configured is
 * checked separately below, since passing it would mean calling Google.
 */
class CompatibilityMatrixTest extends TestCase
{
    private const POLICY_ID = 1;

    public static function extensionSets(): array
    {
        return [
            'core only' => [[]],
            'flarum/approval' => [['flarum-flags', 'flarum-approval']],
            'fof/disposable-emails' => [['fof-disposable-emails']],
            'fof/terms' => [['fof-terms']],
            'fof/recaptcha (unconfigured)' => [['fof-recaptcha']],
            'fof/oauth' => [['fof-oauth']],
            'maicol07/flarum-ext-sso' => [['maicol07-sso']],
            'all of them' => [['flarum-flags', 'flarum-approval', 'fof-disposable-emails', 'fof-terms', 'fof-recaptcha', 'fof-oauth', 'maicol07-sso']],
        ];
    }

    #[Test]
    #[DataProvider('extensionSets')]
    public function a_form_sign_up_never_gets_a_session(array $extensions): void
    {
        $this->enable($extensions);

        $response = $this->register($this->attributes($extensions));

        $this->assertEquals(201, $response->getStatusCode(), (string) $response->getBody());

        $user = User::query()->where('username', 'newbie')->firstOrFail();

        $this->assertFalse((bool) $user->is_email_confirmed);
        $this->assertSame(0, $this->database()->table('access_tokens')->where('user_id', $user->id)->count());
        $this->assertSame(1, $this->database()->table('email_tokens')->where('user_id', $user->id)->count());

        $this->assertNoticeShownOnceAfter($response, 'newbie@machine.local');

        if (in_array('fof-terms', $extensions, true)) {
            $this->assertSame(1, $this->database()->table('fof_terms_policy_user')->where('user_id', $user->id)->count());
        }
    }

    #[Test]
    public function fof_terms_still_rejects_a_sign_up_that_did_not_accept_them(): void
    {
        $this->enable(['fof-terms']);

        $response = $this->register(['username' => 'newbie', 'email' => 'newbie@machine.local', 'password' => 'too-obscure']);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertSame(0, User::query()->where('username', 'newbie')->count());
    }

    #[Test]
    public function fof_recaptcha_still_rejects_a_sign_up_without_a_token_once_configured(): void
    {
        $this->enable(['fof-recaptcha']);
        $this->setting('fof-recaptcha.credentials.site', 'site-key');
        $this->setting('fof-recaptcha.credentials.secret', 'secret-key');
        $this->setting('fof-recaptcha.signup', true);

        $response = $this->register(['username' => 'newbie', 'email' => 'newbie@machine.local', 'password' => 'too-obscure']);

        $this->assertEquals(422, $response->getStatusCode(), (string) $response->getBody());
        $this->assertStringContainsString('g-recaptcha-response', (string) $response->getBody());
        $this->assertSame(0, User::query()->where('username', 'newbie')->count());
    }

    #[Test]
    public function fof_disposable_emails_still_rejects_a_disposable_address(): void
    {
        $this->enable(['fof-disposable-emails']);

        $response = $this->register(['username' => 'newbie', 'email' => 'newbie@mailinator.com', 'password' => 'too-obscure']);

        $this->assertEquals(422, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame(0, User::query()->where('username', 'newbie')->count());
    }

    #[Test]
    public function a_fof_oauth_sign_up_with_a_verified_email_still_signs_in(): void
    {
        $this->enable(['fof-oauth', 'maicol07-sso']);

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
        $this->assertSame(1, $this->database()->table('access_tokens')->where('user_id', $user->id)->count());
        $this->assertSame(1, $this->database()->table('login_providers')->where('user_id', $user->id)->count());
    }

    private function enable(array $extensions): void
    {
        $this->extension(...array_merge($extensions, ['jslirola-registration-policy']));
        $this->setting('allow_sign_up', true);

        if (in_array('fof-terms', $extensions, true)) {
            $this->prepareDatabase([
                'fof_terms_policies' => [
                    ['id' => self::POLICY_ID, 'sort' => 0, 'name' => 'Terms', 'url' => 'https://example.com/terms', 'terms_updated_at' => Carbon::now(), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
                ],
            ]);
        }
    }

    private function attributes(array $extensions): array
    {
        $attributes = ['username' => 'newbie', 'email' => 'newbie@machine.local', 'password' => 'too-obscure'];

        if (in_array('fof-terms', $extensions, true)) {
            $attributes['fof_terms_policy_'.self::POLICY_ID] = true;
        }

        return $attributes;
    }

    private function register(array $attributes): ResponseInterface
    {
        return $this->send(
            $this->request('POST', '/register', ['json' => $attributes])
                ->withAttribute('bypassCsrfToken', true)
        );
    }

    /**
     * The page loaded with the sign-up's session carries the address once,
     * and the one after it does not.
     */
    private function assertNoticeShownOnceAfter(ResponseInterface $signUp, string $email): void
    {
        $first = $this->send($this->request('GET', '/', ['cookiesFrom' => $signUp]));
        $this->assertEquals(200, $first->getStatusCode());
        $this->assertStringContainsString('"pendingEmail":"'.$email.'"', (string) $first->getBody());

        $second = $this->send($this->request('GET', '/', ['cookiesFrom' => $signUp]));
        $this->assertStringNotContainsString('pendingEmail', (string) $second->getBody());
    }
}
