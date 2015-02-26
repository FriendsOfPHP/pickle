<?php

namespace Pickle\Package\Command;

use Pickle\Base\Interfaces;
use Pickle\Engine;
use Pickle\Package\PHP;
use Pickle\Package\HHVM;

class Convert
{
    public static function factory($path, $cb)
    {
        $engine = Engine::factory();

        switch($engine->getName()) {
            case "php":
                return new PHP\Command\Convert($path, $cb);

            case "hhvm":
                throw new \Exception("Nothing to convert for engine '{$engine->getName()}'");

            default:
                throw new \Exception("Unsupported engine '{$engine->getName()}'. Implement it!");
        }
    }
}
