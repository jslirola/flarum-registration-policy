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

class PasswordCheckerTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('jslirola-registration-policy');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'pending', 'email' => 'pending@machine.local', 'is_email_confirmed' => 0, 'password' => $this->normalUser()['password']],
            ],
        ]);
    }

    #[Test]
    public function an_unconfirmed_account_with_the_right_password_is_refused(): void
    {
        $this->assertEquals(401, $this->token('pending', 'too-obscure'));
    }

    #[Test]
    public function an_unconfirmed_account_with_a_wrong_password_gets_the_usual_answer(): void
    {
        $this->assertEquals(401, $this->token('pending', 'wrong-password'));
    }

    #[Test]
    public function a_confirmed_account_signs_in(): void
    {
        $this->assertEquals(200, $this->token('normal', 'too-obscure'));
    }

    #[Test]
    public function an_unconfirmed_account_signs_in_when_the_setting_is_off(): void
    {
        $this->setting(Setting::BLOCK_TOKEN_UNCONFIRMED, false);

        $this->assertEquals(200, $this->token('pending', 'too-obscure'));
    }

    private function token(string $identification, string $password): int
    {
        return $this->send(
            $this->request('POST', '/api/token', [
                'json' => ['identification' => $identification, 'password' => $password],
            ])
        )->getStatusCode();
    }
}
