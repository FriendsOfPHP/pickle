<?php

namespace Pickle\Package\Command;

use Pickle\Engine;
use Pickle\Package\PHP;
use Pickle\Package\HHVM;

class Release
{
    public static function factory($path, $cb, $noConvert = false, $binary = false)
    {
        $engine = Engine::factory();

        switch ($engine->getName()) {
            case 'php':
        if ($binary) {
            if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
                return new PHP\Command\Release\Windows\Binary($path, $cb, $noConvert);
            } else {
                throw new \Exception('Binary packaging not implemented for this platform, use the build system of your favourite package manager');
            }
        } else {
            return new PHP\Command\Release($path, $cb, $noConvert);
        }

            case 'hhvm':
                throw new \Exception("Not implemented for engine '{$engine->getName()}'");

            default:
                throw new \Exception("Unsupported engine '{$engine->getName()}'. Implement it!");
        }
    }
}
