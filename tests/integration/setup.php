<?php

/*
 * This file is part of jslirola/flarum-registration-policy.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Testing\integration\Setup\SetupScript;

require __DIR__.'/../../vendor/autoload.php';

$setup = new SetupScript();

$setup->run();

// flarum/testing 2.0.0 creates storage/{formatter,sessions,views} but not
// storage/locale, where core 2.0 caches its translation catalogue: every test
// then warns that file_put_contents() cannot open the cache file. Same path
// rule as Flarum\Testing\integration\UsesTmpDir::tmpDir().
$tmp = getenv('FLARUM_TEST_TMP_DIR_LOCAL') ?: getenv('FLARUM_TEST_TMP_DIR')
    ?: __DIR__.'/../../vendor/flarum/testing/src/integration/tmp';

if (! is_dir("$tmp/storage/locale")) {
    mkdir("$tmp/storage/locale", 0777, true);
}
