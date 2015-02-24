<?php

namespace Pickle\Base\Interfaces\Package\Convey;

use Composer\IO\ConsoleIO;

use Pickle\Base\Interfaces;

interface DefaultExecutor
{
    public function __construct(Interfaces\Package\Convey\Command $command);
    public function execute($target, $no_convert);
}

