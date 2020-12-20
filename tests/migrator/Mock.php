<?php

namespace Iqomp\Migrate\Tests\Migrator;

use Iqomp\Migrate\MigratorInterface;
use Symfony\Component\Console\Input\InputInterface as In;
use Symfony\Component\Console\Output\OutputInterface as Out;

class Mock implements MigratorInterface
{
    protected $in;
    protected $out;
    protected $config;

    public function __construct(In $in, Out $out, array $config)
    {
        $this->in     = $in;
        $this->out    = $out;
        $this->config = $config;
    }

    public function createDb(): bool
    {
        return $this->config['createDb'] ?? true;
    }

    public function dbExists(): bool
    {
        return $this->config['dbExists'] ?? true;
    }

    public function lastError(): ?string
    {
        return $this->config['lastError'] ?? null;
    }

    public function syncTable(string $model, string $table, array $config): void
    {
        $this->out->writeln('syncTable');
    }

    public function syncTableTo(string $model, string $table, array $config, string $file): void
    {
        $this->out->writeln($file);
    }

    public function testTable(string $model, string $table, array $config): void
    {
        $this->out->writeln('testTable');
    }
}
