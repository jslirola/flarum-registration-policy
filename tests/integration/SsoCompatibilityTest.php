<?php

/*
 * This file is part of jslirola/flarum-registration-policy.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Jslirola\RegistrationPolicy\Tests\integration;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Jslirola\RegistrationPolicy\Setting;
use PHPUnit\Framework\Attributes\Test;

/**
 * With maicol07/flarum-ext-sso enabled, whose ActivateUser listener confirms
 * every account that is created.
 */
class SsoCompatibilityTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('maicol07-sso', 'jslirola-registration-policy');
        $this->setting('allow_sign_up', true);
    }

    #[Test]
    public function a_form_sign_up_must_still_confirm_its_email(): void
    {
        $response = $this->registerThroughForm();

        $this->assertEquals(201, $response->getStatusCode(), (string) $response->getBody());

        $user = User::query()->where('username', 'newbie')->firstOrFail();

        $this->assertFalse((bool) $user->is_email_confirmed);
        $this->assertSame(0, $this->database()->table('access_tokens')->where('user_id', $user->id)->count());
        $this->assertSame(1, $this->database()->table('email_tokens')->where('user_id', $user->id)->count());
    }

    #[Test]
    public function the_sso_activation_is_kept_when_the_override_is_off(): void
    {
        $this->setting(Setting::OVERRIDE_SSO_ACTIVATION, false);

        $this->registerThroughForm();

        $user = User::query()->where('username', 'newbie')->firstOrFail();

        $this->assertTrue((bool) $user->is_email_confirmed);
    }

    #[Test]
    public function an_account_created_through_the_api_keeps_the_sso_activation(): void
    {
        // What the SSO client does: POST /api/users with a master key, as an admin.
        $response = $this->send(
            $this->request('POST', '/api/users', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'users',
                        'attributes' => [
                            'username' => 'ssouser',
                            'email' => 'ssouser@machine.local',
                            'password' => 'too-obscure',
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode(), (string) $response->getBody());

        $this->assertTrue((bool) User::query()->where('username', 'ssouser')->firstOrFail()->is_email_confirmed);
    }

    private function registerThroughForm()
    {
        return $this->send(
            $this->request('POST', '/register', ['json' => [
                'username' => 'newbie',
                'email' => 'newbie@machine.local',
                'password' => 'too-obscure',
            ]])->withAttribute('bypassCsrfToken', true)
        );
    }
}
