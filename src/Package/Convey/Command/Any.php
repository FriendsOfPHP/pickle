<?php

namespace Pickle\Package\Convey\Command;

use Composer\Config;
use Pickle\Package;
use Pickle\Package\Convey\Command\Command;

class Any extends AbstractCommand implements Command
{
    protected function prepare()
    {
        throw new \Exception("Unsupported package type");
    }

    public function execute($target, $no_convert)
    {
    }

    public function getType()
    {
    }
}
