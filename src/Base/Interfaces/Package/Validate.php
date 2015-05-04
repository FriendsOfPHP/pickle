<?php

namespace Pickle\Base\Interfaces\Package;

interface Validate
{
    public function __construct($path, $cb = null);
    public function process();
}
