<?php

/**
 * Command provider
 * @package iqomp\migrate
 * @version 1.0.0
 */

namespace Iqomp\Migrate;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;

class CommandProvider implements CommandProviderCapability
{
    public function getCommands()
    {
        return [new Command()];
    }
}
