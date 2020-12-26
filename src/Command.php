<?php

/**
 * Command executor
 * @package iqomp/migrate
 * @version 2.0.0
 */

namespace Iqomp\Migrate;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
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
            );
    }

    protected function execute(InputInterface $in, OutputInterface $out)
    {
        $action = $in->getArgument('action');

        if (!in_array($action, ['db', 'start', 'test', 'to'])) {
            $msg = 'Unknown action. Use action ( db | start | test | to )';
            $io = new SymfonyStyle($in, $out);
            $io->error($msg);
            return;
        }

        Migrator::init();
        call_user_func([Migrator::class, $action], $in, $out);
    }
}
