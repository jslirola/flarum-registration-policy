<?php

/*
 * This file is part of jslirola/flarum-registration-policy.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Jslirola\RegistrationPolicy;

use Flarum\Foundation\AbstractServiceProvider;

class RegistrationPolicyServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        // One instance per application, so the controller and the listener
        // see the same context during a request.
        $this->container->singleton(RegistrationContext::class);
    }
}
