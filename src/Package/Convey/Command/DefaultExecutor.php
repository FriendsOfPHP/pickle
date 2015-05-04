<?php

namespace Pickle\Package\Convey\Command;

use Pickle\Base\Interfaces;
use Pickle\Package\PHP;
use Pickle\Package\HHVM;
use Pickle\Engine;

class DefaultExecutor implements Interfaces\Package\Convey\DefaultExecutor
{
    protected $command;

    public static function factory(Interfaces\Package\Convey\Command $command)
    {
        $engine = Engine::factory();

        switch ($engine->getName()) {
            case 'php';

            return new PHP\Convey\Command\DefaultExecutor($command);

            case 'hhvm';

            return new HHVM\Convey\Command\DefaultExecutor($command);
        }

        return new self($command);
    }

    public function __construct(Interfaces\Package\Convey\Command $command)
    {
        $this->command = $command;
    }

    public function execute($target, $no_convert)
    {
        throw new \Exception('Default executor cannot be used without concrete implementation');
    }
}
