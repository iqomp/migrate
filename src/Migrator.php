<?php

/**
 * Migration action caller
 * @package iqomp/config
 * @version 1.0.0
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
        $reflection    = new \ReflectionClass(ClassLoader::class);
        $projectRoot   = dirname($reflection->getFileName(), 3);
        $vendorDir     = $projectRoot . '/vendor';
        $composerDir   = $vendorDir   . '/composer';
        $installedFile = $composerDir . '/installed.json';

        if (!is_file($installedFile)) {
            return;
        }

        $installedJson = file_get_contents($installedFile);
        $installed     = json_decode($installedJson);
        $packages      = $installed->packages;

        // app composer.json file
        $appComposerFile = \Composer\Factory::getComposerFile();
        if (is_file($appComposerFile)) {
            $appComposer = file_get_contents($appComposerFile);
            $appComposer = json_decode($appComposer);
            $appComposer->{'install-path'} = dirname($appComposerFile);
            $packages[] = $appComposer;
        }

        $result = [];

        // get all modules and app migrate file
        foreach ($packages as $package) {
            $migrateFile = $package->extra->{'iqomp/migrate'} ?? null;
            if (!$migrateFile) {
                continue;
            }

            $installPath = $package->{'install-path'};

            $migrateFilePath  = realpath(implode('/', [
                $composerDir,
                $installPath,
                $migrateFile
            ]));

            if (!$migrateFilePath) {
                $migrateFilePath = realpath(implode('/', [
                    $installPath,
                    $migrateFile
                ]));
            }

            if (!$migrateFilePath || !is_file($migrateFilePath)) {
                continue;
            }

            $migrateContent = include $migrateFilePath;
            $result = array_replace_recursive($result, $migrateContent);
        }

        if (self::$externals) {
            foreach (self::$externals as $file) {
                $migrateContent = include $file;
                $result = array_replace_recursive($result, $migrateContent);
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
            $out->writeln('All model database already created');
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

    public static function to(In $in, Out $out, string $file): void
    {
        $dirname = dirname($file);
        if (!is_dir($dirname)) {
            $dirs = explode('/', $dirname);
            $current_dir = '';
            foreach ($dirs as $dir) {
                $current_dir .= '/' . $dir;
                if (!is_dir($current_dir)) {
                    mkdir($current_dir);
                }
            }
        }

        if (is_file($file)) {
            unlink($file);
        }

        self::execute($in, $out, 'syncTableTo', $file);
    }
}
