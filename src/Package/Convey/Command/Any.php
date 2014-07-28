<?php

namespace Pickle\Package\Convey\Command;

use Composer\Config;

use Pickle\Package;
use Pickle\Package\Convey\Command;

class Pecl extends AbstractCommand implements Command\Command
{
    protected function prepare()
    {
        throw new \Exception("Any package handling not implemented");
    }

    public function execute($target, $no_convert)
    {
    }

    public function getType()
    {
    }
}
