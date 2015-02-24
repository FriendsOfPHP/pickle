<?php

namespace Pickle\Base\Interfaces\Package;

use Pickle\Base\Interfaces\Package;

interface Build
{
    public function __construct(Package $pkg, $options = null);
    public function prepare();
    public function phpize();
    public function configure($opts = null);
    public function make();
    public function install();
}
