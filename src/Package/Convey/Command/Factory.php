<?php

namespace Pickle\Package\Convey\Command;

use Pickle\Package\Convey\Command;
use Composer\IO\ConsoleIO;

class Factory
{
    public static function getCommand($type, $path, ConsoleIO $io)
    {
        switch ($type)
        {
            case Command\Type::PECL:
                return new Command\Pecl($path, $io);

            case Command\Type::GIT:
                return new Command\Git($path, $io);

            case Command\Type::TGZ:
                return new Command\TGZ($path, $io);

            case Command\Type::SRC_DIR:
                return new Command\SrcDir($path, $io);
        }


        return new Command\Any($path, $io);
    }
}
