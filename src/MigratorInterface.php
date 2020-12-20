<?php

/**
 * Migrator interface
 * @package iqomp/migrate
 * @version 1.0.0
 */

namespace Iqomp\Migrate;

use Symfony\Component\Console\Input\InputInterface as In;
use Symfony\Component\Console\Output\OutputInterface as Out;

interface MigratorInterface
{
    /**
     * Construct migrator
     * @param InputInterface $in Command line input object
     * @param OutputInterface $out Command line output object
     * @param array $config Database connection config
     */
    public function __construct(In $in, Out $out, array $config);

    /**
     * Create the database
     * @return bool true on success, false otherwise
     */
    public function createDb(): bool;

    /**
     * Check if database exists based on current connection
     * @return bool true on exists false otherwise
     */
    public function dbExists(): bool;

    /**
     * Get last error accured
     * @return string on error exists, null otherwise
     */
    public function lastError(): ?string;

    /**
     * Sync migrate config to database table.
     * @param string $model Model name
     * @param string $table Table name
     * @param array $config Migration config
     * @return void
     */
    public function syncTable(string $model, string $table, array $config): void;

    /**
     * Create migration script to a file without executing it.
     * @param string $model Model name
     * @param string $table Table name
     * @param array $config Migration config
     * @param string $file Target script file.
     * @return void
     */
    public function syncTableTo(string $model, string $table, array $config, string $file): void;

    /**
     * Test migrate config to file without executing the migration. This action
     * means to print the comparation result to $out->writeln.
     * @param string $model Model name
     * @param string $table Table name
     * @param array $config Migration config
     * @return void
     */
    public function testTable(string $model, string $table, array $config): void;
}
