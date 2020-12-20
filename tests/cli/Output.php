<?php

namespace Iqomp\Migrate\Tests\Cli;

class Output extends \Symfony\Component\Console\Output\ConsoleOutput
{
    protected $last_message;

    public function writeln($messages, int $options = self::OUTPUT_NORMAL)
    {
        $this->last_message = $messages;
    }

    public function getLastOutput()
    {
        return $this->last_message;
    }
}
