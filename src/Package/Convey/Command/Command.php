<?php

namespace Pickle\Package\Convey\Command;

use Composer\IO\ConsoleIO;

interface Command
{
    public function __construct($path, ConsoleIO $io);
    public function execute($target, $no_convert);
    public function getType();
}

