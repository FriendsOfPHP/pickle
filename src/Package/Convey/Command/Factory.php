<?php

namespace Pickle\Package\Convey\Command;

use Pickle\Package\PHP\Convey\Command\Pecl;
use Composer\IO\ConsoleIO;

class Factory
{
    public static function getCommand($type, $path, ConsoleIO $io)
    {
        switch ($type) {
            case Type::PICKLE:
                return new Pickle($path, $io);
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

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
