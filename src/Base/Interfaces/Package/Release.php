<?php

namespace Pickle\Base\Interfaces\Package;

interface Release
{
    public function __construct($path, $cb = null, $noConvert = false);
    public function create(array $args = array());
}
