<?php

namespace Pickle\Build\Src;

use Pickle\Package;

interface Build
{
    public function __construct(Package $pkg, $options = null);
    public function prepare();
    public function phpize();
    public function configure($opts = null);
    public function make();
    public function install();
}
