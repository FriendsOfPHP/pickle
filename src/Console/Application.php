<?php

namespace Pickle\Console;

use Symfony\Component\Console\Application as BaseApplication;
use Pickle\Console\Helper;

class Application extends BaseApplication
{
    const NAME = 'pickle';
    const VERSION = '@pickle-version@';

    public function __construct($name = null, $version = null)
    {
        self::checkExtensions();

        parent::__construct($name ?: static::NAME, $version ?: (static::VERSION === '@' . 'pickle-version@' ? 'source' : static::VERSION));
    }

    protected function getDefaultHelperSet()
    {
        $helperSet = parent::getDefaultHelperSet();

        $helperSet->set(new Helper\PackageHelper());

        return $helperSet;
    }

    protected function getDefaultCommands()
    {
        $commands = parent::getDefaultCommands();

        $commands[] = new Command\ValidateCommand;
        $commands[] = new Command\ConvertCommand;
        $commands[] = new Command\ReleaseCommand;
        $commands[] = new Command\InstallerCommand;
        $commands[] = new Command\InfoCommand;

        if (\Phar::running() !== '') {
            $commands[] = new Command\SelfUpdateCommand;
        }

        return $commands;
    }

    private static function checkExtensions()
    {
        $required_exts = array(
            "zlib",
            "mbstring",
            "simplexml",
            "json",
            "dom",
            "openssl",
            "phar",
            "zip",
        );

        foreach ($required_exts as $ext) {
            if (!extension_loaded($ext)) {
                die("Extension '$ext' required but not loaded, full required list: " . implode(", ", $required_exts));
            }
        }
    }
}
