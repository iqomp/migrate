<?php

/**
 * Migration action caller
 * @package iqomp/migrate
 * @version 3.0.0
 */

namespace Iqomp\Migrate;

use Hyperf\Command\Command as HyperfCommand;

class Migrator
{
    protected static $cli;

    protected static $migrations = [];
    protected static $connections = [];
    protected static $models = [];
    protected static $externals = [];

    protected static function execute(string $method, $options = null)
    {
        $migrators = config('model.migrators');
        $connections = [];

        foreach (self::$models as $model => $conns) {
            if (!isset($conns['write_conns'])) {
                continue;
            }

            $conn = $conns['write_conns'];
            $driver = $conn['driver'];

            if (!isset($migrators[$driver])) {
                continue;
            }

            $conn_name = $conn['name'];

            if (!isset($connections[$conn_name])) {
                $connections[$conn_name] = [];

                $migrator  = $migrators[$driver];
                $connections[$conn_name][] = new $migrator(self::$cli, $conn);
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
        $vendor_dir     = BASE_PATH . '/vendor';
        $composer_dir   = $vendor_dir . '/composer';
        $installed_file = $composer_dir . '/installed.json';

        if (!is_file($installed_file)) {
            return;
        }

        $installed_json = file_get_contents($installed_file);
        $installed      = json_decode($installed_json);
        $packages       = $installed->packages;

        // app composer.json file
        $app_composer_file = BASE_PATH . '/composer.json';
        if (is_file($app_composer_file)) {
            $app_composer = file_get_contents($app_composer_file);
            $app_composer = json_decode($app_composer);
            $app_composer->{'install-path'} = dirname($app_composer_file);
            $packages[] = $app_composer;
        }

        $result = [];

        // get all modules and app migrate file
        foreach ($packages as $package) {
            $migrate_file = $package->extra->iqomp->migrate ?? null;
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
        $connections = config('databases');
        foreach ($connections as $name => &$conn) {
            $conn['name'] = $name;
        }
        unset($conn);

        self::$connections = $connections;
    }

    protected static function populateModels(): void
    {
        $models = array_keys(self::$migrations);

        $conn_models = config('model.models');
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

    public static function init(HyperfCommand $cli): void
    {
        self::$cli = $cli;
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

    public static function db(): void
    {
        $migrators = config('model.migrators');

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
            $driver = $conn['driver'];

            if (!isset($migrators[$driver])) {
                continue;
            }

            $migrator = $migrators[$driver];
            $obj_conns[] = new $migrator(self::$cli, $conn);
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
            self::$cli->error($err);
        } else {
            self::$cli->info('All models database(s) already created');
        }
    }

    public static function start(): void
    {
        self::execute('syncTable');
    }

    public static function test(): void
    {
        self::execute('testTable');
    }

    public static function to(): void
    {
        self::execute('syncTableTo');
    }
}
