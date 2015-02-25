<?php

namespace Pickle\Base\Interfaces\Package;

interface Release
{
    public function __construct($path, $cb = NULL, $noConvert = false);
    public function create();
}

