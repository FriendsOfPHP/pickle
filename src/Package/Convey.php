<?php

namespace Pickle\Package;

use Pickle\Package;
use Composer\IO\ConsoleIO;

use Pickle\Package\Convey\Command;

class Convey
{
    protected $command;

    public function __construct($path, ConsoleIO $io)
    {
        if (!$path) {
            throw new \Exception("Path cannot be empty");
        }

        $type = Command\Type::determine($path, (false === realpath($path)));
        $this->command = Command\Factory::getCommand($type, $path, $io);
    }

    public function deliver($target = "", $no_convert = false)
    {
        $target = $target ? realpath($target) : sys_get_temp_dir() . DIRECTORY_SEPARATOR . $this->command->getName();

        return $this->command->execute($target, $no_convert);
    }
}

