<?php

namespace Pickle\Base\Abstracts\Engine;

class Ini
{
    protected $engine = null;
    protected $path;
    protected $raw;

    protected $pickleHeaderStartPos = -1;
    protected $pickleHeaderEndPos = -1;
    protected $pickleFooterStartPos = -1;
    protected $pickleFooterEndPos = -1;

    const PICKLE_HEADER = ';Pickle installed extension, do not edit this line and below';
    const PICKLE_FOOTER = ';Pickle installed extension, do not edit this line and above';

    public function __construct(\Pickle\Base\Interfaces\Engine $php)
    {
        $this->engine = $php;
        $this->path   = $php->getIniPath();

        $this->raw = @file_get_contents($this->path);
        if (false === $this->raw) {
            throw new \Exception('Cannot read php.ini');
        }
    }

    public function getengine()
    {
        return $this->engine;
    }

    protected function getPickleSection()
    {
        return substr($this->raw, $this->pickleHeaderEndPos, $this->pickleFooterStartPos - $this->pickleHeaderEndPos);
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
