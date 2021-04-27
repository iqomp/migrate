<?php

/**
 * Command executor
 * @package iqomp/migrate
 * @version 3.0.0
 */

namespace Iqomp\Migrate;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;

/**
 * @Command
 */
class Command extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ConfigInterface
     */
    private $config;

    protected function configure()
    {
        $this->setDescription('Run the migration from config to db or file')
            ->addArgument(
                'action',
                InputArgument::REQUIRED,
                'What action do you want to run?'
            );
    }

    public function handle()
    {
        $action = $this->input->getArgument('action');

        if (!in_array($action, ['db', 'start', 'test', 'to'])) {
            $msg = 'Unknown action. Use action ( db | start | test | to )';

            return $this->error($msg);
        }

        Migrator::init($this);
        Migrator::$action();
    }

    public function __construct(ContainerInterface $container, ConfigInterface $config)
    {
        parent::__construct('iqomp:migrate');
        $this->container = $container;
        $this->config = $config;
    }
}
