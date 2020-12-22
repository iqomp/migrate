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
    protected static $vendor_dir = '.';

    public function activate(Composer $composer, IOInterface $io)
    {
        self::$vendor_dir = $composer->getConfig()->get('vendor-dir');
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

    public static function getVendorDir(): string
    {
        return self::$vendor_dir;
    }

    public static function setVendorDir(string $dir): void
    {
        self::$vendor_dir = $dir;
    }
}
