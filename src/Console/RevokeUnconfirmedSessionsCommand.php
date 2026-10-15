<?php

/*
 * This file is part of jslirola/flarum-registration-policy.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Jslirola\RegistrationPolicy\Console;

use Flarum\Console\AbstractCommand;
use Flarum\Http\AccessToken;
use Symfony\Component\Console\Input\InputOption;

/**
 * Ends the sessions unconfirmed accounts already hold.
 *
 * Installing the extension stops new ones from being issued at sign-up, but
 * every account that registered before it still carries the five-year
 * remember token core gave it — and anything that turns a valid token into a
 * session (core's own cookie, maicol07/flarum-ext-sso's LoginMiddleware, a site
 * sharing the cookie) keeps honouring it. Run once after installing.
 *
 * Dry run unless given --execute, so forgetting an option cannot sign anybody out.
 * Developer API tokens are left alone: they are issued deliberately.
 */
class RevokeUnconfirmedSessionsCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->setName('registration-policy:revoke-unconfirmed')
            ->setDescription('Delete the session and remember tokens held by accounts whose email is unconfirmed.')
            ->addOption('execute', null, InputOption::VALUE_NONE, 'Delete them. Without it, only count them.');
    }

    protected function fire(): int
    {
        $query = AccessToken::query()
            ->whereIn('type', ['session', 'session_remember'])
            ->whereIn('user_id', function ($users) {
                $users->select('id')->from('users')->where('is_email_confirmed', false);
            });

        $tokens = (clone $query)->count();
        $accounts = (clone $query)->distinct()->count('user_id');

        if (! $this->input->getOption('execute')) {
            $this->info("Would delete $tokens token(s) held by $accounts unconfirmed account(s). Run again with --execute to delete them.");

            return 0;
        }

        $query->delete();

        $this->info("Deleted $tokens token(s) held by $accounts unconfirmed account(s).");

        return 0;
    }
}
