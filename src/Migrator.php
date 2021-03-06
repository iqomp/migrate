<?php

/**
 * Migration action caller
 * @package iqomp/config
 * @version 2.0.0
 */

namespace Iqomp\Migrate;

use Symfony\Component\Console\Input\InputInterface as In;
use Symfony\Component\Console\Output\OutputInterface as Out;
use Composer\Autoload\ClassLoader;
use Iqomp\Config\Fetcher as Config;

class Migrator
{
    protected static $migrations = [];
    protected static $connections = [];
    protected static $models = [];
    protected static $externals = [];

    protected static function execute(In $in, Out $out, string $method, $options = null)
    {
        $migrators = Config::get('database', 'migrators');
        $connections = [];

        foreach (self::$models as $model => $conns) {
            if (!isset($conns['write_conns'])) {
                continue;
            }

            $conn = $conns['write_conns'];
            $type = $conn['type'];

            if (!isset($migrators[$type])) {
                continue;
            }

            $conn_name = $conn['name'];

            if (!isset($connections[$conn_name])) {
                $connections[$conn_name] = [];

                $migrator  = $migrators[$type];
                $configs   = $conn['configs'];

                foreach ($configs as $config) {
                    $connections[$conn_name][] = new $migrator($in, $out, $config);
                }
            }

            $table = $model::$table;

            foreach ($connections[$conn_name] as $conn) {
                $config = self::$migrations[$model];
                $conn->$method($model, $table, $config, $options);
            }
        }
    }

    protected static function mergeMigrationConfig(): void
    {
        $vendor_dir     = Plugin::getVendorDir();
        $composer_dir   = $vendor_dir . '/composer';
        $installed_file = $composer_dir . '/installed.json';

        if (!is_file($installed_file)) {
            return;
        }

        $installed_json = file_get_contents($installed_file);
        $installed      = json_decode($installed_json);
        $packages       = $installed->packages;

        // app composer.json file
        $app_composer_file = \Composer\Factory::getComposerFile();
        if (is_file($app_composer_file)) {
            $app_composer = file_get_contents($app_composer_file);
            $app_composer = json_decode($app_composer);
            $app_composer->{'install-path'} = dirname($app_composer_file);
            $packages[] = $app_composer;
        }

        $result = [];

        // get all modules and app migrate file
        foreach ($packages as $package) {
            $migrate_file = $package->extra->{'iqomp/migrate'} ?? null;
            if (!$migrate_file) {
                continue;
            }

            $install_path = $package->{'install-path'};

            $migrate_file_path  = realpath(implode('/', [
                $composer_dir,
                $install_path,
                $migrate_file
            ]));

            if (!$migrate_file_path) {
                $migrate_file_path = realpath(implode('/', [
                    $install_path,
                    $migrate_file
                ]));
            }

            if (!$migrate_file_path || !is_file($migrate_file_path)) {
                continue;
            }

            $migrate_content = include $migrate_file_path;
            $result = array_replace_recursive($result, $migrate_content);
        }

        if (self::$externals) {
            foreach (self::$externals as $file) {
                $migrate_content = include $file;
                $result = array_replace_recursive($result, $migrate_content);
            }
        }

        self::$migrations = $result;
    }

    protected static function pupolateConnections(): void
    {
        $connections = Config::get('database', 'connections');
        foreach ($connections as $name => &$conn) {
            $conn['name'] = $name;
        }
        unset($conn);

        self::$connections = $connections;
    }

    protected static function populateModels(): void
    {
        $models = array_keys(self::$migrations);

        $conn_models = Config::get('database', 'models');
        $model_conns = [];
        foreach ($conn_models as $name => $conn) {
            $regex = preg_quote($name);
            $regex = str_replace('\*', '.+', $regex);

            foreach ($models as $model) {
                if (!preg_match('!^' . $regex . '$!', $model)) {
                    continue;
                }

                if (!isset($conn['read'])) {
                    $conn['read'] = 'default';
                }

                if (!isset($conn['write'])) {
                    $conn['write'] = 'default';
                }

                $model_conns[$model] = [
                    'read'  => self::$connections[$conn['read']] ?? null,
                    'write' => self::$connections[$conn['write']] ?? null
                ];
            }
        }

        foreach ($models as $model) {
            if (!isset($model_conns[$model])) {
                $model_conns[$model] = [
                    'read' => self::$connections['default'] ?? null,
                    'write' => self::$connections['default'] ?? null
                ];
            }
        }

        foreach ($model_conns as $model => $conns) {
            if (!isset($conns['read'])) {
                $conns['read'] = self::$connections['default'] ?? null;
            }

            if (!isset($conns['write'])) {
                $conns['write'] = self::$connections['default'] ?? null;
            }

            if (!$conns['write']) {
                continue;
            }

            $write_conn = $conns['write']['name'];

            if (isset(self::$connections[$write_conn])) {
                $conns['write_conns'] = self::$connections[$write_conn];
            }

            $model_conns[$model] = $conns;
        }

        self::$models = $model_conns;
    }

    public static function init(): void
    {
        // some class is not automatically loaded
        // we'll need to call it manually
        $vendor_dir = Plugin::getVendorDir();
        require_once $vendor_dir . '/autoload.php';

        self::mergeMigrationConfig();
        self::pupolateConnections();
        self::populateModels();
    }

    public static function addMigrateFile(string $file): void
    {
        if (is_file($file)) {
            self::$externals[] = $file;
        }
    }

    public static function db(In $in, Out $out): void
    {
        $migrators = Config::get('database', 'migrators');

        $check_conns = [];
        foreach (self::$models as $model => $conns) {
            if (!isset($conns['write_conns'])) {
                continue;
            }

            $conn_name = $conns['write_conns']['name'];
            if (isset($check_conns[$conn_name])) {
                continue;
            }

            $check_conns[$conn_name] = $conns['write_conns'];
        }

        $obj_conns = [];
        foreach ($check_conns as $name => $conn) {
            $type = $conn['type'];

            if (!isset($migrators[$type])) {
                continue;
            }

            $migrator = $migrators[$type];
            $configs  = $conn['configs'];

            foreach ($configs as $config) {
                $obj_conns[] = new $migrator($in, $out, $config);
            }
        }

        $err = null;
        foreach ($obj_conns as $conn) {
            if (!$conn->dbExists()) {
                if (!$conn->createDb()) {
                    $err = $conn->lastError();
                    break;
                }
            }
        }

        if ($err) {
            $out->writeln($err);
        } else {
            $out->writeln('<info>All models database(s) already created</info>');
        }
    }

    public static function start(In $in, Out $out): void
    {
        self::execute($in, $out, 'syncTable');
    }

    public static function test(In $in, Out $out): void
    {
        self::execute($in, $out, 'testTable');
    }

    public static function to(In $in, Out $out): void
    {
        self::execute($in, $out, 'syncTableTo');
    }
}
