<?php

/**
 * Config provider
 * @package iqomp/migrate
 * @version 3.0.0
 */

namespace Iqomp\Migrate;

class ConfigProvider
{
    public function __invoke()
    {
        return [
            'commands' => [
                Command::class
            ]
        ];
    }
}
