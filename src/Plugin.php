<?php

/**
 * Plugin registerer
 * @package iqomp\migrate
 * @version 1.0.0
 */

namespace Iqomp\Migrate;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\Capable;

class Plugin implements PluginInterface, Capable
{
    public function activate(Composer $composer, IOInterface $io)
    {
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
    }

    public function getCapabilities()
    {
        return [
            'Composer\\Plugin\\Capability\\CommandProvider' => 'Iqomp\\Migrate\\CommandProvider'
        ];
    }
}
