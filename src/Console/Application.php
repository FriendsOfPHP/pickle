<?php

namespace Pickle\Console;

use Pickle\Base\Archive;
use Symfony\Component\Console\Application as BaseApplication;

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

        $commands[] = new Command\ValidateCommand();
        $commands[] = new Command\ConvertCommand();
        $commands[] = new Command\ReleaseCommand();
        $commands[] = new Command\InstallerCommand();
        $commands[] = new Command\InfoCommand();

        if (\Phar::running() !== '') {
            $commands[] = new Command\SelfUpdateCommand();
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
        );

        foreach ($required_exts as $ext) {
            if (!extension_loaded($ext)) {
                Throw new \Exception("Extension '$ext' required but not loaded, full required list: " . implode(", ", $required_exts));
            }
        }

        Archive\Factory::getUnzipperClassName();
        Archive\Factory::getZipperClassName();
    }
}
