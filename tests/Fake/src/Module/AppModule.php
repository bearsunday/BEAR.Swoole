<?php

declare(strict_types=1);

namespace BEAR\SwooleFake\Module;

use BEAR\Package\AbstractAppModule;
use BEAR\Package\PackageModule;
use Override;

/**
 * Its location fixes the app dir: Meta resolves {name}\Module\AppModule and walks up three
 * levels, so this file being here is what makes tests/Fake an application root.
 */
final class AppModule extends AbstractAppModule
{
    #[Override]
    protected function configure(): void
    {
        $this->install(new PackageModule());
    }
}
