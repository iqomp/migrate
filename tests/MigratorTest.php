<?php

namespace Iqomp\Migrate\Tests;

use PHPUnit\Framework\TestCase;
use Iqomp\Migrate\Migrator;
use Iqomp\Migrate\Plugin;
use Iqomp\Config\Fetcher as Config;

use Symfony\Component\Console\Input\ArgvInput as Input;
use Iqomp\Migrate\Tests\Cli\Output;

class MigratorTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        Migrator::addMigrateFile(__DIR__ . '/config/migrator.php');
        Plugin::setVendorDir(dirname(__DIR__) . '/vendor');
    }

    public function testCreateDbExists()
    {
        Config::fetchConfig();
        Config::addConfig([
            'database' => [
                'migrators' => [
                    'mock' => 'Iqomp\\Migrate\\Tests\\Migrator\\Mock'
                ],
                'connections' => [
                    'default' => [
                        'driver' => 'mock',
                        'type' => 'mock',
                        'configs' => [
                            'main' => [
                                'dbExists' => true
                            ]
                        ]
                    ]
                ]
            ]
        ]);
        Migrator::init();

        $in = new Input();
        $out = new Output();
        Migrator::db($in, $out);

        $this->assertEquals('<info>All models database(s) already created</info>', $out->getLastOutput());
    }

    public function testCreateDbNoConnections()
    {
        Config::fetchConfig();
        Config::addConfig([
            'database' => [
                'migrators' => [
                    'mock' => 'Iqomp\\Migrate\\Tests\\Migrator\\Mock'
                ],
                'connections' => []
            ]
        ]);
        Migrator::init();

        $in = new Input();
        $out = new Output();
        Migrator::db($in, $out);

        $this->assertEquals('<info>All models database(s) already created</info>', $out->getLastOutput());
    }

    public function testCreateDbFailCreateDb()
    {
        Config::fetchConfig();
        Config::addConfig([
            'database' => [
                'migrators' => [
                    'mock' => 'Iqomp\\Migrate\\Tests\\Migrator\\Mock'
                ],
                'connections' => [
                    'default' => [
                        'driver' => 'mock',
                        'type' => 'mock',
                        'configs' => [
                            'main' => [
                                'dbExists' => false,
                                'createDb' => false,
                                'lastError' => 'Failed'
                            ]
                        ]
                    ]
                ]
            ]
        ]);
        Migrator::init();

        $in = new Input();
        $out = new Output();
        Migrator::db($in, $out);

        $this->assertEquals('Failed', $out->getLastOutput());
    }

    public function testCreateDbOtherDriver()
    {
        Config::fetchConfig();
        Config::addConfig([
            'database' => [
                'migrators' => [
                    'mock' => 'Iqomp\\Migrate\\Tests\\Migrator\\Mock'
                ],
                'connections' => [
                    'default' => [
                        'driver' => 'mock',
                        'type' => 'mock',
                        'configs' => [
                            'main' => [
                                'dbExists' => true
                            ]
                        ]
                    ],
                    'mine' => [
                        'driver' => 'mock',
                        'type' => 'mock',
                        'configs' => [
                            'main' => [
                                'dbExists' => false,
                                'createDb' => false,
                                'lastError' => 'Failed'
                            ]
                        ]
                    ]
                ],
                'models' => [
                    'Iqomp\\Migrate\\Tests\Model\\*' => [
                        'read' => 'mine',
                        'write' => 'mine'
                    ]
                ]
            ]
        ]);
        Migrator::init();

        $in = new Input();
        $out = new Output();
        Migrator::db($in, $out);

        $this->assertEquals('Failed', $out->getLastOutput());
    }

    public function testCreateDbSecondFail()
    {
        Config::fetchConfig();
        Config::addConfig([
            'database' => [
                'migrators' => [
                    'mock' => 'Iqomp\\Migrate\\Tests\\Migrator\\Mock'
                ],
                'connections' => [
                    'default' => [
                        'driver' => 'mock',
                        'type' => 'mock',
                        'configs' => [
                            'main' => [
                                'dbExists' => true
                            ],
                            'second' => [
                                'dbExists' => false,
                                'createDb' => false,
                                'lastError' => 'Failed'
                            ]
                        ]
                    ]
                ]
            ]
        ]);
        Migrator::init();

        $in = new Input();
        $out = new Output();
        Migrator::db($in, $out);

        $this->assertEquals('Failed', $out->getLastOutput());
    }

    public function testCreateSyncStart()
    {
        Config::fetchConfig();
        Config::addConfig([
            'database' => [
                'migrators' => [
                    'mock' => 'Iqomp\\Migrate\\Tests\\Migrator\\Mock'
                ],
                'connections' => [
                    'default' => [
                        'driver' => 'mock',
                        'type' => 'mock',
                        'configs' => [
                            'main' => []
                        ]
                    ]
                ]
            ]
        ]);
        Migrator::init();

        $in = new Input();
        $out = new Output();
        Migrator::start($in, $out);

        $this->assertEquals('syncTable', $out->getLastOutput());
    }

    public function testCreateSyncTest()
    {
        Config::fetchConfig();
        Config::addConfig([
            'database' => [
                'migrators' => [
                    'mock' => 'Iqomp\\Migrate\\Tests\\Migrator\\Mock'
                ],
                'connections' => [
                    'default' => [
                        'driver' => 'mock',
                        'type' => 'mock',
                        'configs' => [
                            'main' => []
                        ]
                    ]
                ]
            ]
        ]);
        Migrator::init();

        $in = new Input();
        $out = new Output();
        Migrator::test($in, $out);

        $this->assertEquals('testTable', $out->getLastOutput());
    }

    public function testCreateSyncTo()
    {
        Config::fetchConfig();
        Config::addConfig([
            'database' => [
                'migrators' => [
                    'mock' => 'Iqomp\\Migrate\\Tests\\Migrator\\Mock'
                ],
                'connections' => [
                    'default' => [
                        'driver' => 'mock',
                        'type' => 'mock',
                        'configs' => [
                            'main' => []
                        ]
                    ]
                ]
            ]
        ]);
        Migrator::init();

        $in = new Input();
        $out = new Output();
        Migrator::to($in, $out);

        $this->assertEquals('syncTableTo', $out->getLastOutput());
    }
}
