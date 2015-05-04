<?php

namespace Pickle\Base\Abstracts\Package\Convey;

use Composer\IO\ConsoleIO;

abstract class Command
{
    protected $path;
    protected $version;
    protected $prettyVersion;
    protected $name;
    protected $stability;
    protected $url;
    protected $io;

    public function __construct($path, ConsoleIO $io)
    {
        $this->path = $path;
        $this->io = $io;

        $this->prepare();
    }

    abstract protected function prepare();

    public function execute($target, $no_convert)
    {
        throw new \Exception('No command::execute implementation found ');
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function getPrettyVersion()
    {
        return $this->prettyVersion;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getStability()
    {
        return $this->stability;
    }

    public function getUrl()
    {
        return $this->url;
    }
}
