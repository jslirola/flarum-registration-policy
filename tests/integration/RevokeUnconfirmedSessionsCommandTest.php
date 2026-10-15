<?php

/*
 * This file is part of jslirola/flarum-registration-policy.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Jslirola\RegistrationPolicy\Tests\integration;

use Carbon\Carbon;
use Flarum\Testing\integration\ConsoleTestCase;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class RevokeUnconfirmedSessionsCommandTest extends ConsoleTestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('jslirola-registration-policy');

        $now = Carbon::now();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'pending', 'email' => 'pending@machine.local', 'is_email_confirmed' => 0],
            ],
            'access_tokens' => [
                ['token' => 'confirmed-remember', 'user_id' => 2, 'type' => 'session_remember', 'created_at' => $now, 'last_activity_at' => $now],
                ['token' => 'pending-remember', 'user_id' => 3, 'type' => 'session_remember', 'created_at' => $now, 'last_activity_at' => $now],
                ['token' => 'pending-session', 'user_id' => 3, 'type' => 'session', 'created_at' => $now, 'last_activity_at' => $now],
                ['token' => 'pending-developer', 'user_id' => 3, 'type' => 'developer', 'created_at' => $now, 'last_activity_at' => $now],
            ],
        ]);
    }

    #[Test]
    public function a_dry_run_deletes_nothing(): void
    {
        $output = $this->runCommand(['command' => 'registration-policy:revoke-unconfirmed']);

        $this->assertStringContainsString('Would delete 2 token(s) held by 1 unconfirmed account(s)', $output);
        $this->assertSame(4, $this->database()->table('access_tokens')->count());
    }

    #[Test]
    public function execute_deletes_only_the_sessions_of_unconfirmed_accounts(): void
    {
        $this->runCommand(['command' => 'registration-policy:revoke-unconfirmed', '--execute' => true]);

        $this->assertEqualsCanonicalizing(
            ['confirmed-remember', 'pending-developer'],
            $this->database()->table('access_tokens')->pluck('token')->all()
        );
    }
}
