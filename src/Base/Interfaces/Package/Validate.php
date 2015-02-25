<?php

namespace Pickle\Base\Interfaces\Package;

interface Validate
{
    public function __construct($path, $cb = NULL);
    public function process();
}

