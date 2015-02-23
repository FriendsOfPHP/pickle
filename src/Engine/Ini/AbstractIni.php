<?php

namespace Pickle\Engine\Ini;

class AbstractIni
{
    protected $engine = NULL;

    public function __construct(\Pickle\Engine\Engine $php)
    {
        $this->engine = $php;
    }

    public function getengine()
    {
        return $this->engine;
    }
}

