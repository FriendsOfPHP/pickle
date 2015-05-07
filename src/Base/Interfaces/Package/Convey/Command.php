<?php

namespace Pickle\Base\Interfaces\Package\Convey;

use Composer\IO\ConsoleIO;

interface Command
{
    public function __construct($path, ConsoleIO $io);
    public function execute($target, $no_convert);
    public function getType();
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
