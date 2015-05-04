<?php

namespace Pickle\Package\Convey\Command;

use Pickle\Base\Abstracts;
use Pickle\Base\Interfaces;

class Any extends Abstracts\Package\Convey\Command implements Interfaces\Package\Convey\Command
{
    protected function prepare()
    {
        throw new \Exception('Unsupported package type');
    }

    public function execute($target, $no_convert)
    {
        throw new \Exception('Unsupported package type');
    }

    public function getType()
    {
        throw new \Exception('Unsupported package type');
    }
}
