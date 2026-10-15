<?php

/*
 * This file is part of jslirola/flarum-registration-policy.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Jslirola\RegistrationPolicy\Forum\Content;

use Flarum\Frontend\Document;
use Illuminate\Contracts\Session\Session;
use Jslirola\RegistrationPolicy\Forum\Controller\RegisterController;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Hands the page that loads right after a sign-up the address the
 * confirmation email went to, once.
 *
 * Without it the visitor submits the form, the page reloads, they are still a
 * guest, and nothing says why. The forum JS turns the payload into a modal
 * and a notice.
 */
class PendingConfirmationNotice
{
    public function __invoke(Document $document, Request $request): void
    {
        $session = $request->getAttribute('session');

        if (! $session instanceof Session) {
            return;
        }

        $email = $session->pull(RegisterController::SESSION_KEY);

        if (is_string($email) && $email !== '') {
            $document->payload[RegisterController::META_KEY] = ['pendingEmail' => $email];
        }
    }
}
