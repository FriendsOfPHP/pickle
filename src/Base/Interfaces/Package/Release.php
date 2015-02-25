<?php

namespace Pickle\Base\Interfaces\Package;

use Pickle\Base\Interfaces;

interface Release
{
    public function __construct($path, $cb, $noConvert);
    public function create();
}

