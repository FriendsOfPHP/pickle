<?php

namespace Pickle\Package;

use Composer\IO\ConsoleIO;
use Pickle\Package\Convey\Command\Factory;
use Pickle\Package\Convey\Command\Type;
use Pickle\Base\Util;

class Convey
{
    protected $command;

    public function __construct($path, ConsoleIO $io)
    {
        if (!$path) {
            throw new \Exception('Path cannot be empty');
        }

        $type = Type::determine($path, (false === realpath($path)));
        $this->command = Factory::getCommand($type, $path, $io);
    }

    public function deliver($target = '', $no_convert = false)
    {
        $target = $target ? realpath($target) : Util\TmpDir::get().DIRECTORY_SEPARATOR.$this->command->getName();

        return $this->command->execute($target, $no_convert);
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
