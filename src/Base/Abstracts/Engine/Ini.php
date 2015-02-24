<?php

namespace Pickle\Base\Abstracts\Engine;

class Ini
{
    protected $engine = NULL;

    public function __construct(\Pickle\Base\Interfaces\Engine $php)
    {
        $this->engine = $php;
    }

    public function getengine()
    {
        return $this->engine;
    }
}

