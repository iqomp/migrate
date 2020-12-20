<?php

/**
 * Command executor
 * @package iqomp/migrate
 * @version 1.0.0
 */

namespace Iqomp\Migrate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Composer\Command\BaseCommand;

class Command extends BaseCommand
{
    protected function configure()
    {
        $this->setName('migrate')
            ->setDescription('Run the migration from config to db or file')
            ->addArgument(
                'action',
                InputArgument::REQUIRED,
                'What action do you want to run?'
            )
            ->addArgument(
                'file',
                InputArgument::OPTIONAL,
                'Target file the migration',
                './migration'
            );
    }

    protected function execute(InputInterface $in, OutputInterface $out)
    {
        $action = $in->getArgument('action');
        $file   = $in->getArgument('file');

        if (!in_array($action, ['db', 'start', 'test', 'to'])) {
            $msg = 'Unknown action. Use db, start, test, or to [file]';
            $out->writeln('<error>Error: ' . $msg . '</error>');
            return;
        }

        Migrator::init();
        call_user_func([Migrator::class, $action], $in, $out, $file);
    }
}
