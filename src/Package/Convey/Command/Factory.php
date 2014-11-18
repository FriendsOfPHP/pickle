<?php

namespace Pickle\Package\Convey\Command;

use Pickle\Package\Convey\Command\Type;
use Pickle\Package\Convey\Command\Pecl;
use Pickle\Package\Convey\Command\Git;
use Pickle\Package\Convey\Command\Tgz;
use Pickle\Package\Convey\Command\SrcDir;
use Pickle\Package\Convey\Command\Any;
use Composer\IO\ConsoleIO;

class Factory
{
    public static function getCommand($type, $path, ConsoleIO $io)
    {
        switch ($type) {
            case Type::PECL:
                return new Pecl($path, $io);

            case Type::GIT:
                return new Git($path, $io);

            case Type::TGZ:
                return new Tgz($path, $io);

            case Type::SRC_DIR:
                return new SrcDir($path, $io);
        }

        return new Any($path, $io);
    }
}
