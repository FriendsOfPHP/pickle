<?php

namespace Pickle\Base\Interfaces\Package;

use Pickle\Base\Interfaces;

interface Info
{
    public function __construct(Interfaces\Package $package, $cb = null);
    public function show();
    public function getPackage();
}
