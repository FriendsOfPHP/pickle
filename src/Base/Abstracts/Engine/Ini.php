<?php

namespace Pickle\Base\Abstracts\Engine;

class Ini
{
    protected $engine = NULL;

    const PICKLE_HEADER = ';Pickle installed extension, do not edit this line and below';
    const PICKLE_FOOTER = ';Pickle installed extension, do not edit this line and above';

    public function __construct(\Pickle\Base\Interfaces\Engine $php)
    {
        $this->engine = $php;
    }

    public function getengine()
    {
        return $this->engine;
    }
}

