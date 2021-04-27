<?php

/**
 * Migrator interface
 * @package iqomp/migrate
 * @version 3.0.0
 */

namespace Iqomp\Migrate;

use Hyperf\Command\Command as HyperfCommand;

interface MigratorInterface
{
    /**
     * Construct migrator
     * @param HyperfCommand $cli Command line object
     * @param array $config Database connection config
     */
    public function __construct(HyperfCommand $cli, array $config);

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
     * @return void
     */
    public function syncTableTo(string $model, string $table, array $config): void;

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
